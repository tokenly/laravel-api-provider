<?php

namespace Tokenly\LaravelApiProvider\Events;

use Illuminate\Database\Eloquent\Model;

/**
* ModelUpdated event container
*/
class ModelUpdated
{
    
    public $model;
    public $attributes;

    function __construct(Model $model, $attributes)
    {
        $this->model = $model;
        $this->attributes = $attributes;
    }

}