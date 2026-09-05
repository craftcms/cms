<?php

declare(strict_types=1);

namespace CraftCms\Cms\Dashboard\Widgets;

use CraftCms\Cms\Update\Updates as UpdatesService;
use Override;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

class Updates extends Widget
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly UpdatesService $updates,
        array $config = []
    ) {
        parent::__construct($config);
    }

    #[Override]
    public static function displayName(): string
    {
        return t('Updates');
    }

    #[Override]
    public static function isSelectable(): bool
    {
        // Gotta have update permission to get this widget
        return parent::isSelectable() && currentUser()->can('performUpdates');
    }

    #[Override]
    protected static function allowMultipleInstances(): bool
    {
        return false;
    }

    #[Override]
    public static function icon(): string
    {
        return 'certificate';
    }

    public function component(): ?string
    {
        return 'craft:widget-updates';
    }

    /** @return array{cached: bool, total: int}|null */
    public function props(): ?array
    {
        if (! currentUser()->can('performUpdates')) {
            return null;
        }

        return [
            'cached' => $this->updates->isUpdateInfoCached(),
            'total' => $this->updates->totalAvailableUpdates(),
        ];
    }
}
