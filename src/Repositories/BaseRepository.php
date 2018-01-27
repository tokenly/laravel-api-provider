<?php

namespace Tokenly\LaravelApiProvider\Repositories;

use Tokenly\LaravelApiProvider\Events\ModelCreated;
use Tokenly\LaravelApiProvider\Events\ModelDeleted;
use Tokenly\LaravelApiProvider\Events\ModelUpdated;
use Tokenly\LaravelApiProvider\Repositories\Concerns\BroadcastsRepositoryEvents;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Tokenly\LaravelApiProvider\Filter\RequestFilter;

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
        $result = $model->update($attributes);

        // broadcast update event
        if ($this->usesTrait(BroadcastsRepositoryEvents::class)) {
            $this->broadcastRepositoryEvent(new ModelUpdated($model, $attributes));
        }

        return $result;
    }

    public function delete(Model $model) {
        $result = $model->delete();

        // broadcast delete event
        if ($this->usesTrait(BroadcastsRepositoryEvents::class)) {
            $this->broadcastRepositoryEvent(new ModelDeleted($model));
        }

        return $result;
    }


    // ------------------------------------------------------------------------
    // API Model Contract

    public function create($attributes) {
        $attributes = $this->modifyAttributesBeforeCreate($attributes);

        $model = $this->prototype_model->create($attributes);

        // broadcast create event
        if ($this->usesTrait(BroadcastsRepositoryEvents::class)) {
            $this->broadcastRepositoryEvent(new ModelCreated($model, $attributes));
        }

        return $model;
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

    // ------------------------------------------------------------------------
    // traits

    protected $_traits_used = [];

    protected function usesTrait($trait_class) {
        if (!isset($this->_traits_used[$trait_class])) {
            $this->_traits_used = class_uses($this);
        }

        return isset($this->_traits_used[$trait_class]);
    }
}
