<?php

namespace Tokenly\LaravelApiProvider\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Rhumsaa\Uuid\Uuid;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;
use Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract;
use Tokenly\TokenGenerator\TokenGenerator;
use \Exception;

/*
* UserRepository
*/
class UserRepository implements APIUserRepositoryContract
{

    function __construct(APIUserContract $user_model) {
        $this->user_model = $user_model;
    }


    public function findByAPIToken($api_token) {
        return $this->user_model->where('apitoken', $api_token)->first();
    }


    public function findByID($id) {
        return $this->user_model->find($id);
    }

    public function findByUuid($uuid) {
        return $this->user_model->where('uuid', $uuid)->first();
    }

    public function findAll() {
        return $this->user_model->all();
    }


    public function update(Model $model, $attributes) {
        return $model->update($attributes);
    }

    public function delete(Model $model) {
        return $model->delete();
    }

    public function create($attributes) {
        if (!isset($attributes['uuid'])) { $attributes['uuid'] = Uuid::uuid4()->toString(); }

        $attributes = $this->modifyAttributesBeforeCreate($attributes);

        return $this->user_model->create($attributes);
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
