<?php

namespace Tokenly\LaravelApiProvider\Repositories;

use Illuminate\Database\Eloquent\Model;
use Rhumsaa\Uuid\Uuid;
use Tokenly\LaravelApiProvider\Contracts\APIResourceRepositoryContract;
use Tokenly\LaravelApiProvider\Repositories\BaseRepository;
use Exception;

/*
* APIRepository
*/
abstract class APIRepository extends BaseRepository implements APIResourceRepositoryContract
{

    // ------------------------------------------------------------------------
    // add UUID when creating

    public function create($attributes) {
        if (!isset($attributes['uuid'])) { $attributes['uuid'] = Uuid::uuid4()->toString(); }
        return parent::create($attributes);
    }


    // ------------------------------------------------------------------------
    // UUID methods

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

}
