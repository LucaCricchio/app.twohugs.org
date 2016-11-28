<?php
namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected $errors = null;

    public function getErrors()
    {
        return $this->errors;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

}