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
 * Class FullNameField.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FullNameField extends TextField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'fullName';

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
            $config['mandatory'],
            $config['translatable'],
            $config['maxlength'],
            $config['autofocus']
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
            $fields['mandatory'],
            $fields['translatable'],
            $fields['maxlength'],
            $fields['autofocus']
        );
        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('app', 'Full Name');
    }
}
