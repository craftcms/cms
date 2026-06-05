<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Listeners;

use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\Events\FieldDeleted;
use CraftCms\Cms\Field\Events\FieldElementDeletedForSite;
use CraftCms\Cms\Field\Events\FieldElementSaved;
use CraftCms\Cms\Field\Events\FieldLayoutDeleted;
use CraftCms\Cms\Field\Events\FieldLayoutSaved;
use CraftCms\Cms\Field\Events\FieldSaveApplying;
use CraftCms\Cms\Field\FieldReferences;

/**
 * Updates reference rows when fields are saved, clears rows
 * when sources or field/layout instances are removed, and
 * drops stale rows when a field stops reference tracking.
 */
readonly class FieldReferenceEventSubscriber
{
    public function __construct(
        private FieldReferences $fieldReferences,
    ) {}

    public function handleFieldElementSaved(FieldElementSaved $event): void
    {
        if (! $event->field instanceof TracksReferencesFieldInterface) {
            return;
        }

        if (! $this->shouldUpdateTrackedReferences($event)) {
            return;
        }

        $this->fieldReferences->updateReferences($event->field, $event->element);
    }

    public function handleFieldElementDeletedForSite(FieldElementDeletedForSite $event): void
    {
        if (! $event->field instanceof TracksReferencesFieldInterface) {
            return;
        }

        $this->fieldReferences->deleteReferencesForSourceFieldSite($event->field, $event->element);
    }

    public function handleFieldSaveApplying(FieldSaveApplying $event): void
    {
        if (! $event->field instanceof TracksReferencesFieldInterface) {
            return;
        }

        $newType = $event->config['type'] ?? null;

        if (is_string($newType) && is_a($newType, TracksReferencesFieldInterface::class, true)) {
            return;
        }

        $this->fieldReferences->deleteReferencesForField($event->field);
    }

    public function handleFieldDeleted(FieldDeleted $event): void
    {
        if (! $event->field instanceof TracksReferencesFieldInterface) {
            return;
        }

        $this->fieldReferences->deleteReferencesForField($event->field);
    }

    public function handleFieldLayoutSaved(FieldLayoutSaved $event): void
    {
        $this->fieldReferences->deleteReferencesForRemovedInstances(
            $event->previousConfig,
            $event->layout->getConfig(),
        );
    }

    public function handleFieldLayoutDeleted(FieldLayoutDeleted $event): void
    {
        $this->fieldReferences->deleteReferencesForRemovedInstances(
            $event->layout->getConfig(),
            null,
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            FieldElementSaved::class => 'handleFieldElementSaved',
            FieldElementDeletedForSite::class => 'handleFieldElementDeletedForSite',
            FieldSaveApplying::class => 'handleFieldSaveApplying',
            FieldDeleted::class => 'handleFieldDeleted',
            FieldLayoutSaved::class => 'handleFieldLayoutSaved',
            FieldLayoutDeleted::class => 'handleFieldLayoutDeleted',
        ];
    }

    private function shouldUpdateTrackedReferences(FieldElementSaved $event): bool
    {
        if ($event->element->getIsRevision()) {
            return false;
        }

        return $event->isNew ||
            $event->element->isNewForSite ||
            $event->element->duplicateOf !== null ||
            $event->element->isFieldDirty($event->field->handle);
    }
}
