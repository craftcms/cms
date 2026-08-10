<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\Support\Arrayable;

/** PHP counterpart to the `<craft-permission-tree>` web component. */
class PermissionTree extends ViewComponent
{
    use HasDisabled;

    /** @var list<array<string, mixed>> */
    protected array $groups = [];

    /** @var list<string> */
    protected array $permissionKeys = [];

    /** @var list<string> */
    protected array $modelValue = [];

    /** @var list<string> */
    protected array $lockedPermissions = [];

    protected ?string $name = null;

    protected function tagName(): string
    {
        return 'craft-permission-tree';
    }

    /** @param iterable<array<string, mixed>|Arrayable<string, mixed>> $groups */
    public function groups(iterable $groups): static
    {
        $this->groups = [];
        $this->permissionKeys = [];

        foreach ($groups as $group) {
            $group = $group instanceof Arrayable ? $group->toArray() : $group;
            $this->groups[] = $group;
            $this->permissionKeys = [
                ...$this->permissionKeys,
                ...array_map(strval(...), $group['keys']),
            ];
        }

        return $this;
    }

    /** @param list<string> $modelValue */
    public function modelValue(array $modelValue): static
    {
        $this->modelValue = array_values($modelValue);

        return $this;
    }

    /** @param list<string> $lockedPermissions */
    public function lockedPermissions(array $lockedPermissions): static
    {
        $this->lockedPermissions = array_values($lockedPermissions);

        return $this;
    }

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'groups' => Json::encode($this->groups),
            'model-value' => Json::encode($this->modelValue),
            'locked-permissions' => Json::encode($this->lockedPermissions),
            'name' => $this->isDisabled() ? null : $this->name,
            'disabled' => $this->isDisabled(),
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        if ($this->name === null || $this->isDisabled()) {
            return parent::renderSlots();
        }

        $html = Html::hiddenInput($this->name, '', [
            'data-permission-tree-input' => true,
        ]);

        foreach ($this->modelValue as $permission) {
            if (! in_array($permission, $this->permissionKeys, true)) {
                continue;
            }

            $html .= Html::hiddenInput("{$this->name}[]", $permission, [
                'data-permission-tree-input' => true,
            ]);
        }

        return $html.parent::renderSlots();
    }
}
