<?php

namespace Ymigval\LaravelModelToDatatables\Exceptions;

use Exception;

class DataTablesNoColumnDefException extends Exception
{
    /**
     * @var string
     */
    protected $message = "No column definition found.";

    /**
     * DataTablesNoColumnDefException constructor.
     */
    public function __construct()
    {
        
    }
}