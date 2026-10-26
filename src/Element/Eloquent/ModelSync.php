<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;

/**
 * Keeps {@see EloquentElement}s in step with writes the app makes to their models, without the models opting in.
 *
 * Saving a model creates or saves its element, following a change to its key. Soft-deleting or restoring it
 * trashes or restores the element, and deleting it for good hard-deletes the element.
 *
 * @since 6.0.0
 */
#[Singleton]
class ModelSync
{
    /** @var array<class-string<Model>, class-string<EloquentElement<covariant Model>>> */
    private array $observed = [];

    /** @param class-string<EloquentElement<covariant Model>> $elementType */
    public function observe(string $elementType): void
    {
        $modelClass = $elementType::modelClass();

        if (isset($this->observed[$modelClass])) {
            if ($this->observed[$modelClass] !== $elementType) {
                throw new InvalidArgumentException("$modelClass is already represented by {$this->observed[$modelClass]}.");
            }

            return;
        }

        if ($elementType::trashBehavior() === ModelTrashBehavior::SoftDelete && ! $elementType::modelSoftDeletes()) {
            throw new InvalidArgumentException("$elementType soft-deletes its model on trash, but $modelClass doesn’t use SoftDeletes.");
        }

        $this->observed[$modelClass] = $elementType;

        // `saved` follows creates, updates, and touches from related models, but not an instance's increment() or decrement()
        Event::listen("eloquent.saved: $modelClass", function (Model $model) use ($elementType): void {
            if (EloquentElement::isSyncing($model)) {
                return;
            }

            if ($model->wasChanged($model->getKeyName())) {
                app(ModelLinks::class)->rekey($model, $model->getOriginal($model->getKeyName()));
            }

            EloquentElement::syncing($model, fn () => Elements::saveElement($elementType::fromModel($model), runValidation: false));
        });

        Event::listen("eloquent.deleted: $modelClass", function (Model $model) use ($elementType): void {
            if (EloquentElement::isSyncing($model) || ! $element = $this->elementFor($elementType, $model)) {
                return;
            }

            $softDeleted = method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting();

            if (! $softDeleted || ! $element->trashed) {
                EloquentElement::syncing($model, fn () => Elements::deleteElement($element, hardDelete: ! $softDeleted));
            }
        });

        Event::listen("eloquent.restored: $modelClass", function (Model $model) use ($elementType): void {
            if (EloquentElement::isSyncing($model) || ! $element = $this->elementFor($elementType, $model)) {
                return;
            }

            if ($element->trashed) {
                EloquentElement::syncing($model, fn () => Elements::restoreElement($element));
            }
        });
    }

    /**
     * The model's element, holding the very model instance being synced, so the element's own hooks recognize it.
     *
     * @param  class-string<EloquentElement<covariant Model>>  $elementType
     * @return EloquentElement<covariant Model>|null
     */
    private function elementFor(string $elementType, Model $model): ?EloquentElement
    {
        return app(ModelLinks::class)->elementId($model) === null ? null : $elementType::fromModel($model);
    }
}
