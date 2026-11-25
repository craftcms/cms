<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Cp\Html;

use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\View\TemplateMode;
use Override;

use function CraftCms\Cms\template;

/**
 * Renders menus as the legacy disclosure menu markup, which the adapter's
 * jQuery code and plugins built on it boot and reach into.
 *
 * @internal
 */
readonly class LegacyMenuHtml extends MenuHtml
{
    #[Override]
    public function disclosureMenu(array $items, array $config = []): string
    {
        $config = $this->disclosureMenuConfig($items, $config);

        if ($config === null) {
            return '';
        }

        return template('yii2-adapter/_includes/disclosuremenu', $config, templateMode: TemplateMode::Cp);
    }
}
