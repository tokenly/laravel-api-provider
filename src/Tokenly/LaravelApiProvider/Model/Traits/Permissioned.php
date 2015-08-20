<?php

namespace Tokenly\LaravelApiProvider\Model\Traits;

trait Permissioned {

    public function hasPermission($privilege) {
        $privileges = $this['privileges'];
        return (isset($privileges[$privilege]) AND $privileges[$privilege]);
    }

}
