<?php

namespace Tokenly\LaravelApiProvider\Contracts;

/*
* APIPermissionedUserContract
*/
interface APIPermissionedUserContract extends APIUserContract
{

    public function hasPermission($privilege);

}
