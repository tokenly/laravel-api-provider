<?php

namespace Tokenly\LaravelApiProvider\Contracts;

/*
* APIUserContract
*/
interface APIUserContract
{

    public function getID();

    public function getUuid();

    public function getApiSecretKey();

}
