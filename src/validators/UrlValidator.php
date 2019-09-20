<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use yii\validators\UrlValidator as YiiUrlValidator;

/**
 * Class UrlValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UrlValidator extends YiiUrlValidator
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether the value can begin with an alias
     * @deprecated
     */
    public $allowAlias = false;

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
        if (!isset($config['enableIDN']) && Craft::$app->getI18n()->getIsIntlLoaded() && defined('INTL_IDNA_VARIANT_UTS46')) {
            $config['enableIDN'] = true;
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->allowAlias) {
            Craft::$app->getDeprecator()->log(__CLASS__ . '::allowAlias', __CLASS__ . '::allowAlias has been deprecated. Models should use ' . EnvAttributeParserBehavior::class . ' instead.');
        }
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
