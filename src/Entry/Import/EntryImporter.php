<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Import;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Import\Transformers\EntryTransformer;
use CraftCms\Cms\Support\Facades\EntryTypes;
use Override;

use function CraftCms\Cms\t;

/**
 * Imports data into Entry elements.
 */
class EntryImporter extends ElementImporter
{
    public function __construct(?array $config = null)
    {
        parent::__construct($config);

        $this->className = Entry::class;
    }

    #[Override]
    public static function elementClass(): string
    {
        return Entry::class;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Entries');
    }

    /**
     * Convenience factory returning a new instance.
     */
    public static function create(): self
    {
        return new self;
    }

    public static function getDefaultTransformer(): ?string
    {
        return EntryTransformer::class;
    }

    #[Override]
    public function prepareNewRootElementForImport(array &$data, ?ElementInterface $element = null): ElementInterface
    {
        /** @var Entry $element */
        if ($element === null) {
            $entryType = null;

            // if it's UI-driven element import where the fieldLayout was chosen in the editable config,
            // we need to ensure the typeId is set
            if ($this->fieldLayout) {
                $allEntryTypes = EntryTypes::getAllEntryTypes();
                $allFieldLayouts = $allEntryTypes->mapWithKeys(function ($entryType) {
                    $fieldLayout = $entryType->getFieldLayout();

                    return [$fieldLayout->id => $fieldLayout];
                });
                $entryType = $allFieldLayouts->firstWhere('uid', $this->fieldLayout)?->provider;
                if ($entryType) {
                    $data['typeId'] = $entryType->id;
                }
            } elseif ($data['typeId']) {
                if (is_numeric($data['typeId'])) {
                    $entryType = EntryTypes::getEntryTypeById((int) $data['typeId']);
                } else {
                    $entryType = EntryTypes::getEntryTypeByHandle($data['typeId']) ?? EntryTypes::getEntryTypeByUid($data['typeId']);
                }
            }

            $element = new $this->className;
            if ($entryType) {
                $element->setTypeId($entryType->id);
                $element->fieldLayoutId = $entryType->getFieldLayoutId();
                if (isset($data['matchCriteria']['typeId'])) {
                    unset($data['matchCriteria']['typeId']);
                }
            }
        }

        parent::prepareNewRootElementForImport($data, $element);

        return $element;
    }

    #[Override]
    public function setAttributesForImport(ElementInterface $element, array $attributes): void
    {
        // for UI-based import, ensure we're not changing type ID compared to what we chose in the field layout provider step
        if (isset($this->fieldLayout)) {
            unset($attributes['typeId']);
        }

        parent::setAttributesForImport($element, $attributes);
    }
}
