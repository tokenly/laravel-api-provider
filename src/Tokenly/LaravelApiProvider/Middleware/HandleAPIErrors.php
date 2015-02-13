<?php

namespace Tokenly\LaravelApiProvider\Middleware;

use Closure;
use Exception;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Tokenly\LaravelEventLog\EventLog;

class HandleAPIErrors implements Middleware {

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(EventLog $event_log)
    {
        $this->event_log = $event_log;
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
        try {
            return $next($request);
        } catch (HttpResponseException $e) {
            // HttpResponseException can pass through
            throw $e;
        } catch (Exception $e) {
            $this->event_log->logError('error.api.uncaught', $e);

            // catch any uncaught exceptions
            //   and return a 500 response
            $response = new JsonResponse([
                'message' => 'Unable to process this request',
                'errors' => ['Unexpected error'],
            ], 500);
            return $response;
        }
    }

}
