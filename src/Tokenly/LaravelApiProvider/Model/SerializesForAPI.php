<?php

namespace Tokenly\LaravelApiProvider\Model;

use Carbon\Carbon;

trait SerializesForAPI {

    public function serializeForAPI() {
        $out = $this->attributesToArray();

        $out = [];
        $api_attributes = isset($this->api_attributes) ? $this->api_attributes : ['id'];
        foreach($this->api_attributes as $api_attribute) {
            if ($api_attribute == 'id' AND isset($this['uuid'])) {
                $out['id'] = $this['uuid'];
            } else {
                $value = $this[$api_attribute];
                if ($value instanceof Carbon) {
                    $value = $value->toIso8601String();
                }

                $out[camel_case($api_attribute)] = $value;
            }
        }

        return $out;
    }

}
