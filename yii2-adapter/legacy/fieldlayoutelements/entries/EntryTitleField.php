<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\entries;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Support\Html;
use CraftCms\Yii2Adapter\Form\Enums\LegacyHtmlMode;
use CraftCms\Yii2Adapter\Form\LegacyHtml;
use InvalidArgumentException;
use Override;

/** @deprecated 6.0.0 use \CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField instead. */
class EntryTitleField extends \CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField
{
    #[Override]
    protected function formControl(FieldLayoutElementContext $context): ?Control
    {
        $mode = $context->form->mode === ControlMode::Editable ? $context->mode : $context->form->mode;
        $mode = $this->disabled ? ControlMode::Disabled : ($this->readonly && $mode === ControlMode::Editable ? ControlMode::ReadOnly : $mode);

        $node = app(LegacyHtml::class)->capture(
            path: $this->name ?? $this->attribute(),
            hook: fn(): ?string => $mode === ControlMode::Disabled
                ? Html::disableInputs(fn(): ?string => $this->inputHtml($context->element, true))
                : $this->inputHtml($context->element, $mode !== ControlMode::Editable),
            namespace: LegacyHtml::namespace($context->form->namespace),
            mode: match ($mode) {
                ControlMode::Editable => LegacyHtmlMode::Editable,
                ControlMode::ReadOnly => LegacyHtmlMode::Static,
                ControlMode::Disabled => LegacyHtmlMode::Disabled,
            },
        );

        return $node?->getControl()->deltaGroupAtNamespace()->expandValues();
    }

    #[Override]
    public function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', self::class));
        }

        $entryType = $element->getType();

        if (!$entryType->hasTitleField) {
            return null;
        }

        if ($entryType->allowLineBreaksInTitles) {
            return FormFields::textareaHtml([
                'class' => 'nicetext',
                'id' => $this->id(),
                'describedBy' => $this->describedBy($element, $static),
                'rows' => 2,
                'name' => $this->name ?? $this->attribute(),
                'value' => $this->value($element),
                'maxlength' => $this->maxlength,
                'autofocus' => $this->autofocus,
                'disabled' => $static || $this->disabled,
                'readonly' => $this->readonly,
                'required' => !$static && $this->required,
                'title' => $this->title,
                'placeholder' => $this->placeholder,
            ]);
        }

        return parent::inputHtml($element, $static);
    }
}
