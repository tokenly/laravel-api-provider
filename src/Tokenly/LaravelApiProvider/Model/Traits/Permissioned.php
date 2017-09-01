<?php

namespace Tokenly\LaravelApiProvider\Model\Traits;

trait Permissioned {

    public function hasPermission($privilege) {
        $privileges = $this['privileges'];
        return (isset($privileges[$privilege]) AND $privileges[$privilege]);
    }

    public function getCasts() {
        $casts = parent::getCasts();
        if (!isset($casts['privileges'])) {
            $casts['privileges'] = 'json';
        }

        return $casts;
    }

}
