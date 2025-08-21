<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\findreplace\FindReplaceAsset;
use CraftCms\Cms\Utility\Utility;

/**
 * FindAndReplace represents a FindAndReplace dashboard widget.
 *

 * @since 6.0.0
 */
final class FindAndReplace extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Find and Replace');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'find-replace';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'wand-magic-sparkles';
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(FindReplaceAsset::class);
        $view->registerJs('new Craft.FindAndReplaceUtility(\'find-replace\');');

        return $view->renderTemplate('_components/utilities/FindAndReplace.twig');
    }
}
