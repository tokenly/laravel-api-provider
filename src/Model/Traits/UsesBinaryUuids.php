<?php

namespace Tokenly\LaravelApiProvider\Model\Traits;

use Tokenly\LaravelApiProvider\Model\Uuid\UuidHelper;


trait UsesBinaryUuids {

    public function setUuidAttribute($value)
    {
        $this->attributes['uuid'] = UuidHelper::toBinary($value);
    }

    public function getUuidAttribute($value)
    {
        return UuidHelper::toString($value);
    }

}
