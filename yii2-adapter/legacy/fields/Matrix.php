<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use Override;
use RuntimeException;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Matrix} instead.
 */
class Matrix extends \CraftCms\Cms\Field\Matrix implements LegacyField
{
    use LegacyBuiltInField;

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->inputHtmlInternal($value, $element, false);
    }

    private function inputHtmlInternal(mixed $value, ?ElementInterface $element, bool $static): string
    {
        return match ($this->viewMode) {
            self::VIEW_MODE_BLOCKS => $this->blockInputHtml($value, $element, $static),
            default => Html::tag('div', $this->nestedElementManagerHtml($value, $element, $static), [
                'id' => $this->getInputId(),
            ]),
        };
    }

    /** @param EntryQuery<Entry>|ElementCollection<int,Entry>|null $value */
    private function blockInputHtml(EntryQuery|ElementCollection|null $value, ?ElementInterface $element, bool $static): string
    {
        if (!$element?->id) {
            $message = t('{nestedType} can only be created after the {ownerType} has been saved.', [
                'nestedType' => Entry::pluralDisplayName(),
                'ownerType' => $element ? $element::lowerDisplayName() : t('element'),
            ]);

            return Html::tag('div', $message, ['class' => 'pane no-border zilch small']);
        }

        if ($element->hasEagerLoadedElements($this->handle)) {
            $value = $element->getEagerLoadedElements($this->handle)->all();
        }

        if ($value instanceof EntryQuery) {
            $value = $value->getResultOverride() ?? (clone $value)
                ->drafts(null)
                ->canonicalsOnly()
                ->status(null)
                ->limit(null)
                ->all();
        }

        if ($static && empty($value)) {
            return '<p class="light">' . t('No entries.') . '</p>';
        }

        $id = $this->getInputId();
        /** @var Entry[] $value */
        $entryTypes = $this->getEntryTypesForField($value, $element);

        // Get the entry types data
        $entryTypeInfo = array_map(fn(EntryType $entryType) => [
            'id' => $entryType->id,
            'handle' => $entryType->handle,
            'name' => t($entryType->name, category: 'site'),
        ], $entryTypes);
        $createDefaultEntries = (
            $this->minEntries != 0 &&
            count($entryTypeInfo) === 1 &&
            !$element->errors()->has($this->handle)
        );
        $staticEntries = (
            $static ||
            (
                $createDefaultEntries &&
                $this->minEntries === $this->maxEntries &&
                $this->maxEntries >= count($value)
            )
        );

        $settings = [
            'fieldId' => $this->id,
            'maxEntries' => $this->maxEntries,
            'namespace' => InputNamespace::get(),
            'baseInputName' => InputNamespace::namespaceInputName($this->handle),
            'ownerElementType' => $element::class,
            'ownerId' => $element->id,
            'siteId' => $element->siteId,
            'static' => $static,
            'staticEntries' => $staticEntries,
        ];

        // Safe to create the default entries?
        if ($createDefaultEntries && count($value) < $this->minEntries) {
            // @link https://github.com/craftcms/cms/issues/12973
            // for fields with minEntries set Craft.MatrixInput.addEntry() is called before new Craft.ElementEditor(),
            // so when we get our initialSerializedValue() for the ElementEditor,
            // the entry is already there which means the field is reported as not changed since the init
            // and so not passed to PHP for save
            DeltaRegistry::setInitialValue($this->handle, null);

            $settings['addDefaultEntries'] = [
                'type' => $entryTypes[0]->handle,
                'count' => $this->minEntries - count($value),
            ];
        }

        $inputHtml = template('_components/fieldtypes/Matrix/input', [
            'id' => $id,
            'field' => $this,
            'name' => $this->handle,
            'entryTypes' => $entryTypes,
            'entries' => $value,
            'static' => $static,
            'staticEntries' => $staticEntries,
            'createButtonLabel' => $this->createButtonLabel(),
            'labelId' => $this->getLabelId(),
            'siteName' => $this->localizedSiteName($element),
            'forms' => collect($value)->mapWithKeys(fn(Entry $entry): array => [
                $entry->uid => $this->blockFormVariables($entry, $static),
            ])->all(),
        ]);

        // The `<craft-matrix-input>` element (resources/js/modules/matrix)
        // boots the MatrixInput controller from these attributes, replacing the
        // imperative `new Craft.MatrixInput(...)` boot script. The attribute
        // values are written fully namespaced, since the outer namespacing pass
        // only rewrites name/id-style attributes.
        return Html::tag('craft-matrix-input', $inputHtml, [
            'entry-types' => Json::encode($entryTypeInfo),
            'input-name-prefix' => InputNamespace::namespaceInputName($this->handle),
            'settings' => Json::encode($settings),
        ]);
    }

    /** @param EntryQuery<Entry>|ElementCollection<int,Entry>|null $value */
    private function nestedElementManagerHtml(EntryQuery|ElementCollection|null $value, ?ElementInterface $owner, bool $static = false): string
    {
        $config = $this->nestedElementManagerConfig($value, $owner, $static);

        return $this->viewMode === self::VIEW_MODE_INDEX
            ? $this->entryManager()->getIndexHtml($owner, $config)
            : $this->entryManager()->getCardsHtml($owner, $config);
    }
}
