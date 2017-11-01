<?php

namespace Tokenly\LaravelApiProvider\Middleware;

use Illuminate\Contracts\Auth\Guard;
use Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract;
use Tokenly\LaravelEventLog\EventLog;

abstract class AuthenticateAPIRequest {

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth, APIUserRepositoryContract $user_repository, EventLog $event_log)
    {
        $this->user_repository = $user_repository;
        $this->auth            = $auth;
        $this->event_log       = $event_log;

        $this->initAuthenticator();
    }

    protected function initAuthenticator() {
        // abstract
    }

}
