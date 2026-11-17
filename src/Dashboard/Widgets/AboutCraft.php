<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Cp\AboutCraft as AboutCraftInfo;
use Override;

use function CraftCms\Cms\t;

/**
 * Shows Craft’s version and edition, any trials of Craft or plugins that are installed, and, for users who can update
 * Craft, whether updates are available, as the command palette’s “About Craft CMS” view does.
 *
 * @since 6.0.0
 */
class AboutCraft extends Widget
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly AboutCraftInfo $about,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    #[Override]
    public static function displayName(): string
    {
        return t('About Craft CMS');
    }

    #[Override]
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    #[Override]
    public static function icon(): string
    {
        return 'custom-icons/craft-cms';
    }

    public function component(): string
    {
        return 'craft:widget-about-craft';
    }

    /** @return array<string, mixed> */
    #[Override]
    public function props(): array
    {
        return $this->about->toArray();
    }
}
