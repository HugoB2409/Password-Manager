<?php namespace Models;

class TableObject
{
    public $headers;
    public $data;

    function __construct($headers, $data) {
        $this->headers = $headers;
        $this->data = $data;
    }

    public static function newTableObject($data, string ...$headers){
        return new TableObject($headers, $data);
    }
}