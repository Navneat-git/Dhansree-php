<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;

class ApiException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;
    protected ?array $details;
    protected ?string $requestId;

    public function __construct(
        string $code,
        string $message,
        int $status = 400,
        ?array $details = null,
        ?string $requestId = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $status, $previous);
        $this->errorCode = $code;
        $this->statusCode = $status;
        $this->details = $details;
        $this->requestId = $requestId;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            code: $this->errorCode,
            message: $this->getMessage(),
            status: $this->statusCode,
            details: $this->details,
            requestId: $this->requestId
        );
    }
}
