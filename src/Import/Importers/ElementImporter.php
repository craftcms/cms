<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Importers;

use Closure;
use Craft;
use craft\services\Elements;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use http\Exception\InvalidArgumentException;
use Illuminate\Validation\Validator;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class ElementImporter extends BaseImporter
{
    public protected(set) ?Site $site = null;

    public function __construct(?array $config = null)
    {
        parent::__construct($config);
        $this->matchCriteria = ['id' => 'id'];
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Element Importer');
    }

    #[Override]
    protected function settingsHtml(bool $readOnly): string
    {
        $allElementTypes = Craft::$app->getElements()->getAllElementTypes();
        $availableElementTypes = array_map(fn ($type) => [
            'label' => $type::displayName(),
            'value' => $type,
        ], $allElementTypes);

        $defaultElementType = null;
        if (in_array(Entry::class, $allElementTypes, true)) {
            $defaultElementType = Entry::class;
        }

        return template('import/_importer-types/element-importer', [
            'readOnly' => $readOnly,
            'import' => $this,
            'availableElementTypes' => $availableElementTypes,
            'defaultElementType' => $defaultElementType,
            'availableSites' => Sites::getEditableSites()
                ->map(fn ($item) => ['label' => $item->name, 'value' => $item->handle])
                ->all(),
        ]);
    }

    //    #[Override]
    //    public function defaultName()
    //    {
    //        return t('Element Import');
    //    }

    #[Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'settings.className' => fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateElementType($value, $attribute, $fail, $validator),
            'settings.site' => [
                'required',
                'string',
                'max:255',
                fn ($attribute, $value, Closure $fail, Validator $validator) => self::validateSite($value, $attribute, $fail, $validator),
            ],
        ]);
    }

    public static function validateElementType(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        if (empty($value)) {
            $fail($attribute, t('Element type must be provided.'));

            return false;
        }

        $allElementTypes = (new Elements)->getAllElementTypes();
        if (! in_array($value, $allElementTypes)) {
            $fail($attribute, t('Element type “{elementType}” is not a valid element type.', [
                'elementType' => $value,
            ]));

            return false;
        }

        return true;
    }

    public static function validateSite(mixed $value, string $attribute, Closure $fail, Validator $validator): bool
    {
        if (empty($value)) {
            $fail($attribute, t('Site must be provided.'));

            return false;
        }

        $allSites = Sites::getAllSites()->pluck('handle')->all();
        if (! in_array($value, $allSites)) {
            $fail($attribute, t('“{site}” is not a valid site handle.', [
                'site' => $value,
            ]));

            return false;
        }

        return true;
    }

    public static function create(): self
    {
        return new self;
    }

    #[Override]
    public function className(string $className): self
    {
        $allElements = (new Elements)->getAllElementTypes();
        if (! in_array($className, $allElements)) {
            throw new InvalidArgumentException("Class '{$className}' is not a valid element type.");
        }

        $this->className = $className;

        return $this;
    }

    public function site(string|int|Site|null $site): self
    {
        if ($site instanceof Site) {
            $this->site = $site;
        } elseif ($site === null) {
            $this->site = Sites::getPrimarySite();
        } elseif (is_numeric($site)) {
            $this->site = Sites::getAllSites()->firstWhere('id', $site);
        } elseif (is_string($site)) {
            $this->site = Sites::getAllSites()->firstWhere('handle', $site);
            if ($this->site === null) {
                $this->site = Sites::getAllSites()->firstWhere('uid', $site);
            }
        }

        return $this;
    }

    #[Override]
    public function transformer(?string $transformer): self
    {
        if ($transformer === null) {
            // use the default for this element type
            $transformer = $this->className::getDefaultTransformer();
        }

        return parent::transformer($transformer);
    }

    //
    //    public function map(array $map): self
    //    {
    //        $this->map = $map;
    //
    //        return $this;
    //    }
}
