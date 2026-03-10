<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\entries;

use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Entry;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use yii\base\InvalidArgumentException;

/**
 * EntryTitleField represents a Title field that can be included within an entry type’s field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class EntryTitleField extends TitleField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = false;

    /**
     * @inheritdoc
     */
    public bool $requirable = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $this->required = ArrayHelper::remove($config, 'required', $this->required);
        unset($config['requirable']);
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['requirable']);
        $fields['required'] = 'required';
        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function translatable(?ElementInterface $element = null, bool $static = false): bool
    {
        if (!$element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', self::class));
        }

        return $element->getType()->titleTranslationMethod !== Field::TRANSLATION_METHOD_NONE;
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Entry) {
            throw new InvalidArgumentException(sprintf('%s can only be used in entry field layouts.', self::class));
        }

        return ElementHelper::translationDescription($element->getType()->titleTranslationMethod);
    }

    /**
     * @inheritdoc
     */
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
            return Cp::textareaHtml([
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
