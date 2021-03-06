<?php

namespace Tokenly\LaravelApiProvider\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Tokenly\LaravelApiProvider\Model\APIModel;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;
use Tokenly\LaravelApiProvider\Model\Traits\SerializesForAPI;

class APIUser extends Authenticatable implements AuthenticatableContract, APIUserContract {

    use SerializesForAPI;
    protected $api_attributes = ['id', 'api_token', ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    protected static $unguarded = true;


    public function getID() { return $this['id']; }
    public function getUuid() { return $this['uuid']; }
    public function getApiSecretKey() { return $this['apisecretkey']; }


}
