<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Data\PermissionGroup;

use function CraftCms\Cms\template;

class PermissionTree extends Control
{
    /** @var list<array<string, mixed>> */
    private array $groups = [];

    /** @var list<string> */
    private array $lockedPermissions = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $html = template('_includes/permissions', [
            'permissions' => $control->props['groups'],
            'groupPermissions' => $control->props['lockedPermissions'],
            'selectedPermissions' => is_array($value) ? array_map(strval(...), array_values($value)) : [],
            'permissionName' => $attributes['name'],
            'disabled' => $attributes['name'] === null,
            'subject' => null,
        ]);

        return Html::tag('div', $html, [
            'id' => $attributes['id'],
            'role' => 'group',
            'aria' => [
                'invalid' => $attributes['aria']['invalid'] ?? null,
                'required' => $attributes['required'] ? 'true' : null,
            ],
        ]);
    }

    public function component(): string
    {
        return 'craft:permission-tree';
    }

    /** @param iterable<PermissionGroup> $groups */
    public function groups(iterable $groups): static
    {
        $this->groups = [];

        foreach ($groups as $group) {
            $this->groups[] = $group->toArray();
        }

        return $this;
    }

    /** @param list<string> $permissions */
    public function lockedPermissions(array $permissions): static
    {
        $this->lockedPermissions = array_values($permissions);

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            'groups' => $this->groups,
            'lockedPermissions' => $this->lockedPermissions,
        ];
    }
}
