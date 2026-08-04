<?php

namespace craft\log;

use Illuminate\Support\Collection;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Class MessageProcessor
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class MessageProcessor implements ProcessorInterface
{
    public const DEFAULT_CATEGORY = 'application';

    /**
     * @inheritdoc
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record = Collection::make($record);
        $record = $this->_extractCategory($record);
        $record = $this->_filterEmptyContext($record, 'trace');

        return new LogRecord(
            datetime: $record->get('datetime'),
            channel: $record->get('channel'),
            level: $record->get('level'),
            message: $record->get('message'),
            context: $record->get('context'),
            extra: $record->get('extra'),
            formatted: $record->get('formatted'),
        );
    }

    private function _extractCategory(Collection $record): Collection
    {
        $category = $record->pull('context.category');
        $extra = Collection::make($record->get('extra'));
        $extra->put('yii_category', $category ?? self::DEFAULT_CATEGORY);
        $record->put('extra', $extra->all());

        return $record;
    }

    private function _filterEmptyContext(Collection $record, string $key = null): Collection
    {
        $context = Collection::make($record->get('context'))
            ->reject(fn($v, $k) => ($key === null || $k === $key) && empty($v));

        $record->put('context', $context->all());

        return $record;
    }
}
