<?php

namespace CraftCms\Cms\Dashboard\Widgets;

use Craft;
use craft\helpers\App;
use craft\web\assets\craftsupport\CraftSupportAsset;
use Illuminate\Support\Facades\Auth;

/** @since 6.0.0 */
final class CraftSupport extends Widget
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Craft Support');
    }

    /**
     * {@inheritdoc}
     */
    public static function isSelectable(): bool
    {
        // Only admins get the Craft Support widget.
        return parent::isSelectable() && Auth::user()->isAdmin();
    }

    /**
     * {@inheritdoc}
     */
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): ?string
    {
        return 'life-ring';
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyHtml(): ?string
    {
        // Only admins get the Craft Support widget.
        if (! Auth::user()->isAdmin()) {
            return null;
        }

        /** @var \craft\web\Application $craft */
        $craft = app('Craft');

        $view = $craft->getView();
        $assetBundle = $view->registerAssetBundle(CraftSupportAsset::class);

        $cmsVersion = $craft->getVersion();
        $cmsMajorVersion = (int) $cmsVersion;

        $pluginVersions = [];
        foreach ($craft->getPlugins()->getAllPlugins() as $plugin) {
            $pluginVersions[] = sprintf('- %s %s', $plugin->name, $plugin->getVersion());
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

        $view->registerJsWithVars(fn ($id, $settings) => <<<JS
new Craft.CraftSupportWidget($id, $settings);
JS, [
            $this->id,
            [
                'issueTitlePrefix' => sprintf('[%s.x]: ', $cmsMajorVersion),
                'issueParams' => [
                    'labels' => sprintf('bug,craft%s', $cmsMajorVersion),
                    'template' => sprintf('BUG-REPORT-V%s.yml', $cmsMajorVersion),
                    'body' => $body,
                    'cmsVersion' => sprintf('%s (%s)', $cmsVersion, Craft::$app->edition->name),
                    'phpVersion' => App::phpVersion(),
                    'os' => sprintf('%s %s', PHP_OS, php_uname('r')),
                    'db' => sprintf('%s %s', $dbDriver, App::normalizeVersion($db->getSchema()->getServerVersion())),
                    'imageDriver' => sprintf('%s %s', $imageDriver, $imagesService->getVersion()),
                    'plugins' => implode("\n", $pluginVersions),
                ],
            ],
        ]);

        // Only show the DB backup option if DB backups haven't been disabled
        $showBackupOption = (Craft::$app->getConfig()->getGeneral()->backupCommand !== false);

        return $view->renderTemplate('_components/widgets/CraftSupport/body.twig', [
            'widget' => $this,
            'showBackupOption' => $showBackupOption,
            'bundleUrl' => $assetBundle->baseUrl,
        ]);
    }
}
