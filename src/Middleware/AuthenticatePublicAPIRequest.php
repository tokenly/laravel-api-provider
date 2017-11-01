<?php

namespace Tokenly\LaravelApiProvider\Middleware;

use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tokenly\HmacAuth\Exception\AuthorizationException;

class AuthenticatePublicAPIRequest extends AuthenticateAPIRequest {

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;


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
            // get the api token
            $api_token = $request->headers->get('X-Tokenly-Auth-Api-Token');
            if (!$api_token) { $api_token = $request->input('apitoken'); }
            if (!strlen($api_token)) { throw new AuthorizationException("Missing API Token"); }

            // load the user
            $user = $this->user_repository->findByAPIToken($api_token);
            if (!$user) { throw new AuthorizationException("Invalid API Token", "Failed to find user for token $api_token"); }

            // populate Guard with the $user
            $this->auth->setUser($user);

            $authenticated = true;

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
