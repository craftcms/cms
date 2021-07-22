<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * FieldTrait implements the common methods and properties for field classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
trait FieldTrait
{
    /**
     * @var int|null The field’s group’s ID
     */
    public ?int $groupId;

    /**
     * @var string|null The field’s name
     */
    public ?string $name;

    /**
     * @var string|null The field’s handle
     */
    public ?string $handle;

    /**
     * @var string|null The field’s context
     */
    public ?string $context;

    /**
     * @var string|null The field’s instructions
     */
    public ?string $instructions;

    /**
     * @var bool Whether the field's values should be registered as search keywords on the elements.
     */
    public bool $searchable = false;

    /**
     * @var string The field’s translation method
     */
    public string $translationMethod = Field::TRANSLATION_METHOD_NONE;

    /**
     * @var string|null The field’s translation key format, if [[translationMethod]] is "custom"
     */
    public ?string $translationKeyFormat;

    /**
     * @var string|null The field’s previous handle
     */
    public ?string $oldHandle;

    /**
     * @var array|null The field’s previous settings
     * @since 3.1.2
     */
    public ?array $oldSettings;

    /**
     * @var string|null The field’s content column prefix
     */
    public ?string $columnPrefix;

    /**
     * @var string|null The field’s content column suffix
     * @since 3.7.0
     */
    public ?string $columnSuffix;

    /**
     * @var string|null The field's UID
     */
    public ?string $uid;

    // These properties are only populated if the field was fetched via a Field Layout
    // -------------------------------------------------------------------------

    /**
     * @var int|null The ID of the field layout that the field was fetched from
     */
    public ?int $layoutId;

    /**
     * @var int|null The tab ID of the field layout that the field was fetched from
     */
    public ?int $tabId;

    /**
     * @var bool|null Whether the field is required in the field layout it was fetched from
     */
    public ?bool $required;

    /**
     * @var int|null The field’s sort position in the field layout it was fetched from
     */
    public ?int $sortOrder;
}
