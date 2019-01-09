<?php
namespace Simple\Exception;

class NodeException extends \Exception
{
    public function __construct()
    {
        $this->message == 'Node not found';
    }
}

?>