<?php

namespace Devertix\LaravelBase\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource implements BaseResourceInterface
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'type' => $this->getResourceKey(),
            'id' => $this->id,
            'attributes' => $this->getAttributes($request),
        ];
    }
}
