<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use CraftCms\Aliases\Aliases;
use Illuminate\Support\ServiceProvider;

final class IconServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Aliases::set('@icons', '@craftcms/resources/icons');
        Aliases::set('@appicons', '@icons/solid');

        $customIconsPath = '@icons/custom-icons';

        // Icons
        Aliases::set('@appicons/c-debug.svg', "$customIconsPath/c-debug.svg");
        Aliases::set('@appicons/c-outline.svg', "$customIconsPath/c-outline.svg");
        Aliases::set('@appicons/craft-cms.svg', "$customIconsPath/craft-cms.svg");
        Aliases::set('@appicons/craft-partners.svg', "$customIconsPath/craft-partners.svg");
        Aliases::set('@appicons/craft-stack-exchange.svg', "$customIconsPath/craft-stack-exchange.svg");
        Aliases::set('@appicons/default-plugin.svg', "$customIconsPath/default-plugin.svg");
        Aliases::set('@appicons/grip-dots.svg', "$customIconsPath/grip-dots.svg");

        require Aliases::get('@icons/aliases.php');

        $solidIconsPath = '@icons/solid';

        // Renamed icon aliases
        Aliases::set('@appicons/alert.svg', "$solidIconsPath/triangle-exclamation.svg");
        Aliases::set('@appicons/broken-image', "$solidIconsPath/image-slash.svg");
        Aliases::set('@appicons/buoey.svg', "$solidIconsPath/life-ring.svg");
        Aliases::set('@appicons/draft.svg', "$solidIconsPath/scribble.svg");
        Aliases::set('@appicons/entry-types', "$solidIconsPath/files.svg");
        Aliases::set('@appicons/excite.svg', "$solidIconsPath/certificate.svg");
        Aliases::set('@appicons/feed.svg', "$solidIconsPath/rss.svg");
        Aliases::set('@appicons/field.svg', "$solidIconsPath/pen-to-square.svg");
        Aliases::set('@appicons/hash.svg', "$solidIconsPath/hashtag.svg");
        Aliases::set('@appicons/info-circle', "$solidIconsPath/circle-info.svg");
        Aliases::set('@appicons/info-circle.svg', "$solidIconsPath/circle-info.svg");
        Aliases::set('@appicons/info.svg', "$solidIconsPath/circle-info.svg");
        Aliases::set('@appicons/info.svg', "$solidIconsPath/circle-info.svg");
        Aliases::set('@appicons/location.svg', "$solidIconsPath/location-dot.svg");
        Aliases::set('@appicons/photo.svg', "$solidIconsPath/image.svg");
        Aliases::set('@appicons/plugin.svg', "$solidIconsPath/plug.svg");
        Aliases::set('@appicons/routes.svg', "$solidIconsPath/signs-post.svg");
        Aliases::set('@appicons/search.svg', "$solidIconsPath/magnifying-glass.svg");
        Aliases::set('@appicons/shopping-cart', "$solidIconsPath/cart-shopping.svg");
        Aliases::set('@appicons/template.svg', "$solidIconsPath/file-code.svg");
        Aliases::set('@appicons/template.svg', "$solidIconsPath/file-code.svg");
        Aliases::set('@appicons/tip.svg', "$solidIconsPath/lightbulb.svg");
        Aliases::set('@appicons/tools.svg', "$solidIconsPath/screwdriver-wrench.svg");
        Aliases::set('@appicons/tree.svg', "$solidIconsPath/sitemap.svg");
        Aliases::set('@appicons/upgrade.svg', "$solidIconsPath/square-arrow-up.svg");
        Aliases::set('@appicons/wand.svg', "$solidIconsPath/wand-magic-sparkles.svg");
        Aliases::set('@appicons/world.svg', "$solidIconsPath/earth-americas.svg");
    }
}
