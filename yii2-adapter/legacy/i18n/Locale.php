<?php

namespace craft\i18n;

use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

/**
 * Stores locale info.
 *
 * @property string $displayName The locale’s display name.
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Translation\Locale} instead.
 */
class Locale extends BaseObject
{
    public const ATTR_POSITIVE_PREFIX = \CraftCms\Cms\Translation\Locale::ATTR_POSITIVE_PREFIX;
    public const ATTR_POSITIVE_SUFFIX = \CraftCms\Cms\Translation\Locale::ATTR_POSITIVE_SUFFIX;
    public const ATTR_NEGATIVE_PREFIX = \CraftCms\Cms\Translation\Locale::ATTR_NEGATIVE_PREFIX;
    public const ATTR_NEGATIVE_SUFFIX = \CraftCms\Cms\Translation\Locale::ATTR_NEGATIVE_SUFFIX;
    public const ATTR_PADDING_CHARACTER = \CraftCms\Cms\Translation\Locale::ATTR_PADDING_CHARACTER;
    public const ATTR_CURRENCY_CODE = \CraftCms\Cms\Translation\Locale::ATTR_CURRENCY_CODE;
    public const ATTR_DEFAULT_RULESET = \CraftCms\Cms\Translation\Locale::ATTR_DEFAULT_RULESET;
    public const ATTR_PUBLIC_RULESETS = \CraftCms\Cms\Translation\Locale::ATTR_PUBLIC_RULESETS;
    public const STYLE_DECIMAL = \CraftCms\Cms\Translation\Locale::STYLE_DECIMAL;
    public const STYLE_CURRENCY = \CraftCms\Cms\Translation\Locale::STYLE_CURRENCY;
    public const STYLE_PERCENT = \CraftCms\Cms\Translation\Locale::STYLE_PERCENT;
    public const STYLE_SCIENTIFIC = \CraftCms\Cms\Translation\Locale::STYLE_SCIENTIFIC;
    public const SYMBOL_DECIMAL_SEPARATOR = \CraftCms\Cms\Translation\Locale::SYMBOL_DECIMAL_SEPARATOR;
    public const SYMBOL_GROUPING_SEPARATOR = \CraftCms\Cms\Translation\Locale::SYMBOL_GROUPING_SEPARATOR;
    public const SYMBOL_PATTERN_SEPARATOR = \CraftCms\Cms\Translation\Locale::SYMBOL_PATTERN_SEPARATOR;
    public const SYMBOL_PERCENT = \CraftCms\Cms\Translation\Locale::SYMBOL_PERCENT;
    public const SYMBOL_ZERO_DIGIT = \CraftCms\Cms\Translation\Locale::SYMBOL_ZERO_DIGIT;
    public const SYMBOL_DIGIT = \CraftCms\Cms\Translation\Locale::SYMBOL_DIGIT;
    public const SYMBOL_MINUS_SIGN = \CraftCms\Cms\Translation\Locale::SYMBOL_MINUS_SIGN;
    public const SYMBOL_PLUS_SIGN = \CraftCms\Cms\Translation\Locale::SYMBOL_PLUS_SIGN;
    public const SYMBOL_CURRENCY = \CraftCms\Cms\Translation\Locale::SYMBOL_CURRENCY;
    public const SYMBOL_INTL_CURRENCY = \CraftCms\Cms\Translation\Locale::SYMBOL_INTL_CURRENCY;
    public const SYMBOL_MONETARY_SEPARATOR = \CraftCms\Cms\Translation\Locale::SYMBOL_MONETARY_SEPARATOR;
    public const SYMBOL_EXPONENTIAL = \CraftCms\Cms\Translation\Locale::SYMBOL_EXPONENTIAL;
    public const SYMBOL_PERMILL = \CraftCms\Cms\Translation\Locale::SYMBOL_PERMILL;
    public const SYMBOL_PAD_ESCAPE = \CraftCms\Cms\Translation\Locale::SYMBOL_PAD_ESCAPE;
    public const SYMBOL_INFINITY = \CraftCms\Cms\Translation\Locale::SYMBOL_INFINITY;
    public const SYMBOL_NAN = \CraftCms\Cms\Translation\Locale::SYMBOL_NAN;
    public const SYMBOL_SIGNIFICANT_DIGIT = \CraftCms\Cms\Translation\Locale::SYMBOL_SIGNIFICANT_DIGIT;
    public const SYMBOL_MONETARY_GROUPING_SEPARATOR = \CraftCms\Cms\Translation\Locale::SYMBOL_MONETARY_GROUPING_SEPARATOR;
    public const LENGTH_ABBREVIATED = \CraftCms\Cms\Translation\Locale::LENGTH_ABBREVIATED;
    public const LENGTH_SHORT = \CraftCms\Cms\Translation\Locale::LENGTH_SHORT;
    public const LENGTH_MEDIUM = \CraftCms\Cms\Translation\Locale::LENGTH_MEDIUM;
    public const LENGTH_LONG = \CraftCms\Cms\Translation\Locale::LENGTH_LONG;
    public const LENGTH_FULL = \CraftCms\Cms\Translation\Locale::LENGTH_FULL;
    public const FORMAT_ICU = \CraftCms\Cms\Translation\Locale::FORMAT_ICU;
    public const FORMAT_PHP = \CraftCms\Cms\Translation\Locale::FORMAT_PHP;
    public const FORMAT_JUI = \CraftCms\Cms\Translation\Locale::FORMAT_JUI;
    public const FORMAT_HUMAN = \CraftCms\Cms\Translation\Locale::FORMAT_HUMAN;

    /**
     * @var string|null The locale ID.
     */
    public ?string $id = null;

    /**
     * @var string|null The original locale ID, if this is an alias.
     * @since 5.0.0
     */
    public ?string $aliasOf = null;

    /**
     * @var string|null The locale’s custom display name.
     * @see getDisplayName()
     * @see setDisplayName()
     */
    private ?string $_displayName = null;

    /**
     * @var Formatter|null The locale's formatter.
     */
    private ?Formatter $_formatter = null;

    private ?\CraftCms\Cms\Translation\Formatter $newFormatter = null;

    private \CraftCms\Cms\Translation\Locale $newLocale;

    /**
     * Constructor.
     *
     * @param string $id The locale ID.
     * @param array $config Name-value pairs that will be used to initialize the object properties.
     *
     * @throws InvalidArgumentException If $id is an unsupported locale.
     */
    public function __construct(string $id, array $config = [])
    {
        if (str_contains($id, '_')) {
            $id = str_replace('_', '-', $id);
        }

        $this->id = $id;

        parent::__construct($config);

        $this->newLocale = new \CraftCms\Cms\Translation\Locale(
            id: $id,
            aliasOf: $this->aliasOf,
            displayName: $this->_displayName,
        );
    }

    public function __call($name, $params)
    {
        return $this->newLocale->$name(...$params);
    }

    public function __get($name)
    {
        return $this->newLocale->$name;
    }

    public function __toString(): string
    {
        return $this->newLocale->__toString();
    }

    public static function fromNewLocale(\CraftCms\Cms\Translation\Locale $locale): self
    {
        $legacy = new self($locale->id);

        $legacy->aliasOf = $locale->aliasOf;
        $legacy->_displayName = $locale->displayName;
        $legacy->newFormatter = $locale->getFormatter();

        return $legacy;
    }

    public function getFormatter(): Formatter
    {
        if (isset($this->_formatter)) {
            return $this->_formatter;
        }

        $this->_formatter = \Craft::createObject([
            'class' => Formatter::class,
            'locale' => $this->aliasOf ?? $this->id,
            'sizeFormatBase' => $this->newFormatter->sizeFormatBase,
            'dateTimeFormats' => $this->newFormatter->dateTimeFormats,
        ]);

        return $this->_formatter;
    }
}
