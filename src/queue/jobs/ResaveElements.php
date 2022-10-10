<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\console\controllers\ResaveController;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\BatchElementActionEvent;
use craft\helpers\ElementHelper;
use craft\i18n\Translation;
use craft\queue\BaseJob;
use craft\services\Elements;

/**
 * ResaveElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ResaveElements extends BaseJob
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
    public function execute($queue): void
    {
        /** @var ElementQuery $query */
        $query = $this->_query();
        $total = $query->count();
        if ($query->limit) {
            $total = min($total, $query->limit);
        }
        $elementsService = Craft::$app->getElements();

        $to = isset($this->set) ? ResaveController::normalizeTo($this->to) : null;
        $callback = function(BatchElementActionEvent $e) use ($queue, $query, $total, $to) {
            if ($e->query === $query) {
                $this->setProgress($queue, ($e->position - 1) / $total, Translation::prep('app', '{step, number} of {total, number}', [
                    'step' => $e->position,
                    'total' => $total,
                ]));

                $element = $e->element;

                if (isset($this->set) && (!$this->ifEmpty || ElementHelper::isAttributeEmpty($element, $this->set))) {
                    $element->{$this->set} = $to($element);
                }
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $callback);
        $elementsService->resaveElements($query, false, true, $this->updateSearchIndex, $this->touch);
        $elementsService->off(Elements::EVENT_BEFORE_RESAVE_ELEMENT, $callback);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        /** @var ElementQuery $query */
        $query = $this->_query();
        /** @var ElementInterface $elementType */
        $elementType = $query->elementType;
        return Translation::prep('app', 'Resaving {type}', [
            'type' => $elementType::pluralLowerDisplayName(),
        ]);
    }

    /**
     * Returns the element query based on the criteria.
     *
     * @return ElementQueryInterface
     */
    private function _query(): ElementQueryInterface
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $elementType = $this->elementType;
        $query = $elementType::find();

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        return $query;
    }
}
