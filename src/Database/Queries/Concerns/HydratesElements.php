<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns;

use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Database\Queries\Events\ElementHydrated;
use CraftCms\Cms\Database\Queries\Events\ElementsHydrated;
use CraftCms\Cms\Database\Queries\Events\HydratingElement;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use stdClass;

/**
 * @mixin \CraftCms\Cms\Database\Queries\ElementQuery
 *
 * @internal
 */
trait HydratesElements
{
    /**
     * Create a collection of elements from plain arrays.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ElementInterface>
     */
    public function hydrate(array $items): Collection
    {
        $items = array_map(fn (stdClass $row) => (array) $row, $items);

        $elements = new Collection($items)
            ->when($this->searchResults, fn (Collection $collection) => $collection->map(function (array $row) {
                if (! isset($row['id'], $row['siteId'])) {
                    return $row;
                }

                $key = sprintf('%s-%s', $row['id'], $row['siteId']);

                if (isset($this->searchResults[$key])) {
                    $row['searchScore'] = (int) round($this->searchResults[$key]);
                }

                return $row;
            }))->map(fn (array $row) => $this->createElement($row));

        if ($this->withProvisionalDrafts) {
            ElementHelper::swapInProvisionalDrafts($elements);
        }

        if (Event::hasListeners(ElementsHydrated::class)) {
            Event::dispatch($event = new ElementsHydrated($elements, $items));

            return $event->elements;
        }

        return $elements;
    }

    protected function createElement(array $row): ElementInterface
    {
        // Do we have a placeholder for this element?
        if (
            ! $this->ignorePlaceholders &&
            isset($row['id'], $row['siteId']) &&
            ! is_null($element = \Craft::$app->getElements()->getPlaceholderElement($row['id'], $row['siteId']))
        ) {
            return $element;
        }

        /** @var class-string<ElementInterface> $class */
        $class = $this->elementType;

        // Instantiate the element
        if ($this->structureId) {
            $row['structureId'] = $this->structureId;
        }

        if ($class::hasTitles()) {
            // Ensure the title is a string
            $row['title'] = (string) ($row['title'] ?? '');
        }

        // Set the field values
        $content = Arr::pull($row, 'content');
        $row['fieldValues'] = [];

        if (! empty($content) && (! empty($this->customFields) || ! empty($this->generatedFields))) {
            if (is_string($content)) {
                $content = Json::decode($content);
            }

            foreach ($this->customFields as $field) {
                if (is_null($field::dbType())) {
                    continue;
                }

                if (! isset($content[$field->layoutElement->uid])) {
                    continue;
                }

                $handle = $field->layoutElement->handle ?? $field->handle;
                $row['fieldValues'][$handle] = $content[$field->layoutElement->uid];
            }

            foreach ($this->generatedFields as $field) {
                if (! isset($content[$field['uid']])) {
                    continue;
                }

                $row['generatedFieldValues'][$field['uid']] = $content[$field['uid']];

                if (! empty($field['handle'] ?? '')) {
                    $row['generatedFieldValues'][$field['handle']] = $content[$field['uid']];
                }
            }
        }

        if (array_key_exists('dateDeleted', $row)) {
            $row['trashed'] = $row['dateDeleted'] !== null;
        }

        if ($this->drafts !== false) {
            $row['isProvisionalDraft'] = (bool) ($row['isProvisionalDraft'] ?? false);

            if (! empty($row['draftId'])) {
                $row['draftCreatorId'] = Arr::pull($row, 'draftCreatorId');
                $row['draftName'] = Arr::pull($row, 'draftName');
                $row['draftNotes'] = Arr::pull($row, 'draftNotes');
            } else {
                unset(
                    $row['draftCreatorId'],
                    $row['draftName'],
                    $row['draftNotes']
                );
            }
        }

        if ($this->revisions !== false) {
            if (! empty($row['revisionId'])) {
                $row['revisionCreatorId'] = Arr::pull($row, 'revisionCreatorId');
                $row['revisionNum'] = Arr::pull($row, 'revisionNum');
                $row['revisionNotes'] = Arr::pull($row, 'revisionNotes');
            } else {
                unset(
                    $row['revisionCreatorId'],
                    $row['revisionNum'],
                    $row['revisionNotes'],
                );
            }
        }

        $element = null;

        if (Event::hasListeners(HydratingElement::class)) {
            Event::dispatch($event = new HydratingElement($row));

            $row = $event->row;

            if (isset($event->element)) {
                $element = $event->element;
            }
        }

        $element ??= new $class($row);

        if (Event::hasListeners(ElementHydrated::class)) {
            Event::dispatch($event = new ElementHydrated($element, $row));

            return $event->element;
        }

        return $element;
    }
}
