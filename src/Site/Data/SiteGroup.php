<?php

namespace CraftCms\Cms\Site\Data;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

final class SiteGroup extends Dto
{
    public function __construct(
        public ?int $id = null,

        #[Max(255)]
        public ?string $uid = null,

        #[Required]
        #[Unique(Table::SITEGROUPS, 'name', ignore: new RouteParameterReference('id', nullable: true))]
        public string $name = '',
    ) {}

    public function getName(bool $parse = true): string
    {
        return ($parse ? Env::parse($this->name) : $this->name) ?? '';
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the group's sites.
     *
     * @return Collection<Site>
     */
    public function getSites(): Collection
    {
        return Sites::getSitesByGroupId($this->id);
    }

    /**
     * Returns the group’s site IDs.
     *
     * @return Collection<int>
     */
    public function getSiteIds(): Collection
    {
        return $this->getSites()->pluck('id');
    }

    /**
     * Returns the site group’s config.
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'name' => $this->getName(),
        ];
    }
}
