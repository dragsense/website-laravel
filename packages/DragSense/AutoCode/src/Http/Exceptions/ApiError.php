<?php

namespace DragSense\AutoCode\Http\Exceptions;

use Exception;
use Throwable;

class ApiError extends Exception
{
    public $statusCode;
    public $isOperational;

    public function __construct($message, $statusCode, $isOperational = true, Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->isOperational = $isOperational;

        // Call the parent constructor to set the message and status code
        parent::__construct($message, $statusCode, $previous);
    }

    // Optionally, you can override the render method to customize the response
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'status' => $this->statusCode,
            'isOperational' => $this->isOperational,
        ], $this->statusCode);
    }
}
