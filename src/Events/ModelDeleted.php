<?php

namespace Tokenly\LaravelApiProvider\Events;

use Illuminate\Database\Eloquent\Model;

/**
* ModelDeleted event container
*/
class ModelDeleted
{
    
    public $model;

    function __construct(Model $model)
    {
        $this->model = $model;
    }

}