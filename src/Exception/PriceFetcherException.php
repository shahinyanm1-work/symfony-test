<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PriceFetcherException extends ServiceUnavailableHttpException
{
    public function __construct(string $message = 'Price fetcher service unavailable', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(null, $message, $previous, $code, $headers);
    }
}
