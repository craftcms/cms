<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Eloquent;

use ArrayIterator;
use Closure;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\ElementActivity;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use CraftCms\Cms\Http\ViewModels\EloquentElementEditViewModel;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Override;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Traversable;
use WeakMap;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * An element backed by a row of an application's own Eloquent model.
 *
 * Neither the model nor its table changes: Craft records which row each element
 * represents in its own `elements_models` table. The element proxies the model's
 * columns as its own attributes, so they show up as native fields, index columns,
 * sort options and search keywords, and writes them back to the model when a
 * canonical element is saved. Drafts and revisions never touch the model table.
 *
 * Register it with {@see EloquentElements}, which also keeps it in step with
 * writes the app makes to the model, and gives it a control panel index and nav item.
 *
 * ```php
 * class Customer extends EloquentElement
 * {
 *     public static function modelClass(): string
 *     {
 *         return \App\Models\Customer::class;
 *     }
 * }
 *
 * app(EloquentElements::class)->register(Customer::class);
 * ```
 *
 * @template TModel of Model
 *
 * @phpstan-consistent-constructor
 *
 * @since 6.0.0
 */
#[Ruleset(EloquentElementRules::class)]
abstract class EloquentElement extends Element
{
    /**
     * @var WeakMap<Model, int>|null The models whose element ⇄ model sync is in progress, so neither side echoes the other's write back.
     */
    private static ?WeakMap $syncing = null;

    /** @var array<class-string, list<string>> */
    private static array $modelAttributes = [];

    /** @var array<class-string, list<string>> */
    private static array $modelColumns = [];

    /** @var TModel|null */
    private ?Model $model = null;

    /** @return class-string<TModel> */
    abstract public static function modelClass(): string;

    /**
     * Defaults to the model's class name, in words (`OrderLine` → “Order Line”).
     */
    #[Override]
    public static function displayName(): string
    {
        return Str::headline(class_basename(static::modelClass()));
    }

    /**
     * Defaults to the plural of {@see displayName()} (“Order Lines”).
     */
    #[Override]
    public static function pluralDisplayName(): string
    {
        return Str::plural(static::displayName());
    }

    /**
     * Defaults to the model's class name, kebab-cased (`order-line`).
     *
     * Override it if that clashes with another element type's — a `User` model with Craft's users, say.
     */
    #[Override]
    public static function refHandle(): ?string
    {
        return Str::kebab(class_basename(static::modelClass()));
    }

    /**
     * The control panel path of the element type's index, or `null` for no index page.
     *
     * Defaults to the plural of {@see refHandle()} (`customers`, `order-lines`).
     */
    public static function cpIndexUri(): ?string
    {
        return Str::plural((string) static::refHandle());
    }

    /**
     * The icon of the element type's nav item.
     */
    public static function cpNavIcon(): string
    {
        return 'database';
    }

    /**
     * The field type that relates elements to this type's elements, registered along with the type.
     *
     * @return class-string<EloquentElementsField>|null
     */
    public static function relationFieldType(): ?string
    {
        return null;
    }

    /**
     * The nav item linking to the element type's index, or `null` to leave it out of the nav.
     *
     * Its sources are listed beneath it once there's more than one; a lone
     * “All” source would only repeat the item itself.
     */
    public static function cpNavItem(): ?NavItem
    {
        $uri = static::cpIndexUri();

        if ($uri === null) {
            return null;
        }

        $sources = app(Navigation::class)->sourceSubnav(static::class, $uri);

        return new NavItem()
            ->label(static::pluralDisplayName())
            ->href($uri)
            ->icon(static::cpNavIcon())
            ->subnav(count($sources) > 1 ? $sources : false);
    }

    /**
     * What happens to the model when the element is moved to the trash.
     *
     * Defaults to soft-deleting the model when it uses `SoftDeletes`, and keeping it otherwise.
     */
    public static function trashBehavior(): ModelTrashBehavior
    {
        return static::modelSoftDeletes() ? ModelTrashBehavior::SoftDelete : ModelTrashBehavior::Keep;
    }

    public static function modelSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive(static::modelClass()), true);
    }

    /**
     * Runs a write on one side of a model's element ⇄ model pair without the other side writing it back.
     *
     * Only that model is affected, so other models written along the way (by an
     * observer, say) still sync with their elements.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function syncing(Model $model, Closure $callback): mixed
    {
        $syncing = self::$syncing ??= new WeakMap;
        $syncing[$model] = ($syncing[$model] ?? 0) + 1;

        try {
            return $callback();
        } finally {
            if (--$syncing[$model] === 0) {
                unset($syncing[$model]);
            }
        }
    }

    public static function isSyncing(Model $model): bool
    {
        return isset(self::$syncing[$model]);
    }

    /**
     * The model attribute used as the element's label in the control panel.
     *
     * Defaults to the model's `name` or `title` column, whichever it has first.
     */
    public static function labelAttribute(): ?string
    {
        return array_find(['name', 'title'], fn (string $column) => in_array($column, self::modelColumns(), true));
    }

    /**
     * The model attributes the element exposes as its own.
     *
     * Defaults to every column except the primary key, the timestamps, the soft-delete
     * column, and any named like one of the element's own properties (`title`,
     * `enabled`, `slug`, …), which the element can't stand in for. Those are still
     * on the model ({@see getModel()}).
     *
     * @return list<string>
     */
    public static function modelAttributes(): array
    {
        return self::$modelAttributes[static::class] ??= (function (): array {
            $model = static::newModel();
            $elementProperties = array_map(
                fn (ReflectionProperty $property) => $property->getName(),
                array_filter(
                    new ReflectionClass(static::class)->getProperties(ReflectionProperty::IS_PUBLIC),
                    fn (ReflectionProperty $property) => ! $property->isStatic(),
                ),
            );
            $excluded = [
                $model->getKeyName(),
                $model->getCreatedAtColumn(),
                $model->getUpdatedAtColumn(),
                method_exists($model, 'getDeletedAtColumn') ? $model->getDeletedAtColumn() : null,
                ...$elementProperties,
            ];

            return array_values(array_diff(self::modelColumns(), $excluded));
        })();
    }

    /**
     * The model attributes the element exposes ({@see modelAttributes()}), less any in the model's
     * `$hidden` property, which are kept out of its index columns, sort options and search keywords.
     *
     * @return list<string>
     */
    public static function listedModelAttributes(): array
    {
        return array_values(array_diff(static::modelAttributes(), static::newModel()->getHidden()));
    }

    /**
     * Every column of the model's table.
     *
     * @return list<string>
     */
    public static function modelColumns(): array
    {
        return self::$modelColumns[static::class] ??= Schema::getColumnListing(static::newModel()->getTable());
    }

    /** @return TModel */
    public static function newModel(): Model
    {
        $class = static::modelClass();

        return new $class;
    }

    /**
     * Returns the element for a model, creating an unsaved one if the model doesn't have one yet.
     */
    public static function fromModel(Model $model): static
    {
        $element = static::findForModel($model) ?? new static;
        $element->setModel($model);

        return $element;
    }

    /**
     * Returns the element linked to a model, including a trashed one.
     */
    public static function findForModel(Model $model): ?static
    {
        $elementId = app(ModelLinks::class)->elementId($model);

        return $elementId === null ? null : new EloquentElementQuery(static::class, modelScopes: false)->id($elementId)->status(null)->trashed(null)->one();
    }

    /**
     * Deletes the models linked to elements that are about to be purged.
     *
     * @param  list<int>  $elementIds
     * @return list<int> The IDs of the elements whose models are gone, which can be purged. An
     *                   element whose model refused to be deleted stays in the trash.
     */
    public static function deleteModels(array $elementIds): array
    {
        $keys = app(ModelLinks::class)->modelKeys($elementIds);
        $models = static::newModel()->newQueryWithoutScopes()->whereKey(array_values($keys))->get()
            ->keyBy(fn (Model $model) => (string) $model->getKey());

        return array_values(array_filter($elementIds, function (int $elementId) use ($keys, $models): bool {
            $model = isset($keys[$elementId]) ? $models->get($keys[$elementId]) : null;

            return $model === null || static::syncing($model, fn () => $model->forceDelete()) !== false;
        }));
    }

    /** @return EloquentElementQuery<static> */
    #[Override]
    public static function find(): EloquentElementQuery
    {
        return new EloquentElementQuery(static::class);
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return false;
    }

    #[Override]
    public static function hasDrafts(): bool
    {
        return false;
    }

    #[Override]
    public static function editViewModelClass(): string
    {
        return EloquentElementEditViewModel::class;
    }

    /** @return TModel */
    public function getModel(): Model
    {
        return $this->model ??= static::newModel();
    }

    /** @param TModel|null $model */
    public function setModel(?Model $model): void
    {
        $this->model = $model;
    }

    /**
     * Validation rules for the model attributes, applied when the element is saved live.
     *
     * @return array<string, mixed>
     */
    public function modelRules(): array
    {
        return [];
    }

    public function isModelAttribute(string $name): bool
    {
        return in_array($name, static::modelAttributes(), true);
    }

    #[Override]
    public function __get(string $name): mixed
    {
        if ($this->isModelAttribute($name)) {
            return $this->getModel()->getAttribute($name);
        }

        return parent::__get($name);
    }

    #[Override]
    public function __set(string $name, mixed $value): void
    {
        if ($this->isModelAttribute($name)) {
            $this->getModel()->setAttribute($name, $value);

            return;
        }

        parent::__set($name, $value);
    }

    #[Override]
    public function canGetProperty(string $name): bool
    {
        return $this->isModelAttribute($name) || parent::canGetProperty($name);
    }

    #[Override]
    public function canSetProperty(string $name): bool
    {
        return $this->isModelAttribute($name) || parent::canSetProperty($name);
    }

    #[Override]
    public function __isset(string $name): bool
    {
        if ($this->isModelAttribute($name)) {
            return $this->getModel()->getAttribute($name) !== null;
        }

        return parent::__isset($name);
    }

    /**
     * Sandboxed templates may read the model's attributes too, except the ones the model hides from serialization.
     */
    #[Override]
    public function propertyAllowedInSandbox(string $property): bool
    {
        if ($this->isModelAttribute($property)) {
            return ! in_array($property, $this->getModel()->getHidden(), true);
        }

        return parent::propertyAllowedInSandbox($property);
    }

    #[Override]
    public function __clone()
    {
        parent::__clone();

        if ($this->model) {
            $this->model = clone $this->model;
        }
    }

    #[Override]
    public function attributes(): array
    {
        return array_values(array_unique([...parent::attributes(), ...static::modelAttributes()]));
    }

    /**
     * Leaves out the model's hidden attributes, as the model's own serialization does.
     */
    #[Override]
    public function fields(): array
    {
        return array_diff_key(parent::fields(), array_flip($this->getModel()->getHidden()));
    }

    /**
     * Leaves out the model's hidden attributes, so looping over an element in a
     * sandboxed template can't reach what reading them directly can't.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator(array_diff_key($this->validationData(), array_flip($this->getModel()->getHidden())));
    }

    /**
     * Only lets a request assign the model attributes the model itself allows to be mass assigned.
     */
    #[Override]
    public function setAttributesFromRequest(array $values): void
    {
        $model = $this->getModel();

        foreach ($values as $name => $value) {
            if (! $this->isModelAttribute($name)) {
                continue;
            }

            if ($model->isFillable($name)) {
                $model->setAttribute($name, $value === '' ? null : $value);
            }

            unset($values[$name]);
        }

        parent::setAttributesFromRequest($values);
    }

    #[Override]
    public function afterSave(bool $isNew): void
    {
        if ($this->ownsModel()) {
            $this->saveModel($isNew);
        }

        parent::afterSave($isNew);
    }

    #[Override]
    public function afterDelete(): void
    {
        parent::afterDelete();

        $model = $this->getModel();

        if (! $this->ownsModel() || static::isSyncing($model) || ! $model->exists) {
            return;
        }

        if ($this->hardDelete) {
            $this->ensureModelWrite(static::syncing($model, fn () => $model->forceDelete()) !== false, 'deleted');
        } elseif (static::trashBehavior() === ModelTrashBehavior::SoftDelete) {
            $this->ensureModelWrite(static::syncing($model, fn () => $model->delete()) !== false, 'deleted');
        }
    }

    #[Override]
    public function afterRestore(): void
    {
        parent::afterRestore();

        $model = $this->getModel();

        if (
            $this->ownsModel() &&
            ! static::isSyncing($model) &&
            static::trashBehavior() === ModelTrashBehavior::SoftDelete &&
            method_exists($model, 'trashed') &&
            $model->trashed()
        ) {
            $this->ensureModelWrite(static::syncing($model, fn () => $model->restore()) !== false, 'restored');
        }
    }

    /**
     * Whether this is the canonical element, which is the only one that reads and writes the model.
     */
    private function ownsModel(): bool
    {
        return ! $this->getIsDraft() && ! $this->getIsRevision();
    }

    private function saveModel(bool $isNew): void
    {
        $links = app(ModelLinks::class);
        $model = $this->getModel();

        // The model's own save is what's being synced. An existing element was found through
        // its link, so only a new one needs linking. Its changes stay dirty until `saved` ends.
        if (static::isSyncing($model)) {
            if ($isNew) {
                $links->link($this->id, $model);
            }

            $this->recordModelActivity($isNew, $this->modelChanges($model));

            return;
        }

        $linkedElementId = $links->elementId($model);

        // A duplicated element gets a row of its own
        if ($linkedElementId !== null && $linkedElementId !== $this->id) {
            $model = $this->model = $model->replicate();
            $linkedElementId = null;
        }

        $created = ! $model->exists;
        $changes = $this->modelChanges($model);

        $this->ensureModelWrite(static::syncing($model, fn () => $model->save()), 'saved');

        if ($linkedElementId === null) {
            $links->link($this->id, $model);
        }

        $this->recordModelActivity($created, $changes);
    }

    /**
     * The model's unsaved changes to its exposed attributes, as raw column values.
     *
     * @return list<ActivityChange>
     */
    private function modelChanges(Model $model): array
    {
        return collect($model->getDirty())
            ->only(static::modelAttributes())
            ->except($model->getHidden())
            ->map(fn (mixed $new, string $attribute) => new ActivityChange(
                Str::headline($attribute),
                $model->getRawOriginal($attribute),
                $new,
            ))
            ->values()
            ->all();
    }

    /**
     * Records the model's creation or update on the element's activity timeline.
     *
     * Duplicates are already recorded as duplicated.
     *
     * @param  list<ActivityChange>  $changes
     */
    private function recordModelActivity(bool $created, array $changes): void
    {
        if (
            $this->duplicateOf !== null ||
            (! $created && $changes === []) ||
            ! ElementActivity::shouldRecordWrite($this)
        ) {
            return;
        }

        $site = Sites::getSiteById($this->siteId);

        Activities::record($created
            ? new ElementCreated(subject: $this, site: $site)
            : new ElementUpdated(subject: $this, site: $site, changes: $changes));
    }

    /**
     * Fails the element's write — rolling back its transaction — when the model's
     * write was cancelled, by an observer returning `false`, say.
     */
    private function ensureModelWrite(bool $written, string $action): void
    {
        if (! $written) {
            throw new RuntimeException(sprintf('The %s couldn’t be %s: its model refused the change.', static::lowerDisplayName(), $action));
        }
    }

    #[Override]
    protected function uiLabel(): ?string
    {
        $attribute = static::labelAttribute();

        if ($attribute === null) {
            return null;
        }

        $label = $this->getModel()->getAttribute($attribute);

        return is_scalar($label) && $label !== '' ? (string) $label : null;
    }

    /**
     * `{index URI}/{id}`, so the editor sits beneath the index in the nav.
     */
    #[Override]
    protected function cpEditUrl(): ?string
    {
        $uri = static::cpIndexUri();

        return $uri === null
            ? ElementHelper::elementEditorUrl($this, false)
            : Url::cpUrl("$uri/{$this->getCanonicalId()}");
    }

    #[Override]
    protected function crumbs(): array
    {
        $uri = static::cpIndexUri();

        return $uri === null ? [] : [
            new ActionItem()->label(static::pluralDisplayName())->href(Url::cpUrl($uri)),
        ];
    }

    /**
     * Returns the field layout saved for the element type, or the one defined by {@see defineFieldLayout()}.
     */
    #[Override]
    public function getFieldLayout(): FieldLayout
    {
        return static::resolveFieldLayout();
    }

    /**
     * The field layout saved for the element type, or the one defined by {@see defineFieldLayout()}.
     */
    public static function resolveFieldLayout(): FieldLayout
    {
        return app(Fields::class)->getLayoutByType(static::class, create: false)
            ?? self::definedFieldLayout(static::class);
    }

    /**
     * Defines the element type's field layout in code.
     *
     * Defaults to a single tab with a field per model attribute. Model attributes
     * are added as {@see TextField}s:
     *
     * ```php
     * public static function defineFieldLayout(FieldLayout $layout): FieldLayout
     * {
     *     return $layout
     *         ->tab('Contact', fn (FieldLayoutTab $tab) => $tab->add(
     *             new TextField(['attribute' => 'name', 'label' => 'Name']),
     *             new TextField(['attribute' => 'email', 'label' => 'Email']),
     *         ))
     *         ->tab('Company', fn (FieldLayoutTab $tab) => $tab
     *             ->add(new TextField(['attribute' => 'company', 'label' => 'Company']))
     *             ->tip('Shown on invoices.'));
     * }
     * ```
     *
     * Custom fields can be added with {@see FieldLayoutTab::field()}.
     *
     * Cards show the element's label; add to them with {@see FieldLayout::cardView()},
     * which takes model attributes and custom fields by handle.
     *
     * The layout isn't saved, so its tabs and elements get UIDs derived from the
     * element type instead: fields by attribute or handle, tabs by name, and other
     * elements by position within their tab. Custom field values are stored by
     * those UIDs, so they stay put when fields are reordered or moved between tabs,
     * but are left behind if a field is swapped for another with a different handle.
     */
    public static function defineFieldLayout(FieldLayout $layout): FieldLayout
    {
        $fields = array_map(fn (string $attribute) => new TextField([
            'attribute' => $attribute,
            'label' => Str::headline($attribute),
        ]), static::modelAttributes());

        return $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(...$fields));
    }

    /**
     * Builds the code-defined layout once per request.
     *
     * @param  class-string<static>  $elementType
     */
    private static function definedFieldLayout(string $elementType): FieldLayout
    {
        return once(function () use ($elementType): FieldLayout {
            $layout = $elementType::defineFieldLayout(FieldLayout::make($elementType));
            $namespace = Uuid::uuid5(Uuid::NAMESPACE_URL, "craftcms:eloquent-element:$elementType");
            $uid = fn (string $name) => Uuid::uuid5($namespace, $name)->toString();

            $layout->uid ??= $uid('layout');

            foreach ($layout->getTabs() as $tab) {
                $tab->uid ??= $uid("tab:$tab->name");

                foreach ($tab->getElements() as $position => $element) {
                    $element->uid ??= $uid($element instanceof BaseField
                        ? "field:{$element->attribute()}"
                        : "tab:$tab->name:$position");
                }
            }

            $layout->reset();

            return $layout;
        });
    }

    /**
     * The key of the Craft permission for `view`, `create`, `edit` or `delete`
     * (`elements:customer:edit`), namespaced by the {@see refHandle()} so that it
     * can't clash with another permission, or change when the display name is translated.
     */
    public static function permission(string $action): string
    {
        return static::permissionNamespace().":$action";
    }

    /**
     * The prefix of the element type's permission keys, and the handle of their group.
     */
    public static function permissionNamespace(): string
    {
        return 'elements:'.static::refHandle();
    }

    /**
     * The Craft permissions for the element type, consulted when its model has no policy.
     */
    public static function permissionGroup(): PermissionGroup
    {
        $type = static::pluralLowerDisplayName();

        return new PermissionGroup(
            handle: static::permissionNamespace(),
            heading: static::pluralDisplayName(),
            permissions: collect([
                new Permission(
                    key: static::permission('view'),
                    label: t('View {type}', ['type' => $type]),
                    nested: collect([
                        new Permission(static::permission('create'), mb_ucfirst(t('Create {type}', ['type' => $type]))),
                        new Permission(static::permission('edit'), mb_ucfirst(t('Edit {type}', ['type' => $type]))),
                        new Permission(static::permission('delete'), mb_ucfirst(t('Delete {type}', ['type' => $type]))),
                    ]),
                ),
            ]),
        );
    }

    /**
     * Whether a user may see the element type's index and nav item: the model policy's
     * `viewAny` ability when the model has a policy, otherwise the “View” permission.
     */
    public static function canViewIndex(CraftUser $user): bool
    {
        $gate = Gate::forUser($user);

        return Gate::getPolicyFor(static::modelClass()) === null
            ? $gate->check(static::permission('view'))
            : $gate->check('viewAny', static::modelClass());
    }

    /**
     * Authorizes against the model's policy when the app registered one, otherwise against the
     * element type's Craft permissions ({@see PermissionGroup()}).
     *
     * @param  'view'|'create'|'update'|'delete'  $ability
     */
    protected function canModel(User $user, string $ability): bool
    {
        $model = $this->getModel();
        $gate = Gate::forUser(self::policyUser($user));

        if (Gate::getPolicyFor($model) === null) {
            return $gate->check(static::permission($ability === 'update' ? 'edit' : $ability));
        }

        return $gate->check($ability, $model);
    }

    /**
     * The user as Craft's auth guard returns them — the User model, in a request — so a model
     * policy is handed the same kind of user here as it is everywhere else in the app.
     */
    private static function policyUser(User $user): CraftUser
    {
        $currentUser = currentUser();

        if ($currentUser !== null && $currentUser->getCraftUserId() === $user->id) {
            return $currentUser;
        }

        return UserModel::query()->find($user->id) ?? $user;
    }

    #[Override]
    public function canView(User $user): bool
    {
        return $this->canModel($user, 'view');
    }

    #[Override]
    public function canSave(User $user): bool
    {
        return $this->canModel($user, $this->getModel()->exists ? 'update' : 'create');
    }

    #[Override]
    public function canDuplicate(User $user): bool
    {
        return $this->canModel($user, 'create');
    }

    #[Override]
    public function canDelete(User $user): bool
    {
        return $this->canModel($user, 'delete');
    }

    /**
     * Offers the sources only to users who may view the index, so without a source
     * the element index endpoints find nothing — as Entries only offers the sections
     * a user can edit.
     *
     * Checked here rather than in {@see defineSources()}, so overriding that can't
     * skip it, and on every call, outside the cache of defined sources.
     */
    #[Override]
    public static function sources(string $context): array
    {
        $user = currentUser();

        if ($user === null || ! static::canViewIndex($user)) {
            return [];
        }

        return parent::sources($context);
    }

    #[Override]
    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => t('All {type}', ['type' => static::pluralLowerDisplayName()]),
                'criteria' => [],
            ],
        ];
    }

    /**
     * The element type's one layout, so a code-defined layout's custom fields are offered as index columns and sort options too.
     */
    #[Override]
    protected static function defineFieldLayouts(?string $source): array
    {
        return [static::resolveFieldLayout()];
    }

    #[Override]
    protected static function defineTableAttributes(): array
    {
        $attributes = [];

        foreach (static::listedModelAttributes() as $attribute) {
            $attributes[$attribute] = ['label' => Str::headline($attribute)];
        }

        return [...$attributes, ...parent::defineTableAttributes()];
    }

    #[Override]
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [...static::listedModelAttributes(), 'dateCreated'];
    }

    #[Override]
    protected static function defineSortOptions(): array
    {
        $table = static::newModel()->getTable();
        $options = [];

        foreach (static::listedModelAttributes() as $attribute) {
            $options[] = [
                'label' => Str::headline($attribute),
                'orderBy' => "$table.$attribute",
                'attribute' => $attribute,
            ];
        }

        return [...$options, ...array_diff_key(parent::defineSortOptions(), array_flip(static::modelAttributes()))];
    }

    /**
     * The listed model attributes, and the label column even when it isn't one (a `title` column, say).
     */
    #[Override]
    protected static function defineSearchableAttributes(): array
    {
        return array_values(array_unique(array_filter([...static::listedModelAttributes(), static::labelAttribute()])));
    }

    /**
     * Reads model columns from the model, including those the element doesn't expose.
     */
    #[Override]
    protected function searchKeywords(string $attribute): string
    {
        if (in_array($attribute, self::modelColumns(), true)) {
            return Str::toString($this->getModel()->getAttribute($attribute));
        }

        return parent::searchKeywords($attribute);
    }
}
