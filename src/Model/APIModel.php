<?php

namespace Tokenly\LaravelApiProvider\Model;

use Illuminate\Database\Eloquent\Model;
use Tokenly\LaravelApiProvider\Model\Traits\SerializesForAPI;

/*
* APIModel
*/
class APIModel extends Model
{

    use SerializesForAPI;

    protected static $unguarded = true;

    protected $api_attributes = ['id'];

    public function getAPIAttributes() {
        return $this->api_attributes;
    }

}
