<?php

namespace craft\log;

use Illuminate\Support\Collection;
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
    public function __invoke(array $record): array
    {
        $record = Collection::make($record);
        $record = $this->_extractCategory($record);
        $record = $this->_filterEmpty($record, 'context.trace');

        return $record->all();
    }

    private function _extractCategory(Collection $record): Collection
    {
        $category = $record->pull('context.category');
        $extra = Collection::make($record['extra']);
        $extra->put('yii_category', $category ?? self::DEFAULT_CATEGORY);
        $record->put('extra', $extra->all());

        return $record;
    }

    private function _filterEmpty(Collection $record, string $key): Collection
    {
        if (empty($record->get($key))) {
            $record->pull($key);
        }

        return $record;
    }
}
