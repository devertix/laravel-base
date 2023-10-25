<?php

namespace Devertix\LaravelBase\Http\Controllers;

use App\Http\Controllers\Controller;
use Devertix\LaravelBase\Repositories\BaseRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use stdClass;

abstract class BaseResourceController extends Controller
{
    protected $repository;

    const DEFAULT_ADMIN_PAGER_LIMIT = 50;

    const ALLOW_LISTING_WO_PAGER = false;

    public function __construct(Request $request)
    {
        $this->repository = $this->getRepository();
    }

    abstract protected function getResource();

    abstract protected function getRepository(): BaseRepository;

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getFilterInfo(Request $request)
    {
        return null;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * If ALLOW_LISTING_WO_PAGER is true and the 'limit' query parameter is -1 then this action
     * responses with all entity without paging.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit') ?? null;
        $orderBy = $request->input('orderby', 'id');
        $sortOrder = $request->input('sortorder', 'asc');
        $orderInfo = ['order_by' => $orderBy, 'sort_order' => $sortOrder];

        if (static::ALLOW_LISTING_WO_PAGER && $limit == -1) {
            $entities = $this->repository->getFilteredOrdered(
                $this->getFilterInfo($request),
                $orderInfo
            );
            return $this->getCollectionResponse($entities);
        }

        $entities = $this->repository->getFilteredOrderedPaginated(
            $this->getFilterInfo($request),
            $orderInfo,
            ['limit' => $limit]
        );
        return $this->getPaginatedResponse($entities);
    }

    public function show($id)
    {
        $resource = $this->repository->getByIdOrFail($id);
        return $this->getItemResponse($resource);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return $this->getNoContentResponse();
    }

    public function getPaginatedResponse($model)
    {
        $resource = $this->getResource();
        $collection = $resource::collection($model);
        $response = $this->addPaginationToResponse($collection->response()->getData());
        return response()->json($response);
    }

    protected function addPaginationToResponse($response)
    {
        $meta = $response->meta;
        $response->meta = new stdClass;
        $response->meta->links = $meta->links;
        $response->meta->pagination = new stdClass;
        $response->meta->pagination->count = $meta->total;
        $response->meta->pagination->current_page = $meta->current_page;
        $response->meta->pagination->per_page = $meta->per_page;
        $response->meta->pagination->total = $meta->total;
        $response->meta->pagination->total_pages = $meta->last_page;
        return $response;
    }

    public function getCollectionResponse($model)
    {
        $resource = $this->getResource();
        $collection = $resource::collection($model);
        $response = $collection->response()->getData();
        return response()->json($response);
    }

    public function getCollectionUpdateResponse($model)
    {
        $resource = $this->getResource();
        $collection = new $resource($model);
        $response = $collection->response()->getData();

        foreach ($response->data as &$item) {
            unset($item->attributes);
        }

        return response()->json($response);
    }

    public function getItemResponse($model, $responseCode = Response::HTTP_OK)
    {
        $resource = $this->getResource();
        $collection = new $resource($model);
        $response = $collection->response()->getData();
        return response()->json($response, $responseCode);
    }

    public function getUpdateResponse($model, $responseCode = Response::HTTP_OK)
    {
        $resource = $this->getResource();
        $collection = new $resource($model);
        $response = $collection->response()->getData();
        return response()->json($response, $responseCode);
    }

    public function getNoContentResponse()
    {
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function getPaginationInfo(Request $request)
    {
        return [
            'limit' => $request->query('limit') ?? static::DEFAULT_ADMIN_PAGER_LIMIT,
        ];
    }

    public function getOrderInfo(Request $request)
    {
        return [
            'order_by' => $request->query('orderby', 'id'),
            'sort_order' => $request->query('sortorder', 'asc'),
        ];
    }

    public function notImplemented()
    {
        return response()->json([
            'errors' => 'Not implemented',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Validate additional fields
     *
     * Validate fields that are not compatible with Laravel's simple validation
     * method. Eg. in rules array: 'email' => 'unique:users' has to look for email
     * field in the database to check if its value is unique. In case of json api
     * we usually use field names like 'data.attributes.email'. In this case it
     * would not work.
     */
    public function validateAdditional(array $data, array $rules, array $messages = [])
    {
        Validator::make($data, $rules, $messages)->validate();
    }

    private function errorResponse($key, $response, $params = [])
    {
        return response()->json(['errors' => [
            [
                'status' => __($key, $params),
                'detail' => $response,
            ],
        ]], $response);
    }
}
