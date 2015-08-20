<?php

namespace Tokenly\LaravelApiProvider\Contracts;

use Illuminate\Database\Eloquent\Model;
use Tokenly\LaravelApiProvider\Filter\RequestFilter;

/*
* APIResourceRepositoryContract
*/
interface APIResourceRepositoryContract
{

    public function create($attributes);

    public function findAll(RequestFilter $filter=null);

    public function findByUuid($uuid);

    public function deleteByUuid($uuid);

    public function delete(Model $resource);

    public function updateByUuid($uuid, $attributes);

    public function update(Model $resource, $attributes);

}
