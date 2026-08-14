<?php
// app/Exceptions/ApiException.php
namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public string $errorCode;
    public int $status;
    public mixed $details;

    public function __construct(
        string $errorCode,
        string $message,
        int $status = 422,
        mixed $details = null,
    ) {
        $this->errorCode = $errorCode;
        $this->status = $status;
        $this->details = $details;

        parent::__construct($message, $status);
    }
}