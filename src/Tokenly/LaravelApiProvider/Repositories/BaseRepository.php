<?php

namespace Tokenly\LaravelApiProvider\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Tokenly\LaravelApiProvider\Filter\RequestFilter;
use Exception;

/*
* BaseRepository
*/
abstract class BaseRepository
{

    // must define this when using the default constructor
    protected $model_type = '';

    protected $prototype_model;

    function __construct(Application $app) {
        $this->prototype_model = $app->make($this->model_type);
    }

    public function findByID($id) {
        return $this->prototype_model->find($id);
    }

    public function update(Model $model, $attributes) {
        $attributes = $this->modifyAttributesBeforeUpdate($attributes, $model);
        return $model->update($attributes);
    }

    public function delete(Model $model) {
        return $model->delete();
    }


    // ------------------------------------------------------------------------
    // API Model Contract

    public function create($attributes) {
        $attributes = $this->modifyAttributesBeforeCreate($attributes);

        return $this->prototype_model->create($attributes);
    }

    public function findAll(RequestFilter $filter=null) {
        if ($filter === null) {
            return $this->prototype_model->all();
        }

        $query = $this->prototype_model->newQuery();

        if ($filter !== null) {
            $filter->filter($query);
            $filter->limit($query);
            $filter->sort($query);
        }

        return $query->get();
    }

    // ------------------------------------------------------------------------
    // Modify Hooks
    
    protected function modifyAttributesBeforeCreate($attributes) {
        return $attributes;
    }

    protected function modifyAttributesBeforeUpdate($attributes, Model $model) {
        return $attributes;
    }

}
