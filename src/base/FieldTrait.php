<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

/**
 * FieldTrait implements the common methods and properties for field classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait FieldTrait
{
    // Properties
    // =========================================================================

    /**
     * @var integer The field’s group’s ID
     */
    public $groupId;

    /**
     * @var string The field’s name
     */
    public $name;

    /**
     * @var string The field’s handle
     */
    public $handle;

    /**
     * @var string The field’s context
     */
    public $context;

    /**
     * @var string The field’s instructions
     */
    public $instructions;

    /**
     * @var string The field’s translation method
     */
    public $translationMethod = Field::TRANSLATION_METHOD_NONE;

    /**
     * @var string The field’s translation key format, if [[translationMethod]] is "custom"
     */
    public $translationKeyFormat;

    /**
     * @var string The field’s previous handle
     */
    public $oldHandle;

    /**
     * @var string The field’s content column prefix
     */
    public $columnPrefix;

    // These properties are only populated if the field was fetched via a Field Layout
    // -------------------------------------------------------------------------

    /**
     * @var integer The ID of the field layout that the field was fetched from
     */
    public $layoutId;

    /**
     * @var integer The tab ID of the field layout that the field was fetched from
     */
    public $tabId;

    /**
     * @var boolean Whether the field is required in the field layout it was fetched from
     */
    public $required;

    /**
     * @var integer The field’s sort position in the field layout it was fetched from
     */
    public $sortOrder;
}
