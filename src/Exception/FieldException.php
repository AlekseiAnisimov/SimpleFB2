<?php
namespace Simple\Exception;

class FieldException extends \Exception
{
    public function __construct($field)
    {
        $this->message = "Field '$field' not found";
    }
}

?>