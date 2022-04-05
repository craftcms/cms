<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue\jobs;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\events\BatchElementActionEvent;
use craft\i18n\Translation;
use craft\queue\BaseJob;
use craft\services\Elements;

/**
 * PropagateElements job
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.13
 */
class PropagateElements extends BaseJob
{
    /**
     * @var string The element type that should be propagated
     * @phpstan-var class-string<ElementInterface>
     */
    public string $elementType;

    /**
     * @var array|null The element criteria that determines which elements should be propagated
     */
    public ?array $criteria = null;

    /**
     * @var int|int[]|null The site ID(s) that the elements should be propagated to
     *
     * If this is `null`, then elements will be propagated to all supported sites, except the one they were queried in.
     */
    public array|int|null $siteId = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        /** @var ElementQuery $query */
        $query = $this->_query();
        $total = $query->count();
        $elementsService = Craft::$app->getElements();

        $callback = function(BatchElementActionEvent $e) use ($queue, $query, $total) {
            if ($e->query === $query) {
                $this->setProgress($queue, ($e->position - 1) / $total, Translation::prep('app', '{step, number} of {total, number}', [
                    'step' => $e->position,
                    'total' => $total,
                ]));
            }
        };

        $elementsService->on(Elements::EVENT_BEFORE_PROPAGATE_ELEMENT, $callback);
        $elementsService->propagateElements($query, $this->siteId);
        $elementsService->off(Elements::EVENT_BEFORE_PROPAGATE_ELEMENT, $callback);
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
        $total = $query->count();
        return Translation::prep('app', 'Propagating {type}', [
            'type' => $total == 1 ? $elementType::lowerDisplayName() : $elementType::pluralLowerDisplayName(),
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

        $query
            ->offset(null)
            ->limit(null)
            ->orderBy([]);

        return $query;
    }
}
