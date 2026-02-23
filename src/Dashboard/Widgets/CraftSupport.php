<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\web\Application;
use craft\web\assets\craftsupport\CraftSupportAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Support\PHP;
use Illuminate\Support\Facades\Auth;
use Override;

use function CraftCms\Cms\normalizeVersion;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

final class CraftSupport extends Widget
{
    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Plugins $plugins,
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

        /** @var Application $craft */
        $craft = app('Craft');

        $view = $craft->getView();
        $assetBundle = $view->registerAssetBundle(CraftSupportAsset::class);

        $cmsVersion = $craft->getVersion();
        $cmsMajorVersion = (int) $cmsVersion;

        $pluginVersions = [];
        foreach ($this->plugins->getAllPlugins() as $plugin) {
            $pluginVersions[] = sprintf('- %s %s', $plugin->name, $plugin->version);
        }

        $db = $craft->getDb();
        $dbDriver = $db->getDriverLabel();

        $imagesService = $craft->getImages();
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

        AssetRegistry::jsWithVars(fn ($id, $settings) => <<<JS
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
                    'db' => sprintf('%s %s', $dbDriver, normalizeVersion($db->getSchema()->getServerVersion())),
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
            'bundleUrl' => $assetBundle->baseUrl,
        ]);
    }
}
