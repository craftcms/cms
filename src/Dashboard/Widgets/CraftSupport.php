<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Cms;
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

/**
 * @since 6.0.0
 */
class CraftSupport extends Widget
{
    /** @param array<string, mixed> $config */
    public function __construct(
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

    public function component(): ?string
    {
        return currentUser()?->isAdmin() ? 'craft:widget-craft-support' : null;
    }

    /** @return array{links: list<array{url: string, label: string, icon: string, iconFamily?: string}>} */
    public function props(): array
    {
        $cmsMajorVersion = (int) Cms::VERSION;

        return [
            'links' => [
                ['url' => sprintf('https://craftcms.com/docs/%s.x/', $cmsMajorVersion), 'label' => t('Documentation'), 'icon' => 'book'],
                ['url' => 'https://craftcms.com/knowledge-base', 'label' => t('Knowledge Base'), 'icon' => 'lightbulb'],
                ['url' => 'https://craftcms.com/contact', 'label' => t('Contact Craft Support'), 'icon' => 'headset'],
                ['url' => $this->bugReportUrl($cmsMajorVersion), 'label' => t('Report a bug on GitHub'), 'icon' => 'bug'],
                ['url' => 'https://github.com/craftcms/cms/discussions/new?category=ideas', 'label' => t('Request a feature on GitHub'), 'icon' => 'github', 'iconFamily' => 'brands'],
                ['url' => 'https://craftcms.com/discord', 'label' => t('Meet the Craft community'), 'icon' => 'discord', 'iconFamily' => 'brands'],
                ['url' => 'https://craftcms.com/partners', 'label' => t('Find an official Craft Partner'), 'icon' => 'handshake'],
            ],
        ];
    }

    /**
     * Returns the URL for a new GitHub issue from the bug report template, with this install’s environment filled in.
     */
    private function bugReportUrl(int $cmsMajorVersion): string
    {
        $pluginVersions = [];
        foreach ($this->plugins->getAllPlugins() as $plugin) {
            $pluginVersions[] = sprintf('- %s %s', $plugin->name, $plugin->version);
        }

        $imageDriver = match ($this->images->getDriver()) {
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

        return 'https://github.com/craftcms/cms/issues/new?'.http_build_query([
            'template' => sprintf('BUG-REPORT-V%s.yml', $cmsMajorVersion),
            'labels' => sprintf('bug,craft%s', $cmsMajorVersion),
            'title' => sprintf('[%s.x]: ', $cmsMajorVersion),
            'body' => $body,
            'cmsVersion' => sprintf('%s (%s)', Cms::VERSION, Edition::get()->name),
            'phpVersion' => PHP::version(),
            'os' => sprintf('%s %s', PHP_OS, php_uname('r')),
            'db' => sprintf('%s %s', DB::driverLabel(), normalizeVersion(DB::getServerVersion())),
            'imageDriver' => sprintf('%s %s', $imageDriver, $this->images->getVersion()),
            'plugins' => implode("\n", $pluginVersions),
        ], encoding_type: PHP_QUERY_RFC3986);
    }
}
