<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\Batchable;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\console\controllers\ResaveController;
use craft\db\QueryBatcher;
use craft\errors\InvalidElementException;
use craft\helpers\ElementHelper;
use craft\i18n\Translation;
use craft\queue\BaseBatchedJob;
use Throwable;

/**
 * ResaveElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ResaveElements extends BaseBatchedJob
{
    /**
     * @var string The element type that should be resaved
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be resaved
     */
    public ?array $criteria = null;

    /**
     * @var bool Whether to update the search indexes for the resaved elements.
     * @since 3.4.2
     */
    public bool $updateSearchIndex = false;

    /**
     * @var string|null An attribute name that should be set for each of the elements. The value will be determined by [[to]].
     * @since 4.2.6
     */
    public ?string $set = null;

    /**
     * @var string|null The value that should be set on the [[set]] attribute.
     * @since 4.2.6
     */
    public ?string $to = null;

    /**
     * @var bool Whether the [[set]] attribute should only be set if it doesn’t have a value.
     * @since 4.2.6
     */
    public bool $ifEmpty = false;

    /**
     * @var bool Whether to update the `dateUpdated` timestamp for the elements.
     * @since 4.2.6
     */
    public bool $touch = false;

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType;
        $query = $elementType::find()
            ->orderBy(['elements.id' => SORT_ASC]);

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return new QueryBatcher($query);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        // Make sure the element was queried with its content
        /** @var ElementInterface $item */
        if ($item::hasContent() && $item->contentId === null) {
            throw new InvalidElementException($item, "Skipped resaving {$item->getUiLabel()} ($item->id) because it wasn’t loaded with its content.");
        }


        $item->setScenario(Element::SCENARIO_ESSENTIALS);
        $item->resaving = true;

        if (isset($this->set) && (!$this->ifEmpty || ElementHelper::isAttributeEmpty($item, $this->set))) {
            $to = ResaveController::normalizeTo($this->to);
            $item->{$this->set} = $to($item);
        }

        try {
            Craft::$app->getElements()->saveElement($item,
                updateSearchIndex: $this->updateSearchIndex,
                forceTouch: $this->touch,
            );
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType;
        return Translation::prep('app', 'Resaving {type}', [
            'type' => $elementType::pluralLowerDisplayName(),
        ]);
    }
}
