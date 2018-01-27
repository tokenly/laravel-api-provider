<?php

namespace Tokenly\LaravelApiProvider\Repositories\Concerns;

/**
 * Broadcasts create, update and delete events
 */
trait BroadcastsRepositoryEvents
{
 
    protected function broadcastRepositoryEvent($event) {
        event($event);
    }

}