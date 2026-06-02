<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\fieldlayoutelements\CustomField;
use DateTime;

/**
 * FieldTrait implements the common methods and properties for field classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait FieldTrait
{
    /**
     * @var string|null The field’s name
     */
    public ?string $name = null;

    /**
     * @var string|null The field’s handle
     */
    public ?string $handle = null;

    /**
     * @var string|null The field’s context
     */
    public ?string $context = null;

    /**
     * @var string|null The field’s instructions
     */
    public ?string $instructions = null;

    /**
     * @var bool Whether the field's values should be registered as search keywords on the elements.
     */
    public bool $searchable = false;

    /**
     * @var string|null The `aria-describedby` attribute value that should be set on the focusable input(s).
     * @see FieldInterface::getInputHtml()
     * @since 3.7.24
     */
    public ?string $describedBy = null;

    /**
     * @var string The field’s translation method
     * @phpstan-var Field::TRANSLATION_METHOD_*
     */
    public string $translationMethod = Field::TRANSLATION_METHOD_NONE;

    /**
     * @var string|null The field’s translation key format, if [[translationMethod]] is "custom"
     */
    public ?string $translationKeyFormat = null;

    /**
     * @var string|null The field’s previous handle
     */
    public ?string $oldHandle = null;

    /**
     * @var array|null The field’s previous settings
     * @since 3.1.2
     */
    public ?array $oldSettings = null;

    /**
     * @var string|null The field’s content column suffix
     * @since 3.7.0
     * @deprecated in 5.0.0
     */
    public ?string $columnSuffix = null;

    /**
     * @var string|null The field's UID
     */
    public ?string $uid = null;

    /**
     * @var bool|null Whether the field is required in the field layout it was fetched from
     * @deprecated in 4.1.4. [[\craft\fieldlayoutelements\BaseField::$required]] should be used instead
     */
    public ?bool $required = null;

    /**
     * @var DateTime|null The date that the field was trashed
     * @since 5.3.0
     */
    public ?DateTime $dateDeleted = null;

    /**
     * @var CustomField|null The field layout element
     * @since 5.0.0
     */
    public ?CustomField $layoutElement = null;

    /**
     * @var bool Whether the field is being displayed statically.
     * @since 5.7.0
     */
    public bool $static = false;
}
