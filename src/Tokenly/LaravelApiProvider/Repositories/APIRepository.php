<?php

namespace Tokenly\LaravelApiProvider\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Rhumsaa\Uuid\Uuid;
use Tokenly\LaravelApiProvider\Contracts\APIResourceRepositoryContract;
use \Exception;

/*
* APIRepository
*/
abstract class APIRepository implements APIResourceRepositoryContract
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


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // API Model Contract

    public function create($attributes) {
        if (!isset($attributes['uuid'])) { $attributes['uuid'] = Uuid::uuid4()->toString(); }

        $attributes = $this->modifyAttributesBeforeCreate($attributes);

        return $this->prototype_model->create($attributes);
    }

    public function findAll() {
        return $this->prototype_model->all();
    }

    public function findByUuid($uuid) {
        return $this->prototype_model->where('uuid', $uuid)->first();
    }

    public function updateByUuid($uuid, $attributes) {
        $model = $this->findByUuid($uuid);
        if (!$model) { throw new Exception("Unable to find model for uuid $uuid", 1); }
        $this->update($model, $attributes);
        return $model;
    }

    public function deleteByUuid($uuid) {
        $model = $this->findByUuid($uuid);
        if (!$model) { throw new Exception("Unable to find model for uuid $uuid", 1); }

        $this->delete($model);
        return $model;
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Modify
    
    protected function modifyAttributesBeforeCreate($attributes) {
        return $attributes;
    }

    protected function modifyAttributesBeforeUpdate($attributes, Model $model) {
        return $attributes;
    }

}
