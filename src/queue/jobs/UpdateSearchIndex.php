<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\queue\BaseJob;

/**
 * UpdateSearchIndex job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class UpdateSearchIndex extends BaseJob
{
    /**
     * @var string|ElementInterface|null The type of elements to update.
     */
    public $elementType;

    /**
     * @var int|int[]|null The ID(s) of the element(s) to update
     */
    public $elementId;

    /**
     * @var int|string|null The site ID of the elements to update, or `'*'` to update all sites
     */
    public $siteId = '*';

    /**
     * @var string[]|null The field handles that should be indexed
     * @since 3.4.0
     */
    public $fieldHandles;

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $class = $this->elementType;
        $elements = $class::find()
            ->id($this->elementId)
            ->siteId($this->siteId)
            ->anyStatus()
            ->all();
        $total = count($elements);
        $searchService = Craft::$app->getSearch();

        foreach ($elements as $i => $element) {
            $this->setProgress($queue, ($i + 1) / $total);
            $searchService->indexElementAttributes($element, $this->fieldHandles);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Updating search indexes');
    }
}
