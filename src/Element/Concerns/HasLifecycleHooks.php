<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Element\ElementRelations;
use CraftCms\Cms\Element\Events\AfterDelete;
use CraftCms\Cms\Element\Events\AfterPropagate;
use CraftCms\Cms\Element\Events\AfterRestore;
use CraftCms\Cms\Element\Events\AfterSave;
use CraftCms\Cms\Element\Events\BeforeDelete;
use CraftCms\Cms\Element\Events\BeforeRestore;
use CraftCms\Cms\Element\Events\BeforeSave;

/**
 * HasLifecycleHooks provides the lifecycle hooks for the element.
 *
 * This trait contains methods for handling element events such as saving, deleting, and restoring.
 *
 * @internal
 */
trait HasLifecycleHooks
{
    /**
     * {@inheritdoc}
     *
     * @see Element::beforeSave()
     */
    public function beforeSave(bool $isNew): bool
    {
        // Tell the fields about it
        if (! array_all($this->fieldLayoutFields(), fn ($field) => $field->beforeElementSave($this, $isNew))) {
            return false;
        }

        event($event = new BeforeSave($this, $isNew));

        return $event->isValid;
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::afterSave()
     */
    public function afterSave(bool $isNew): void
    {
        // Update the element's relation data
        app(ElementRelations::class)->updateRelations($this, $isNew);

        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        event(new AfterSave($this, $isNew));
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::afterPropagate()
     */
    public function afterPropagate(bool $isNew): void
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementPropagate($this, $isNew);
        }

        event(new AfterPropagate($this, $isNew));

        $this->handleDraftSave();
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::beforeDelete()
     */
    public function beforeDelete(): bool
    {
        // Tell the fields about it
        if (! array_all($this->fieldLayoutFields(), fn ($field) => $field->beforeElementDelete($this))) {
            return false;
        }

        event($event = new BeforeDelete($this));

        return $event->isValid;
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::afterDelete()
     */
    public function afterDelete(): void
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementDelete($this);
        }

        event(new AfterDelete($this));

        $this->handleRevisionDelete();
        $this->handleDraftDelete();
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::beforeDeleteForSite()
     */
    public function beforeDeleteForSite(): bool
    {
        return array_all($this->fieldLayoutFields(), fn ($field) => $field->beforeElementDeleteForSite($this));
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::afterDeleteForSite()
     */
    public function afterDeleteForSite(): void
    {
        // Delete any site-specific relation data
        app(ElementRelations::class)->deleteSiteRelations($this);

        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementDeleteForSite($this);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::beforeRestore()
     */
    public function beforeRestore(): bool
    {
        // Tell the fields about it
        if (! array_all($this->fieldLayoutFields(), fn ($field) => $field->beforeElementRestore($this))) {
            return false;
        }

        event($event = new BeforeRestore($this));

        return $event->isValid;
    }

    /**
     * {@inheritdoc}
     *
     * @see Element::afterRestore()
     */
    public function afterRestore(): void
    {
        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementRestore($this);
        }

        event(new AfterRestore($this));
    }
}
