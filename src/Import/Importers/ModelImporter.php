<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\Schema;
use Override;

/**
 * The ModelImporter should be used for importing data into an eloquent model.
 * It's abstract - each importable model is represented by its own concrete subclass, which
 * implements modelClass() to name its target model (see SystemMessageImporter).
 * A model is importable if and only if a ModelImporter subclass is registered for it, via the
 * RegisterImporterTypes event.
 * Element types must use an ElementImporter subclass instead (see EntryImporter, AssetImporter, UserImporter).
 */
abstract class ModelImporter extends BaseImporter
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

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): array
    {
        return [];
    }

    #[Override]
    public function refreshSettingsForm(array $settings): void {}

    #[Override]
    public function storeSettings(array $settings): void {}

    #[Override]
    public function getSettings(): array
    {
        return [];
    }

    #[Override]
    public static function getDefaultTransformer(): ?string
    {
        return null;
    }

    #[Override]
    public function getDestinationCols(): array
    {
        $columns = Schema::getColumns((new (static::targetClass()))->getTable());

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
     * Returns whether a given DB column type can be used to value being imported against the value in the database.
     * You can match on text, numeric, boolean, and date/time values.
     */
    private function isTypeMatchable(string $type): bool
    {
        $type = self::TYPE_ALIASES[$type] ?? $type;

        return in_array(Query::getSimplifiedColumnType($type), [Query::SIMPLE_TYPE_NUMERIC, Query::SIMPLE_TYPE_TEXTUAL]) ||
            in_array($type, [Query::TYPE_BOOLEAN, Query::TYPE_DATETIME, Query::TYPE_DATE, Query::TYPE_TIME, Query::TYPE_TIMESTAMP], true);
    }

    /**
     * Creates a new model or looks up an existing one via a match-criteria `where()` query.
     */
    private function getModel(array $data): BaseModel
    {
        $model = new (static::targetClass());

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
