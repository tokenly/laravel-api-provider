<?php

namespace Tokenly\LaravelApiProvider\Helpers;

use Exception;

class ResponseException extends Exception {

    protected $errors;

    function __construct($message="", $code=0, $previous=null) {
        $this->setErrors(is_array($message) ? $message : [$message]);
        parent::__construct(is_array($message) ? implode(" ", $message) : $message, $code, $previous);
    }

    public function setErrors($errors) {
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }

}

