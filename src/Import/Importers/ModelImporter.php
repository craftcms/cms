<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Cms\Import\Transformers\BaseTransformer;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Import;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class ModelImporter extends BaseImporter
{
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
    protected function settingsHtml(bool $readOnly): string
    {
        return template('import/_importer-types/base-importer', [
            'readOnly' => $readOnly,
            'import' => $this,
        ]);
    }

    #[Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
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

        // has to extend Craft's BaseModel
        if (! (new $value) instanceof BaseModel) {
            $fail($attribute, t('Class name must extend Craft\'s BaseModel.'));

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
    public function transformer(string|null|BaseTransformer $transformer): self
    {
        //        if ($transformer === null) {
        //            return $this;
        //        }

        return parent::transformer($transformer);
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
            //            'canBeMatchCriteria' => $this->isTypeMatchable($col['type']),
            //            'canBeCleared' => !$col['nullable'],
            // 'isProperty' => true,
        ], $columns);
    }

    #[Override]
    public function getSourceDataCols(): array
    {
        $filePath = BaseImporter::resolvedFilePath($this->file);

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
