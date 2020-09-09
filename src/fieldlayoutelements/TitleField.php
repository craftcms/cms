<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;

/**
 * TitleField represents a Title field that can be included in field layouts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class TitleField extends StandardTextField
{
    /**
     * @inheritdoc
     */
    public $mandatory = true;

    /**
     * @inheritdoc
     */
    public $attribute = 'title';

    /**
     * @inheritdoc
     */
    public $translatable = true;

    /**
     * @inheritdoc
     */
    public $maxlength = 255;

    /**
     * @inheritdoc
     */
    public $required = true;

    /**
     * @inheritdoc
     */
    public $autofocus = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        unset(
            $config['mandatory'],
            $config['attribute'],
            $config['translatable'],
            $config['maxlength'],
            $config['required'],
            $config['autofocus']
        );

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        unset(
            $fields['mandatory'],
            $fields['attribute'],
            $fields['translatable'],
            $fields['maxlength'],
            $fields['required'],
            $fields['autofocus']
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(ElementInterface $element = null, bool $static = false)
    {
        return Craft::t('app', 'Title');
    }

    /**
     * @inheritdoc
     */
    protected function statusClass(ElementInterface $element = null, bool $static = false)
    {
        if ($element && ($status = $element->getAttributeStatus('title'))) {
            return $status[0];
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function statusLabel(ElementInterface $element = null, bool $static = false)
    {
        if ($element && ($status = $element->getAttributeStatus('title'))) {
            return $status[1];
        }
        return null;
    }
}
