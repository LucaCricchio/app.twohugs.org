<?php

namespace App\Exceptions;


use App\Helpers\ErrorCode;
use Exception;

class ExceptionWithCustomCode extends \Exception
{
    protected $errorCode;

    public function __construct($message = "", $errorCode = ErrorCode::UNKNOWN, $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }


    public function getErrorCode()
    {
        return $this->errorCode;
    }
}