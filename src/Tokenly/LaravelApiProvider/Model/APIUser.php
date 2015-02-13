<?php

namespace Tokenly\LaravelApiProvider\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;
use Tokenly\LaravelApiProvider\Model\SerializesForAPI;

class APIUser extends Model implements AuthenticatableContract, APIUserContract {

    use Authenticatable;

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
