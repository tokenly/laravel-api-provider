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
            try {
                $error_trace = $this->getExceptionTraceAsString($e);
            } catch (Exception $other_e) {
                $error_trace = "FAILED getExceptionTraceAsString: ".$other_e->getMessage()."\n\n".$e->getTraceAsString();
            }
            $this->event_log->logError('error.api.uncaught', $e, ['errorTrace' => $error_trace]);

            // catch any uncaught exceptions
            //   and return a 500 response
            $response = new JsonResponse([
                'message' => 'Unable to process this request',
                'errors' => ['Unexpected error'],
            ], 500);
            return $response;
        }
    }

    protected function getExceptionTraceAsString(Exception $exception) {
        $output = "";
        $count = 0;
        foreach ($exception->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (is_array($arg)) {
                        $args[] = "Array";
                    } elseif (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_bool($arg)) {
                        $args[] = ($arg) ? "true" : "false";
                    } elseif (is_object($arg)) {
                        $args[] = get_class($arg);
                    } elseif (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = join(", ", $args);
            }
            $output .= sprintf( "#%s %s(%s): %s(%s)\n",
                                     $count,
                                     isset($frame['file']) ? $frame['file'] : 'unknown file',
                                     isset($frame['line']) ? $frame['line'] : 'unknown line',
                                     (isset($frame['class']))  ? $frame['class'].$frame['type'].$frame['function'] : $frame['function'],
                                     $args );
            $count++;
        }
        return $output;
    }


}
