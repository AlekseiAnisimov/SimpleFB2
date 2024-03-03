<?php

namespace SimpleFB2\Exception;

class FileExistException extends \Exception {
    public function __construct() {
        $this->message = "File not found";
    }
}