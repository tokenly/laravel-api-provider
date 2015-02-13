<?php

namespace Tokenly\LaravelApiProvider\Contracts;

/*
* APIResourceRepositoryContract
*/
interface APIResourceRepositoryContract
{

    public function create($attributes);

    public function findAll();

    public function findByUuid($uuid);

    public function deleteByUuid($uuid);

    public function updateByUuid($uuid, $attributes);


}
