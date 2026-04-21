<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\View\LegacyAssets\CraftSupportAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Override;

use function CraftCms\Cms\craftAsset;
use function CraftCms\Cms\normalizeVersion;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

class CraftSupport extends Widget
{
    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Plugins $plugins,
        private readonly Images $images,
        array $config = []
    ) {
        parent::__construct($config);
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Craft Support');
    }

    #[Override]
    public static function isSelectable(): bool
    {
        // Only admins get the Craft Support widget.
        return parent::isSelectable() && Auth::user()?->isAdmin();
    }

    #[Override]
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    #[Override]
    public static function icon(): string
    {
        return 'life-ring';
    }

    #[Override]
    public function getTitle(): ?string
    {
        return null;
    }

    #[Override]
    public function getBodyHtml(): ?string
    {
        // Only admins get the Craft Support widget.
        if (! Auth::user()?->isAdmin()) {
            return null;
        }

        app(InternalAssetRegistry::class)->register(CraftSupportAsset::class);

        $cmsVersion = Cms::VERSION;
        $cmsMajorVersion = (int) $cmsVersion;

        $pluginVersions = [];
        foreach ($this->plugins->getAllPlugins() as $plugin) {
            $pluginVersions[] = sprintf('- %s %s', $plugin->name, $plugin->version);
        }

        $dbDriver = DB::driverLabel();

        $imagesService = $this->images;
        if ($imagesService->getIsGd()) {
            $imageDriver = 'GD';
        } else {
            $imageDriver = 'Imagick';
        }

        $body = <<<'EOD'
### Description



### Steps to reproduce

1.

### Expected behavior



### Actual behavior


EOD;

        HtmlStack::jsWithVars(fn ($id, $settings) => <<<JS
new Craft.CraftSupportWidget($id, $settings)
JS, [
            $this->id,
            [
                'issueTitlePrefix' => sprintf('[%s.x]: ', $cmsMajorVersion),
                'issueParams' => [
                    'labels' => sprintf('bug,craft%s', $cmsMajorVersion),
                    'template' => sprintf('BUG-REPORT-V%s.yml', $cmsMajorVersion),
                    'body' => $body,
                    'cmsVersion' => sprintf('%s (%s)', $cmsVersion, Edition::get()->name),
                    'phpVersion' => PHP::version(),
                    'os' => sprintf('%s %s', PHP_OS, php_uname('r')),
                    'db' => sprintf('%s %s', $dbDriver, normalizeVersion(DB::getServerVersion())),
                    'imageDriver' => sprintf('%s %s', $imageDriver, $imagesService->getVersion()),
                    'plugins' => implode("\n", $pluginVersions),
                ],
            ],
        ]);

        // Only show the DB backup option if DB backups haven't been disabled
        $showBackupOption = $this->generalConfig->backupCommand !== false;

        return template('_components/widgets/CraftSupport/body', [
            'widget' => $this,
            'showBackupOption' => $showBackupOption,
            'bundleUrl' => craftAsset('legacy/craftsupport/dist'),
        ]);
    }
}
