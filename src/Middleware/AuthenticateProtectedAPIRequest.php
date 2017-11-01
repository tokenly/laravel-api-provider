<?php

namespace Tokenly\LaravelApiProvider\Middleware;

use Closure;
use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Tokenly\HmacAuth\Exception\AuthorizationException;
use Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract;

class AuthenticateProtectedAPIRequest extends AuthenticateAPIRequest {

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

        $substitions = Config::get('protectedApi.allowedSubstitutions');
        if ($substitions) {
            $this->hmac_validator->setSignedURLValidationFunction(function($actual_url, $signed_url) use ($substitions) {
                return $this->validateSignedURL($actual_url, $signed_url, $substitions);
            });
        }
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


    public function validateSignedURL($actual_url, $signed_url, $substitions) {
        $current_route = Request::route();
        $current_route_name = $current_route->getName();
        if (isset($substitions[$current_route_name])) {
            $substition = $substitions[$current_route_name];

            // make sure the host of the signed URL matches the host of the substitution URL
            if (isset($substition['host'])) {
                $signed_host = $this->getHostFromURL($signed_url);
                $allowed_host = $substition['host'];
                if ($signed_host != $allowed_host) {
                    Log::debug("HOST MISMATCH: \$signed_host={$signed_host} \$allowed_host={$allowed_host}");
                    // no host match - return false
                    return false;
                }
            }

            // check the route
            $substitute_route = new Route($current_route->getMethods(), $substition['route'], []);
            $signed_request = \Illuminate\Http\Request::create($signed_url, Request::method());
            if ($substitute_route->matches($signed_request)) {
                // the allowed substitute route matches the signed request
                return true;
            }

            Log::debug("ROUTE MISMATCH: pathinfo=".json_encode(Request::getFacadeRoot()->getPathInfo())." allowed route={$substition['route']}");
        }

        // this signed URL was not valid
        return false;
    }
    // ------------------------------------------------------------------------


    protected function getHostFromURL($url) {
        $pieces = parse_url($url);
        return $pieces['host'];
    }
}
