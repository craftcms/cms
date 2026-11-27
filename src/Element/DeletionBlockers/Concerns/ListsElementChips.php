<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\DeletionBlockers\Concerns;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * Lists elements as chips in a deletion blocker's details.
 *
 * @since 6.0.0
 */
trait ListsElementChips
{
    /** How many elements {@see elementChipsHtml()} lists before summarizing the rest. */
    protected const int DETAILS_LIMIT = 20;

    /**
     * Lists the elements a query finds as chips, linked to their edit pages,
     * for a blocker's details. Past {@see DETAILS_LIMIT} it says how many more
     * there are rather than listing them all.
     */
    protected function elementChipsHtml(ElementQueryInterface $query): ?string
    {
        $total = (clone $query)->count();

        if ($total === 0) {
            return null;
        }

        $elementHtml = app(ElementHtml::class);
        $items = (clone $query)
            ->limit(self::DETAILS_LIMIT)
            ->get()
            ->map(fn (ElementInterface $element) => $this->chipElement($element))
            ->unique(fn (ElementInterface $element) => $element->id)
            ->map(fn (ElementInterface $element) => Html::tag('li', $elementHtml->chipHtml($element, [
                'hyperlink' => true,
            ])))
            ->join('');

        $html = Html::tag('ul', $items, ['class' => 'edm-elements']);

        if ($total > self::DETAILS_LIMIT) {
            $html .= Html::tag('p', t('and {count} more', [
                'count' => $total - self::DETAILS_LIMIT,
            ]), ['class' => 'edm-elements-more']);
        }

        return $html;
    }

    /**
     * A nested element with no label of its own, such as an untitled Matrix
     * entry, is shown as the element it belongs to, which says where it lives.
     */
    private function chipElement(ElementInterface $element): ElementInterface
    {
        if (
            $element instanceof NestedElementInterface &&
            $element->getUiLabel() === '' &&
            ($owner = $element->getOwner()) !== null
        ) {
            return $owner;
        }

        return $element;
    }
}
