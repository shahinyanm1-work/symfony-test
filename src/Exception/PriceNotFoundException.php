<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PriceNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Price not found', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
