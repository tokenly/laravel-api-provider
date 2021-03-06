<?php

namespace Tokenly\LaravelApiProvider\Repositories;

use Illuminate\Support\Facades\Hash;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;
use Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use Tokenly\TokenGenerator\TokenGenerator;
use \Exception;

/*
* UserRepository
*/
class UserRepository extends APIRepository implements APIUserRepositoryContract
{

    function __construct(APIUserContract $prototype_model) {
        $this->prototype_model = $prototype_model;
    }


    public function findByAPIToken($api_token) {
        return $this->prototype_model->where('apitoken', $api_token)->first();
    }

    public function findByEmail($email) {
        return $this->prototype_model->where('email', $email)->first();
    }


    protected function modifyAttributesBeforeCreate($attributes) {
        $token_generator = new TokenGenerator();

        // create a token
        if (!isset($attributes['apitoken'])) {
            $attributes['apitoken'] = $token_generator->generateToken(16, 'T');
        }
        if (!isset($attributes['apisecretkey'])) {
            $attributes['apisecretkey'] = $token_generator->generateToken(40, 'K');
        }

        // hash any password
        if (isset($attributes['password']) AND strlen($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        } else {
            // un-guessable random password
            $attributes['password'] = Hash::make($token_generator->generateToken(34));
        }

        return $attributes;
    }

}
