<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Illuminate\Contracts\Log\ContextLogProcessor;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Adds the request body that {@see Middleware\AddLogContext} stored on the request to the first
 * warning or worse, or to the first record in debug mode, so it's logged at most once per request.
 *
 * @since 6.0.0
 */
readonly class RequestBodyLogProcessor implements ContextLogProcessor
{
    public const string REQUEST_ATTRIBUTE = 'craft.logBody';

    public function __construct(
        private ContextLogProcessor $processor,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $record = ($this->processor)($record);

        if (! app()->bound('request')) {
            return $record;
        }

        $attributes = app('request')->attributes;
        $body = $attributes->get(self::REQUEST_ATTRIBUTE);

        if ($body === null || ($record->level->isLowerThan(Level::Warning) && ! app()->hasDebugModeEnabled())) {
            return $record;
        }

        $attributes->remove(self::REQUEST_ATTRIBUTE);

        return $record->with(context: [...$record->context, 'body' => $body]);
    }
}
