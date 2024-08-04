<?php

declare(strict_types=1);

namespace tTorMt\SChat\Logger;

use Psr\Log\AbstractLogger;

/**
 * Default Logger uses error_log function
 */
class DefaultLogger extends AbstractLogger
{

    /**
     * @param $level
     * @param string|\Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $date = '['.date("Y-m-d H:i:s").']';
        error_log($date.$message);
    }
}
