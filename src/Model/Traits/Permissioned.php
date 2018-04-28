<?php

namespace Tokenly\LaravelApiProvider\Model\Traits;

trait Permissioned {

    public function hasPermission($privilege) {
        $privileges = $this['privileges'];
        if(!is_array($privileges)){
            $privileges = json_decode($this['privileges'], true);
        }
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
