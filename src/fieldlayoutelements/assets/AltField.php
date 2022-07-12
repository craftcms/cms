<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\assets;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\fieldlayoutelements\TextareaField;

/**
 * AltField represents an Alternative Text field that can be included within a volume’s field layout designer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AltField extends TextareaField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'alt';

    /**
     * @inheritdoc
     */
    public bool $translatable = false;

    /**
     * @inheritdoc
     */
    public bool $requirable = true;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        unset(
            $config['attribute'],
            $config['autofocus'],
            $config['mandatory'],
            $config['maxlength'],
            $config['requirable'],
            $config['translatable'],
        );

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset(
            $fields['autofocus'],
            $fields['mandatory'],
            $fields['maxlength'],
            $fields['translatable'],
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Alternative Text');
    }

    /**
     * @inheritdoc
     */
    protected function translationDescription(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Field::TRANSLATION_METHOD_SITE;
    }
}
