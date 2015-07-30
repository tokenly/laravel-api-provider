<?php

namespace Tokenly\LaravelApiProvider\Filter;

use Exception;
use Illuminate\Http\Request;

/*
* Transformers
*/
class Transformers
{

    public static function toBooleanInteger($v) {
        $fl = substr(strtolower(trim($v)), 0, 1); 
        return ($fl == 'y' OR $fl == 'n' OR $fl == '1') ? 1 : 0;
    }

    public static function LCTrimmed($v) {
        return strtolower(trim($v));
    }

}

