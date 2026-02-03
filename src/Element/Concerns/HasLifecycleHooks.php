<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\events\ModelEvent;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementRelations;
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
     * @event ModelEvent The event that is triggered after the element is saved.
     *
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
     * use craft\events\ModelEvent;
     * use craft\helpers\ElementHelper;
     * use yii\base\Event;
     *
     * Event::on(Entry::class, Element::EVENT_AFTER_SAVE, function(ModelEvent $e) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     if (ElementHelper::isDraftOrRevision($entry)) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     */
    public const EVENT_AFTER_SAVE = 'afterSave';

    /**
     * @event ModelEvent The event that is triggered after the element is fully saved and propagated to other sites.
     *
     * If you want to ignore events for drafts or revisions, call [[\craft\helpers\ElementHelper::isDraftOrRevision()]]
     * from your event handler:
     *
     * ```php
     * use CraftCms\Cms\Element\Element;
     * use CraftCms\Cms\Entry\Elements\Entry;
     * use craft\events\ModelEvent;
     * use craft\helpers\ElementHelper;
     * use yii\base\Event;
     *
     * Event::on(Entry::class, Element::EVENT_AFTER_PROPAGATE, function(ModelEvent $e) {
     *     // @var Entry $entry
     *     $entry = $e->sender;
     *
     *     if (ElementHelper::isDraftOrRevision($entry) {
     *         return;
     *     }
     *
     *     // ...
     * });
     * ```
     *
     * @since 3.2.0
     */
    public const EVENT_AFTER_PROPAGATE = 'afterPropagate';

    /**
     * @event ModelEvent The event that is triggered before the element is deleted.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting deleted.
     */
    public const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * @event \yii\base\Event The event that is triggered after the element is deleted.
     */
    public const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * @event ModelEvent The event that is triggered before the element is restored.
     *
     * You may set [[\yii\base\ModelEvent::$isValid]] to `false` to prevent the element from getting restored.
     *
     * @since 3.1.0
     */
    public const EVENT_BEFORE_RESTORE = 'beforeRestore';

    /**
     * @event \yii\base\Event The event that is triggered after the element is restored.
     *
     * @since 3.1.0
     */
    public const EVENT_AFTER_RESTORE = 'afterRestore';

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
        // Update the element’s relation data
        app(ElementRelations::class)->updateRelations($this, $isNew);

        // Tell the fields about it
        foreach ($this->fieldLayoutFields() as $field) {
            $field->afterElementSave($this, $isNew);
        }

        // Fire an 'afterSave' event
        if ($this->hasEventHandlers(Element::EVENT_AFTER_SAVE)) {
            $this->trigger(Element::EVENT_AFTER_SAVE, new ModelEvent([
                'isNew' => $isNew,
            ]));
        }
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

        // Fire an 'afterPropagate' event
        if ($this->hasEventHandlers(Element::EVENT_AFTER_PROPAGATE)) {
            $this->trigger(Element::EVENT_AFTER_PROPAGATE, new ModelEvent([
                'isNew' => $isNew,
            ]));
        }

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

        // Fire a 'beforeDelete' event
        if ($this->hasEventHandlers(Element::EVENT_BEFORE_DELETE)) {
            $event = new ModelEvent;
            $this->trigger(Element::EVENT_BEFORE_DELETE, $event);

            return $event->isValid;
        }

        return true;
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

        // Fire an 'afterDelete' event
        if ($this->hasEventHandlers(Element::EVENT_AFTER_DELETE)) {
            $this->trigger(Element::EVENT_AFTER_DELETE);
        }

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

        // Fire a 'beforeRestore' event
        if ($this->hasEventHandlers(Element::EVENT_BEFORE_RESTORE)) {
            $event = new ModelEvent;
            $this->trigger(Element::EVENT_BEFORE_RESTORE, $event);

            return $event->isValid;
        }

        return true;
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

        // Fire an 'afterRestore' event
        if ($this->hasEventHandlers(Element::EVENT_AFTER_RESTORE)) {
            $this->trigger(Element::EVENT_AFTER_RESTORE);
        }
    }
}
