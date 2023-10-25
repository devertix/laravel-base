<?php

namespace Devertix\LaravelBase\Http\Resources;

interface BaseResourceInterface
{
    public function getResourceKey();

    public function getAttributes($request);
}
