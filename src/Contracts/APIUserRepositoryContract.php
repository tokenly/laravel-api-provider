<?php

namespace Tokenly\LaravelApiProvider\Contracts;

/*
* APIUserRepositoryContract
*/
interface APIUserRepositoryContract
{

    public function findByUuid($uuid);
    public function findByAPIToken($api_token);
    public function findAll();

    public function findByEmail($email);

}
