<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SiteGroup extends Component
{
    public ?int $id = null;

    public ?string $uid = null;

    private ?string $_name = null;

    public string $name {
        get => $this->getName();
        set {
            $this->setName($value);
        }
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', Rule::exists(Table::SITEGROUPS, 'id')],
            'uid' => ['nullable', 'string', 'uuid'],
            'name' => ['required', 'string', 'max:255', Rule::unique(Table::SITEGROUPS)->ignore($this->id)->withoutTrashed('dateDeleted')],
        ];
    }

    public function getName(bool $parse = true): string
    {
        return ($parse ? Env::parse($this->_name) : $this->_name) ?? '';
    }

    public function setName(string $name): void
    {
        $this->_name = $name;
    }

    /**
     * @return Collection<int, Site>
     */
    public function getSites(): Collection
    {
        return Sites::getSitesByGroupId($this->id);
    }

    /**
     * @return Collection<int, int>
     */
    public function getSiteIds(): Collection
    {
        return $this->getSites()->pluck('id');
    }

    /** @return array<string, string> */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    /** @return array<string, mixed> */
    #[\Override]
    public function toArray(array $fields = [], array $expand = [], bool $recursive = true): array
    {
        return array_merge(parent::toArray($fields, $expand, $recursive), [
            'rawName' => $this->getName(false),
        ]);
    }
}
