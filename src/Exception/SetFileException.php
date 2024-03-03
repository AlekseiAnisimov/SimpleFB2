<?php

namespace SimpleFB2\Exception;

class SetFileException extends \Exception {
    public function __construct() {
        $this->message = "Incorrect file path";
    }
}