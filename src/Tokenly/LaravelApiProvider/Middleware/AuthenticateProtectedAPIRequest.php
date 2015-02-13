<?php

namespace Tokenly\LaravelApiProvider\Middleware;

use Closure;
use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Swapbot\Providers\EventLog\Facade\EventLog;
use Tokenly\HmacAuth\Exception\AuthorizationException;
use LaravelApiProvider\Contracts\APIUserRepositoryContract;
use LaravelApiProvider\Middleware\AuthenticateAPIRequest;

class AuthenticateProtectedPIRequest extends AuthenticateAPIRequest {

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    protected function initAuthenticator() {
        $auth = $this->auth;
        $this->hmac_validator  = new \Tokenly\HmacAuth\Validator(function($api_token) use ($auth) {
            // lookup the API secrect by $api_token using $this->auth
            $user = $this->user_repository->findByAPIToken($api_token);
            if (!$user) { return null; }

            // populate Guard with the $user
            $auth->setUser($user);

            // the purpose of this function is to look up the secret
            $api_secret = $user->getApiSecretKey();
            return $api_secret;
        });
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $authenticated = false;

        try {
            $authenticated = $this->hmac_validator->validateFromRequest($request);

        } catch (AuthorizationException $e) {
            // unauthorized
            $this->event_log->logError('error.auth.unauthenticated', $e, ['remoteIp' => $request->getClientIp()]);
            $error_message = $e->getAuthorizationErrorString();
            $error_code = $e->getCode();

            if (!$error_message) { $error_message = 'Authorization denied.'; }

        } catch (Exception $e) {
            // something else went wrong
            $this->event_log->logError('error.auth.unexpected', $e);
            $error_message = 'An unexpected error occurred';
            $error_code = 500;
        }

        if (!$authenticated) {
            $response = new JsonResponse([
                'message' => $error_message,
                'errors' => [$error_message],
            ], $error_code);
            return $response;
        }

        return $next($request);
    }

}
