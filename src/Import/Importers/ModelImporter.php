<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Validation\Validator;
use League\Fractal\TransformerAbstract;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class ModelImporter extends BaseImporter
{
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
        if (! $value instanceof BaseModel) {
            $fail($attribute, t('Class name must extend Craft\'s BaseModel.'));

            return false;
        }

        return true;
    }

    public static function create(): self
    {
        return new self;
    }

    #[Override]
    public function transformer(string|null|TransformerAbstract $transformer): self
    {
        if ($transformer === null) {
            return $this;
        }

        return parent::transformer($transformer);
    }
}
