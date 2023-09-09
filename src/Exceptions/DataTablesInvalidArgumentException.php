<?php

namespace Ymigval\LaravelModelToDatatables\Exceptions;

use InvalidArgumentException;

class DataTablesInvalidArgumentException extends InvalidArgumentException
{
    /**
     * @var string
     */
    protected $message = "The input type for column definition is invalid. Expected an array or callback.";

    /**
     * DataTablesInvalidArgumentException constructor.
     */
    public function __construct()
    {

    }
}