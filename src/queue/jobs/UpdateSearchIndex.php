<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\i18n\Translation;
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
     * @var string The type of elements to update.
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var int|int[]|null The ID(s) of the element(s) to update
     */
    public array|int|null $elementId = null;

    /**
     * @var int|string|null The site ID of the elements to update, or `'*'` to update all sites
     */
    public string|int|null $siteId = '*';

    /**
     * @var string[]|null The field handles that should be indexed
     * @since 3.4.0
     */
    public ?array $fieldHandles = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        /** @var string|ElementInterface $class */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $class */
        $class = $this->elementType;
        $elements = $class::find()
            ->drafts(null)
            ->provisionalDrafts(null)
            ->id($this->elementId)
            ->siteId($this->siteId)
            ->status(null)
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
    protected function defaultDescription(): ?string
    {
        return Translation::prep('app', 'Updating search indexes');
    }
}
