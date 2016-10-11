<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

use Craft;
use craft\app\helpers\Url;

/**
 * Widget is the base class for classes representing dashboard widgets in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Widget extends SavableComponent implements WidgetInterface
{
    // Traits
    // =========================================================================

    use WidgetTrait;

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function isSelectable()
    {
        return (static::allowMultipleInstances() || !Craft::$app->getDashboard()->doesUserHaveWidget(static::class));
    }

    /**
     * Returns whether the widget can be selected more than once.
     *
     * @return boolean Whether the widget can be selected more than once
     */
    protected static function allowMultipleInstances()
    {
        return true;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['type'], 'required'],
            [['type'], 'string', 'max' => 150],
        ];

        // Only validate the ID if it's not a new widget
        if ($this->id !== null && strncmp($this->id, 'new', 3) !== 0) {
            $rules[] = [['id'], 'number', 'integerOnly' => true];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        // Default to the widget's display name
        return static::displayName();
    }

    /**
     * @inheritdoc
     */
    public function getIconPath()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        return '<div style="margin: 0 -30px -30px;">'.
        '<img style="display: block; width: 100%;" src="'.Url::getResourceUrl('images/prg.jpg').'">'.
        '</div>';
    }

    /**
     * @inheritdoc
     */
    public function getMaxColspan()
    {
        return null;
    }
}
