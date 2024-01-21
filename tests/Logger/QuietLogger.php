<?php

namespace SilverStripe\SearchElastic\Tests\Logger;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * A Logger that does nothing
 *
 * Used to silence logging in tests that log messages (otherwise we get a tonne of useless noise in the test report)
 */
class QuietLogger implements LoggerInterface
{

    public function emergency(Stringable|string $message, array $context = []): void
    {
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
    }

    public function error(Stringable|string $message, array $context = []): void
    {
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
    }

    public function info(Stringable|string $message, array $context = []): void
    {
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
    }

}
