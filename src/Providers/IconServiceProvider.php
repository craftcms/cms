<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use Illuminate\Support\ServiceProvider;
use Yiisoft\Aliases\Aliases as YiiAliases;

class IconServiceProvider extends ServiceProvider
{
    public function boot(YiiAliases $aliases): void
    {
        $aliases->set('@icons', '@craftcms/resources/icons');
        $aliases->set('@appicons', '@icons/solid');

        $customIconsPath = '@icons/custom-icons';

        // Icons
        $aliases->set('@appicons/c-debug.svg', "$customIconsPath/c-debug.svg");
        $aliases->set('@appicons/c-outline.svg', "$customIconsPath/c-outline.svg");
        $aliases->set('@appicons/craft-cms.svg', "$customIconsPath/craft-cms.svg");
        $aliases->set('@appicons/craft-partners.svg', "$customIconsPath/craft-partners.svg");
        $aliases->set('@appicons/craft-stack-exchange.svg', "$customIconsPath/craft-stack-exchange.svg");
        $aliases->set('@appicons/default-plugin.svg', "$customIconsPath/default-plugin.svg");

        require $aliases->get('@icons/aliases.php');

        $solidIconsPath = '@icons/solid';

        // Renamed icon aliases
        $aliases->set('@appicons/alert.svg', "$solidIconsPath/triangle-exclamation.svg");
        $aliases->set('@appicons/broken-image', "$solidIconsPath/image-slash.svg");
        $aliases->set('@appicons/buoey.svg', "$solidIconsPath/life-ring.svg");
        $aliases->set('@appicons/draft.svg', "$solidIconsPath/scribble.svg");
        $aliases->set('@appicons/entry-types', "$solidIconsPath/files.svg");
        $aliases->set('@appicons/excite.svg', "$solidIconsPath/certificate.svg");
        $aliases->set('@appicons/feed.svg', "$solidIconsPath/rss.svg");
        $aliases->set('@appicons/field.svg', "$solidIconsPath/pen-to-square.svg");
        $aliases->set('@appicons/hash.svg', "$solidIconsPath/hashtag.svg");
        $aliases->set('@appicons/info-circle', "$solidIconsPath/circle-info.svg");
        $aliases->set('@appicons/info-circle.svg', "$solidIconsPath/circle-info.svg");
        $aliases->set('@appicons/info.svg', "$solidIconsPath/circle-info.svg");
        $aliases->set('@appicons/info.svg', "$solidIconsPath/circle-info.svg");
        $aliases->set('@appicons/location.svg', "$solidIconsPath/location-dot.svg");
        $aliases->set('@appicons/photo.svg', "$solidIconsPath/image.svg");
        $aliases->set('@appicons/plugin.svg', "$solidIconsPath/plug.svg");
        $aliases->set('@appicons/routes.svg', "$solidIconsPath/signs-post.svg");
        $aliases->set('@appicons/search.svg', "$solidIconsPath/magnifying-glass.svg");
        $aliases->set('@appicons/shopping-cart', "$solidIconsPath/cart-shopping.svg");
        $aliases->set('@appicons/template.svg', "$solidIconsPath/file-code.svg");
        $aliases->set('@appicons/template.svg', "$solidIconsPath/file-code.svg");
        $aliases->set('@appicons/tip.svg', "$solidIconsPath/lightbulb.svg");
        $aliases->set('@appicons/tools.svg', "$solidIconsPath/screwdriver-wrench.svg");
        $aliases->set('@appicons/tree.svg', "$solidIconsPath/sitemap.svg");
        $aliases->set('@appicons/upgrade.svg', "$solidIconsPath/square-arrow-up.svg");
        $aliases->set('@appicons/wand.svg', "$solidIconsPath/wand-magic-sparkles.svg");
        $aliases->set('@appicons/world.svg', "$solidIconsPath/earth-americas.svg");
    }
}
