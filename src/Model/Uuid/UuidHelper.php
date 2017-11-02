<?php

namespace Tokenly\LaravelApiProvider\Model\Uuid;


class UuidHelper {

    public static function toBinary($value) {
        return hex2bin(str_replace('-', '', $value));
    }

    public static function toString($value) {
        $hex = bin2hex($value);

        return 
            substr($hex, 0, 8).'-'.
            substr($hex, 8, 4).'-'.
            substr($hex, 12, 4).'-'.
            substr($hex, 16, 4).'-'.
            substr($hex, 20);
    }

}
