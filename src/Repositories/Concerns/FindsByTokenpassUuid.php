<?php

namespace Tokenly\LaravelApiProvider\Repositories\Concerns;

/**
 * find by tokenly_uuid field
 */
trait FindsByTokenpassUuid
{
 
    public function findByTokenlyUuid($tokenly_uuid) {
        return $this->prototype_model->where('tokenly_uuid', $tokenly_uuid)->first();
    }


}