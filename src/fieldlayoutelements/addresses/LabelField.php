<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fieldlayoutelements\addresses;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\ArrayHelper;

/**
 * Class LabelField.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class LabelField extends TitleField
{
    /**
     * @inheritdoc
     */
    public bool $requirable = true;

    /**
     * @inheritdoc
     */
    public bool $translatable = false;

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
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Label');
    }
}
