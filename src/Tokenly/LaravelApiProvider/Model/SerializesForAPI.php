<?php

namespace Tokenly\LaravelApiProvider\Model;

use Carbon\Carbon;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

trait SerializesForAPI {

    private function __serializeForAPI($attribute_names, $array) {
        $out = [];
        foreach($attribute_names as $attribute_name) {
            if ($attribute_name == 'id' AND isset($array['uuid'])) {
                // id always uses the uuid
                $out['id'] = $array['uuid'];
            } else {
                $value = $array[$attribute_name];
                if ($value instanceof Carbon) {
                    $value = $value->toIso8601String();
                } else if ($value instanceof APISerializeable) {
                    $value = $value->serializeForAPI();
                } else if (is_array($value)) {
                    // if this is an array of objects, then recurse
                    $keys = array_keys($value);
                    if ($keys AND is_object($value[$keys[0]])) {
                        $value = $this->__serializeForAPI($keys, $value);
                    }
                }

                $out[camel_case($attribute_name)] = $value;
            }
        }
        return $out;
    }

    public function serializeForAPI() {
        return $this->__serializeForAPI($this->api_attributes, $this);
    }

}
