<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationException extends HttpException
{
    public function __construct(string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct(Response::HTTP_UNPROCESSABLE_ENTITY, $message, $previous, $headers, $code);
    }
}
