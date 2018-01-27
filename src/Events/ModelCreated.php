<?php

namespace Tokenly\LaravelApiProvider\Events;

use Illuminate\Database\Eloquent\Model;

/**
* ModelCreated event container
*/
class ModelCreated
{
    
    public $model;
    public $attributes;

    function __construct(Model $model, $attributes)
    {
        $this->model = $model;
        $this->attributes = $attributes;
    }

}