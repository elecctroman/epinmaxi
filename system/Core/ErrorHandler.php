<?php

namespace System\Core;

use Throwable;

class ErrorHandler
{
    private static string $logFile;

    public static function register(): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        self::$logFile = $logDir . '/error.log';

        ini_set('log_errors', '1');
        ini_set('error_log', self::$logFile);
        ini_set('display_errors', '0');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::write(sprintf('PHP Error [%s] %s in %s:%d', self::severityToString($severity), $message, $file, $line));

        return false;
    }

    public static function handleException(Throwable $throwable): void
    {
        self::write(sprintf('Uncaught Exception [%s] %s in %s:%d | Trace: %s',
            get_class($throwable),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        ));
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::write(sprintf('Fatal Error [%s] %s in %s:%d', self::severityToString($error['type']), $error['message'], $error['file'], $error['line']));
        }
    }

    private static function write(string $message): void
    {
        $context = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'url' => $_SERVER['REQUEST_URI'] ?? 'cli',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'cli',
        ];

        $entry = sprintf("[%s] %s | %s\n", date('c'), $message, json_encode($context, JSON_UNESCAPED_SLASHES));
        file_put_contents(self::$logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private static function severityToString(int $severity): string
    {
        return match ($severity) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => (string) $severity,
        };
    }
}
