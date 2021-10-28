<?php

namespace App\Helpers;

/**
 * Crate logs
 */
class Logger
{
    protected string $logDir;
    protected string $logFile;
    protected string $message;

    public function __construct(string $logDir, string $logFile)
    {
        $this->logDir = $logDir;
        $this->logFile = $logFile;
        $this->message = '';

        $this->createFolder();
    }

    /**
     * Create new log folder id current id nor exists.
     */
    private function createFolder(): void
    {
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function setMessage(string $message): self
    {
        $this->message .= $message;

        return $this;
    }

    /**
     * Write log message it log file
     * @param string $type
     */
    public function log(mixed $type = null, string $delimiter = '-'): void
    {
        file_put_contents(
            $this->logFile,
            "\n" . date('Y-m-d H:m:i') . "\n" . ($type ? strtoupper($type) . ': ' : '') . $this->message . "\n" . str_repeat($delimiter, 50),
            FILE_APPEND
        );
    }
}