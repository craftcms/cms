<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element\Queries;

use Craft;
use craft\elements\GlobalSet;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Http\Controllers\Gql\ApiController;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use Illuminate\Support\Facades\Route;
use Override;

/**
 * @template T of GlobalSet
 *
 * @extends ElementQuery<T>
 * @deprecated in 6.0.0
 */
class GlobalSetQuery extends ElementQuery
{
    #[Override]
    protected string $table = DeprecatedTable::GLOBALSETS;

    #[Override]
    protected array $defaultOrderBy = ['globalsets.sortOrder' => SORT_ASC];

    /**
     * @var bool|null Whether to only return global sets that the user has permission to edit.
     *
     * @used-by editable()
     */
    public ?bool $editable = null;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     *
     * @used-by handle()
     */
    public string|array|null $handle = null;

    /**
     * @var mixed The reference code(s) used to identify the element(s).
     *
     * This property is set when accessing elements via their reference tags, e.g. `{globalset:handle}`.
     *
     * @used-by ref()
     */
    public mixed $ref = null;

    public function __construct(array $config = [])
    {
        parent::__construct(GlobalSet::class, $config);

        $this->query->addSelect([
            'globalsets.name',
            'globalsets.handle',
            'globalsets.sortOrder',
        ]);

        $this->beforeQuery(function(self $query) {
            $this->applyHandleParam($query);
            $this->applyEditableParam($query);
            $this->applyRefParam($query);
        });
    }

    /**
     * Sets the [[$editable]] property.
     *
     * @param  bool|null  $value  The property value (defaults to true)
     *
     * @uses $editable
     */
    public function editable(?bool $value = true): static
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the global sets’ handles.
     *
     * Possible values include:
     *
     * | Value | Fetches global sets…
     * | - | -
     * | `'foo'` | with a handle of `foo`.
     * | `'not foo'` | not with a handle of `foo`.
     * | `['foo', 'bar']` | with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not with a handle of `foo` or `bar`.
     *
     * ---
     *
     * ```twig
     * {# Fetch the global set with a handle of 'foo' #}
     * {% set {element-var} = {twig-method}
     *   .handle('foo')
     *   .one() %}
     * ```
     *
     * ```php
     * // Fetch the global set with a handle of 'foo'
     * ${element-var} = {php-method}
     *     ->handle('foo')
     *     ->one();
     * ```
     *
     * @uses $handle
     */
    public function handle(string|array|null $value): static
    {
        $this->handle = $value;

        return $this;
    }

    /**
     * Narrows the query results based on a reference string.
     */
    public function ref(mixed $value): static
    {
        $this->ref = $value;

        return $this;
    }

    /**
     * Applies the 'handle' param to the query being prepared.
     */
    private function applyHandleParam(self $query): void
    {
        if ($query->handle) {
            $query->whereParam('globalsets.handle', $query->handle);
        }
    }

    /**
     * Applies the 'editable' param to the query being prepared.
     */
    private function applyEditableParam(self $query): void
    {
        if ($query->editable) {
            // Limit the query to only the global sets the user has permission to edit
            $query->whereIn('elements.id', Craft::$app->getGlobals()->getEditableSetIds());
        }
    }

    /**
     * Applies the 'ref' param to the query being prepared.
     */
    private function applyRefParam(self $query): void
    {
        if ($query->ref) {
            $query->whereParam('globalsets.handle', $query->ref);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getCacheTags(): array
    {
        // no need to register cache tags for global set queries,
        // unless this is a GraphQL request
        if (Route::current()?->controller instanceof ApiController) {
            return parent::getCacheTags();
        }

        return [];
    }
}
