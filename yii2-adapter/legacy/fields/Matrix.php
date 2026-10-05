<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use craft\events\BulkElementsEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Yii2Adapter\Field\Concerns\LegacyBuiltInField;
use CraftCms\Yii2Adapter\Field\Contracts\LegacyField;
use CraftCms\Yii2Adapter\Field\MatrixEntrySaveCompatibility;
use CraftCms\Yii2Adapter\Form\NestedElementFieldHtml;
use Override;
use RuntimeException;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Matrix} instead.
 */
class Matrix extends \CraftCms\Cms\Field\Matrix implements LegacyField
{
    use LegacyBuiltInField;

    public function afterSaveEntries(BulkElementsEvent $event): void
    {
        app(MatrixEntrySaveCompatibility::class)->rememberCollapsedEntries($event->elements);
    }

    /**
     * @throws RuntimeException
     */
    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $entries = $this->viewMode === self::VIEW_MODE_BLOCKS ? $this->entriesForForm($value) : [];
        $control = parent::formControl(new FieldContext(
            path: ['fields', $this->handle],
            value: $this->viewMode === self::VIEW_MODE_BLOCKS ? new ElementCollection($entries) : $value,
            element: $element,
            mode: $this->legacyInputMode,
            inline: $inline,
        ));

        $errors = [];
        if ($this->viewMode === self::VIEW_MODE_BLOCKS) {
            $messages = $element?->errors()->get($this->handle) ?? [];
            if ($messages !== []) {
                $errors["fields.{$this->handle}"] = $messages;
            }

            $identities = ElementHelper::nestedElementIdentities($entries);
            foreach ($entries as $index => $entry) {
                if ($entry->errors()->isEmpty()) {
                    continue;
                }

                $form = app(FieldLayoutCompiler::class)->compile(
                    $entry->getFieldLayout(),
                    $entry,
                    new FormContext(errors: $entry->errors()->getMessages(), mode: $this->legacyInputMode),
                );
                if ($form->globalErrors !== []) {
                    $path = "fields.{$this->handle}";
                    $errors[$path] = array_merge($errors[$path] ?? [], $form->globalErrors);
                }

                foreach ($form->errors as $error) {
                    $path = ['fields', $this->handle, 'entries', $identities[$index], ...$error['path']];
                    $errors[implode('.', $path)] = $error['messages'];
                }
            }
        }

        return app(NestedElementFieldHtml::class)->render($control, $this->getInputId(), $this->handle, $this->legacyInputMode, $errors);
    }
}
