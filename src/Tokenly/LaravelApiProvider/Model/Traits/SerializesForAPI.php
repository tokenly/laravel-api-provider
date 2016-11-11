<?php

namespace Tokenly\LaravelApiProvider\Model\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Tokenly\LaravelApiProvider\Contracts\APISerializeable;

trait SerializesForAPI {

    private function __serializeForAPI($attribute_names, $array) {
        $out = [];
        foreach($attribute_names as $attribute_name) {
            if ($attribute_name == 'id' AND isset($array['uuid'])) {
                // id always uses the uuid
                $out['id'] = $array['uuid'];
            } else {
                // don't try to serialize values that don't exist
                if ($array instanceof Model AND !$array->offsetExists($attribute_name)) {
                    continue;
                } else if (!isset($array[$attribute_name])) {
                    $out[camel_case($attribute_name)] = null;
                    continue;
                }

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

    public function serializeForAPI($context=null) {
        if ($context === null) {
            $api_attributes = $this->api_attributes;
        } else {
            $var_name = 'api_attributes_'.$context;
            $api_attributes = $this->{$var_name};
        }

        return $this->__serializeForAPI($api_attributes, $this);
    }

}
