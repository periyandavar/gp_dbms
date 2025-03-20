<?php

class DatabaseException extends Exception
{
    protected array $errorData = [];
    public const UNKNOWN_ERROR = 100;
    public const DATABASE_CONNECTION_ERROR = 101;
    public const DATABASE_QUERY_ERROR = 102;
    public const UNKNOWN_METHOD_CALL_ERROR = 103;
    public const DRIVER_NOT_FOUND_ERROR = 104;
    public function __construct($message = '', $code = 0, ?Throwable $previous = null, $errorData = [])
    {
        $code = $code ?: self::UNKNOWN_ERROR;
        parent::__construct($message, $code, $previous);
        $this->errorData = $errorData;
    }

    /**
     * Get the value of errorData
     *
     * @return array
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Set the value of errorData
     *
     * @param array $errorData
     *
     * @return self
     */
    public function setErrorData(array $errorData): self
    {
        $this->errorData = $errorData;

        return $this;
    }
}
