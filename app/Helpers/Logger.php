<?php
// /app/Helpers/Logger.php

namespace App\Helpers;

final class Logger
{
    private static ?Logger $instance = null;
    private string $file;
    private string $level;
    private string $env;
    private string $requestId;

    /** @var array<string,int> */
    private const LEVELS = [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ];

    private function __construct(string $file, string $level, string $env, string $requestId)
    {
        $this->file      = $file;
        $this->level     = strtolower($level);
        $this->env       = $env;
        $this->requestId = $requestId;
    }

    /**
     * Initialize the singleton. Call once in bootstrap.
     */
    public static function init(
        ?string $file = null,
        string $level = 'debug',
        string $env = 'dev',
        ?string $requestId = null
    ): void {
        if (self::$instance !== null) return;

        $file = $file ?: (ini_get('error_log') ?: (dirname(__DIR__, 2) . '/storage/logs/dev_error.log'));
        $requestId = $requestId ?: bin2hex(random_bytes(8));
        self::$instance = new self($file, $level, $env, $requestId);
    }

    /**
     * Access the singleton.
     */
    public static function get(): Logger
    {
        if (self::$instance === null) {
            // sensible defaults if init() wasn’t called yet
            self::init();
        }
        return self::$instance;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if (!isset(self::LEVELS[$level])) {
            $level = 'debug';
        }
        if (self::LEVELS[$level] < self::LEVELS[$this->level]) {
            return; // below threshold
        }

        $line = $this->formatLine($level, $message, $context);

        // Ensure directory exists
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $fh = @fopen($this->file, 'ab');
        if ($fh === false) {
            // As a fallback, emit to PHP’s error_log handler
            error_log($line);
            return;
        }
        // Exclusive lock to avoid interleaving
        if (flock($fh, LOCK_EX)) {
            fwrite($fh, $line);
            fflush($fh);
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    }

    // Convenience shorthands
    public function emergency(string $m, array $c = []): void { $this->log('emergency', $m, $c); }
    public function alert(string $m, array $c = []): void     { $this->log('alert', $m, $c); }
    public function critical(string $m, array $c = []): void  { $this->log('critical', $m, $c); }
    public function error(string $m, array $c = []): void     { $this->log('error', $m, $c); }
    public function warning(string $m, array $c = []): void   { $this->log('warning', $m, $c); }
    public function notice(string $m, array $c = []): void    { $this->log('notice', $m, $c); }
    public function info(string $m, array $c = []): void      { $this->log('info', $m, $c); }
    public function debug(string $m, array $c = []): void     { $this->log('debug', $m, $c); }

    // ---- internals ----

    private function formatLine(string $level, string $message, array $context): string
    {
        $ts = (new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get() ?: 'UTC')))
            ->format('Y-m-d\TH:i:sP');

        $interpolated = $this->interpolate($message, $context);

        // Keep context separately as JSON (safer than smashing into the message)
        $contextOut = '';
        if (!empty($context)) {
            // Avoid huge dumps; encode scalars/arrays/objects sanely
            $contextOut = ' | ' . json_encode($this->sanitizeContext($context), JSON_UNESCAPED_SLASHES);
        }

        return sprintf(
            "[%s] %s.%s [req:%s] %s%s%s",
            $ts,
            $this->env,
            strtoupper($level),
            $this->requestId,
            $interpolated,
            $contextOut,
            PHP_EOL
        );
    }

    private function interpolate(string $message, array $context): string
    {
        // PSR-3 style: replace {key} with scalar/context string
        $replacements = [];
        foreach ($context as $key => $value) {
            if (is_null($value) || is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements['{' . $key . '}'] = (string)$value;
            } else {
                $replacements['{' . $key . '}'] = json_encode($this->sanitizeContext($value), JSON_UNESCAPED_SLASHES);
            }
        }
        return strtr($message, $replacements);
    }

    private function sanitizeContext(mixed $value): mixed
    {
        // Redact common secrets; extend as needed
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                if (in_array(strtolower((string)$k), ['password','pass','db_pass','secret','token','authorization'], true)) {
                    $out[$k] = '***REDACTED***';
                } else {
                    $out[$k] = $this->sanitizeContext($v);
                }
            }
            return $out;
        }
        if (is_object($value)) {
            // Convert simple DTOs/arrays; avoid dumping entire models
            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            }
            // Fallback: safest string cast
            return method_exists($value, '__toString') ? (string)$value : get_class($value);
        }
        return $value;
    }

    public function exception(\Throwable $e, string $level = 'error'): void
    {
        $this->log($level, $e->getMessage(), [
            'exception' => get_class($e),
            'code'      => $e->getCode(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);
    }
}

/**
 * Global helper for convenience: logger()->info(...), etc.
 */
function logger(): Logger
{
    return Logger::get();
}

