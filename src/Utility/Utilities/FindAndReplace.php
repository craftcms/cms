<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\findreplace\FindReplaceAsset;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Utility\Utility;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * FindAndReplace represents a FindAndReplace dashboard widget.
 */
final class FindAndReplace extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Find and Replace');
    }

    #[Override]
    public static function id(): string
    {
        return 'find-replace';
    }

    #[Override]
    public static function icon(): string
    {
        return 'wand-magic-sparkles';
    }

    #[Override]
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        $view->registerAssetBundle(FindReplaceAsset::class);
        AssetRegistry::js('new Craft.FindAndReplaceUtility(\'find-replace\');');

        return template('_components/utilities/FindAndReplace');
    }
}
