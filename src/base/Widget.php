<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;

/**
 * Widget is the base class for classes representing dashboard widgets in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
    public static function isSelectable(): bool
    {
        return (static::allowMultipleInstances() || !Craft::$app->getDashboard()->doesUserHaveWidget(static::class));
    }

    /**
     * Returns whether the widget can be selected more than once.
     *
     * @return bool Whether the widget can be selected more than once
     */
    protected static function allowMultipleInstances(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function maxColspan()
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [];

        // Only validate the ID if it's not a new widget
        if (!$this->getIsNew()) {
            $rules[] = [['id'], 'number', 'integerOnly' => true];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        // Default to the widget's display name
        return static::displayName();
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        $url = Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/cp/dist', true, 'images/prg.jpg');

        return <<<EOD
<div style="margin: 0 -24px -24px;">
    <img style="display: block; width: 100%; border-radius: 0 0 4px 4px" src="{$url}">
</div>
EOD;
    }
}
