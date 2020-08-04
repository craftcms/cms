<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\ArrayableTrait;
use yii\base\BaseObject;

/**
 * FieldLayoutElementInterface defines the common interface to be implemented by field layout element  classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
abstract class FieldLayoutElement extends BaseObject implements FieldLayoutElementInterface
{
    use ArrayableTrait {
        fields as baseFields;
    }

    /**
     * @var int The width (%) of the field
     */
    public $width = 100;

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = $this->baseFields();
        if (!$this->hasCustomWidth()) {
            unset($fields['width']);
        }
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function hasCustomWidth(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function settingsHtml()
    {
        return null;
    }

    /**
     * Returns the element container HTML attributes.
     *
     * @param ElementInterface|null $element The element the form is being rendered for
     * @param bool $static Whether the form should be static (non-interactive)
     * @return array
     */
    protected function containerAttributes(ElementInterface $element = null, bool $static = false): array
    {
        $attributes = [];
        if ($this->hasCustomWidth()) {
            $attributes['class'][] = 'width-' . ($this->width ?? 100);
        }
        return $attributes;
    }
}
