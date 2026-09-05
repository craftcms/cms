<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Image\Enums\ImageDriver;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\PHP;
use Illuminate\Support\Facades\DB;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\normalizeVersion;
use function CraftCms\Cms\t;

class CraftSupport extends Widget
{
    /** @param array<string, mixed> $config */
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
        return parent::isSelectable() && currentUser()?->isAdmin();
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

    public function component(): ?string
    {
        return 'craft:widget-craft-support';
    }

    /** @return array<string, mixed>|null */
    public function props(): ?array
    {
        // Only admins get the Craft Support widget.
        if (! currentUser()?->isAdmin()) {
            return null;
        }

        $cmsVersion = Cms::VERSION;
        $cmsMajorVersion = (int) $cmsVersion;

        $pluginVersions = [];
        foreach ($this->plugins->getAllPlugins() as $plugin) {
            $pluginVersions[] = sprintf('- %s %s', $plugin->name, $plugin->version);
        }

        $dbDriver = DB::driverLabel();

        $imagesService = $this->images;
        $imageDriver = match ($imagesService->getDriver()) {
            ImageDriver::Gd => 'GD',
            ImageDriver::Imagick => 'Imagick',
            ImageDriver::Vips => 'Vips',
        };

        $body = <<<'EOD'
### Description



### Steps to reproduce

1.

### Expected behavior



### Actual behavior


EOD;

        return [
            'resources' => [
                ['url' => 'https://craftcms.com/partners', 'label' => t('Find an official Craft Partner')],
                ['url' => 'https://craftcms.com/discord', 'label' => t('Meet the Craft community')],
                ['url' => 'https://craftquest.io', 'label' => t('Unlimited video training')],
                ['url' => 'https://craftcms.com/docs/5.x/', 'label' => t('Documentation')],
                ['url' => 'https://craftcms.com/knowledge-base', 'label' => t('Knowledge Base')],
            ],
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
            'showBackupOption' => $this->generalConfig->backupCommand !== false,
            'canContactSupport' => Edition::get()->value >= Edition::Pro->value,
            'email' => in_array(currentUser()->asElement()->email, ['support@pixelandtonic.com', 'support@craftcms.com'], true) ? '' : currentUser()->asElement()->email,
        ];
    }
}
