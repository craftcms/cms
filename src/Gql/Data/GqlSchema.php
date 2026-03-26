<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Str;
use Illuminate\Validation\Rule;
use Override;
use Stringable;

class GqlSchema extends Component implements Stringable
{
    public ?int $id = null;

    public ?string $name = null;

    public array $scope = [];

    public bool $isPublic = false;

    public ?string $uid = null;

    private array $_cachedPairs = [];

    #[Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255', Rule::unique(Table::GQLSCHEMAS, 'name')->ignore($this->id)],
            'scope' => ['nullable', 'array'],
            'isPublic' => ['boolean'],
            'uid' => ['nullable', 'uuid'],
        ];
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function has(string $name): bool
    {
        return in_array($name, $this->scope, true);
    }

    public function getAllScopePairs(): array
    {
        if ($this->_cachedPairs !== []) {
            return $this->_cachedPairs;
        }

        foreach ($this->scope as $permission) {
            if (! preg_match('/:([\w-]+)$/', (string) $permission, $matches)) {
                continue;
            }

            $action = $matches[1];
            $permission = Str::chopEnd($permission, ':'.$action);
            $parts = explode('.', $permission);

            if (count($parts) === 2) {
                $this->_cachedPairs[$action][$parts[0]][] = $parts[1];

                continue;
            }

            if (count($parts) === 1) {
                $this->_cachedPairs[$action][$parts[0]] = true;
            }
        }

        return $this->_cachedPairs;
    }

    public function getAllScopePairsForAction(string $action = 'read'): array
    {
        return $this->getAllScopePairs()[$action] ?? [];
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'isPublic' => $this->isPublic,
        ];

        if ($this->scope !== []) {
            $config['scope'] = $this->scope;
        }

        return $config;
    }
}
