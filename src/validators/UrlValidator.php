<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use yii\validators\UrlValidator as YiiUrlValidator;

/**
 * Class UrlValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UrlValidator extends YiiUrlValidator
{
    /**
     * @since 3.6.0
     */
    const URL_PATTERN = '^(?:(?:{schemes}:)?\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)?|\/)[^\s]*$';

    /**
     * @var bool Whether the value can begin with an alias
     * @deprecated
     */
    public $allowAlias = false;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Override the $pattern regex so that a TLD is not required, and the protocol may be relative.
        if (!isset($config['pattern'])) {
            $config['pattern'] = '/' . self::URL_PATTERN . '/i';
        }

        // Enable support for validating international domain names if the intl extension is available.
        if (!isset($config['enableIDN']) && function_exists('idn_to_ascii') && defined('INTL_IDNA_VARIANT_UTS46')) {
            $config['enableIDN'] = true;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value)
    {
        if ($this->allowAlias && strncmp($value, '@', 1) === 0) {
            $value = Craft::getAlias($value);

            // Prevent validateAttribute() from prepending a default scheme if the alias is missing one
            $this->defaultScheme = null;
        }

        // Add support for protocol-relative URLs
        if ($this->defaultScheme !== null && strpos($value, '/') === 0) {
            $this->defaultScheme = null;
        }

        return parent::validateValue($value);
    }
}
