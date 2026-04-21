<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Dashboard\Dashboard;
use function CraftCms\Cms\craftAsset;

/**
 * Widget is the base class for classes representing dashboard widgets in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Extend `\CraftCms\Cms\Dashboard\Widgets\Widget` instead.
 */
abstract class Widget extends SavableComponent implements \CraftCms\Cms\Dashboard\Contracts\WidgetInterface
{
    use WidgetTrait;

    /**
     * Old widgets will get validated by calling the ->validate() method.
     */
    public function getRules(): array
    {
        return [];
    }

    /**
     * Old widgets will get validated by with Yii's rules.
     */
    public function getValidationData(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        return (static::allowMultipleInstances() || !app(Dashboard::class)->doesUserHaveWidget(static::class));
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
    public static function icon(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function maxColspan(): ?int
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Only validate the ID if it’s not a new widget
        if (!$this->getIsNew()) {
            $rules[] = [['id'], 'number', 'integerOnly' => true];
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        // Default to the widget's display name
        return static::displayName();
    }

    /**
     * @inheritdoc
     */
    public function getSubtitle(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        $url = craftAsset('legacy/cp/dist/images/prg.jpg');

        return <<<EOD
<div style="margin: 0 -24px -24px;">
    <img style="display: block; width: 100%; border-radius: 0 0 4px 4px" src="$url">
</div>
EOD;
    }
}
