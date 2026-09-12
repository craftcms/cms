<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Contracts\ImportableModelInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;
use Override;

use function CraftCms\Cms\t;

/**
 * The ModelImporter should be used for importing data into an eloquent model.
 * For a model to support this, it has to implement the ImportableModelInterface.
 * Element types must use ElementImporter.
 * Unlike with Elements (where all elements start as importable and can opt out via `isImportable()` method,
 * ImportableModelInterface is strictly an opt-in mechanism.
 */
class ModelImporter extends BaseImporter
{
    /**
     * Maps driver-specific `type_name` values, as reported by `Schema::getColumns()` for
     * MySQL and PostgreSQL connections, to their equivalent {@see Query::TYPE_*} constant.
     */
    private const array TYPE_ALIASES = [
        // MySQL
        'int' => Query::TYPE_INTEGER,
        'mediumint' => Query::TYPE_INTEGER,

        // PostgreSQL
        'int2' => Query::TYPE_SMALLINT,
        'int4' => Query::TYPE_INTEGER,
        'int8' => Query::TYPE_BIGINT,
        'bpchar' => Query::TYPE_CHAR,
        'numeric' => Query::TYPE_DECIMAL,
        'float4' => Query::TYPE_FLOAT,
        'float8' => Query::TYPE_DOUBLE,
        'bool' => Query::TYPE_BOOLEAN,
        'timestamptz' => Query::TYPE_TIMESTAMP,
        'timetz' => Query::TYPE_TIME,

        // MySQL and PostgreSQL
        'varchar' => Query::TYPE_STRING,
    ];

    /**
     * Calls the parent constructor then sets default match criteria to `['id' => 'id']`.
     *
     * @param  array|null  $config  Optional config array, potentially containing a `uid` key.
     */
    public function __construct(?array $config = null)
    {
        parent::__construct($config);
        $this->matchCriteria = ['id' => 'id'];
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Model Importer');
    }

    #[Override]
    public static function getSettingsRules(): array
    {
        return array_merge(parent::getSettingsRules(), [
            'settings.className' => fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateModel($value, $attribute, $fail, $validator),
        ]);
    }

    /**
     * Validates that the given class is not an element type and does extend BaseModel.
     *
     * @param  mixed  $value  The value of the model class being validated.
     * @param  string  $attribute  The name of the attribute being validated.
     * @param  Closure  $fail  The callback function to invoke when validation fails.
     * @param  Validator  $validator  The validator instance performing the validation.
     */
    public static function validateModel(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        // can't be empty
        if (empty($value)) {
            $fail($attribute, t('Model must be provided.'));

            return false;
        }

        // can't be for an element type - in that case the ElementImporter should be used
        $allElementTypes = Elements::getAllElementTypes();
        if (in_array($value, $allElementTypes)) {
            $fail($attribute, t('Model “{elementType}” is a valid element type. Use ElementImporter to handle it.', [
                'elementType' => $value,
            ]));

            return false;
        }

        // has to implement ImportableModel interface
        if (! is_subclass_of($value, ImportableModelInterface::class)) {
            $fail($attribute, t('Class name must implement Craft\'s ImportableModelInterface.'));

            return false;
        }

        return true;
    }

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
    }

    #[Override]
    public function getDestinationCols(): array
    {
        $columns = Schema::getColumns((new $this->className)->getTable());

        return array_map(fn ($col) => [
            'handle' => $col['name'],
            'label' => $col['name'],
            'prefixedHandleForMap' => Html::namespaceInputName($col['name'], 'map'),
            'prefixedHandleForMatchCriteria' => Html::namespaceInputName($col['name'], 'matchCriteria'),
            'prefixedHandleForClear' => Html::namespaceInputName($col['name'], 'clearableItems'),
            'prefixedHandle' => $col['name'],
            'prefixedHandleAsArray' => Arr::bracketsToArray($col['name']),
            'isContainer' => false,
            'canBeMatchCriteria' => $this->isTypeMatchable($col['type_name']),
            'canBeCleared' => $col['nullable'],
            // 'isProperty' => true,
        ], $columns);
    }

    /**
     * Returns whether a given DB column type can be used to value being imported against the value in the database.
     * You can match on text, numeric, boolean, and date/time values.
     */
    private function isTypeMatchable(string $type): bool
    {
        $type = self::TYPE_ALIASES[$type] ?? $type;

        return in_array(Query::getSimplifiedColumnType($type), [Query::SIMPLE_TYPE_NUMERIC, Query::SIMPLE_TYPE_TEXTUAL]) ||
            in_array($type, [Query::TYPE_BOOLEAN, Query::TYPE_DATETIME, Query::TYPE_DATE, Query::TYPE_TIME, Query::TYPE_TIMESTAMP], true);
    }

    #[Override]
    public function getSourceDataCols(): array
    {
        $filePath = BaseImporter::resolvedFilePath($this->file);

        // a config can be saved before its file is chosen, and the map screen still renders
        if ($filePath === null) {
            return [];
        }

        return Import::getDataHeadings($filePath);
    }

    #[Override]
    public function importItem(array $data): void
    {
        $model = $this->getModel($data);
        $isNew = ! $model->exists;

        $item = Import::processData($this, $data, $model);

        $attributeHandles = Schema::getColumnListing($model->getTable());
        $attributes = array_filter(array_filter($item, fn ($value, $key) => in_array($key, $attributeHandles), ARRAY_FILTER_USE_BOTH));

        $model->fill($attributes);

        if ($isNew || $model->isDirty()) {
            $model->save();
        }
    }

    /**
     * Creates a new model or looks up an existing one via a match-criteria `where()` query.
     */
    private function getModel(array $data): BaseModel
    {
        $model = new $this->className;

        // if null then return a brand new model
        if (! isset($data['matchCriteria'])) {
            return $model;
        }

        if (is_array($data['matchCriteria'])) {
            $criteria = $data['matchCriteria'];

            if (empty($criteria)) {
                return $model;
            }

            $query = $model::query()
                ->where($criteria);

            return $query->first() ?? $model;
        }

        return $model;
    }
}
