<?php

namespace Tokenly\LaravelApiProvider\Contracts;

use Illuminate\Database\Eloquent\Model;

/*
* APIResourceRepositoryContract
*/
interface APIResourceRepositoryContract
{

    public function create($attributes);

    public function findAll();

    public function findByUuid($uuid);

    public function deleteByUuid($uuid);

    public function delete(Model $resource);

    public function updateByUuid($uuid, $attributes);

    public function update(Model $resource, $attributes);

}
