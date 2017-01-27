<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\validators;

use Craft;
use yii\validators\UrlValidator as YiiUrlValidator;

/**
 * Class UrlValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UrlValidator extends YiiUrlValidator
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Override the $pattern regex so that a TLD is not required, and the protocol may be relative.
        if (!isset($config['pattern'])) {
            $config['pattern'] = '/^(?:(?:{schemes}:)?\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)?|\/)[^\s]*$/i';
        }

        // Enable support for validating international domain names if the intl extension is available.
        if (!isset($config['enableIDN']) && Craft::$app->getI18n()->getIsIntlLoaded()) {
            $config['enableIDN'] = true;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value)
    {
        // Add support for protocol-relative URLs
        if ($this->defaultScheme !== null && strpos($value, '/') === 0) {
            $this->defaultScheme = null;
        }

        return parent::validateValue($value);
    }
}
