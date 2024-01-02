<?php

namespace Ymigval\LaravelModelToDatatables\Exceptions;

use Exception;

class DataTablesColumnDefErrorException extends Exception
{
    /**
     * @var string
     */
    protected $message = 'Column definition error in row #%d.';

    /**
     * DataTablesColumnDefErrorException constructor.
     *
     * @param  int  $columnRowNumber The row number where the column definition error occurred.
     */
    public function __construct(int $columnRowNumber)
    {
        $this->message = sprintf($this->message, $columnRowNumber);
    }
}
