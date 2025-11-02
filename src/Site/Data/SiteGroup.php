<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Data;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

final class SiteGroup extends Dto
{
    public function __construct(
        #[Exists(Table::SITEGROUPS, 'id')]
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

    /**
     * We override the way errors are returned as the frontend does
     * not accept errors in Laravel's format at this time.
     *
     * @todo: Update frontend
     */
    public static function validate(array|Arrayable $payload): Arrayable|array
    {
        try {
            return parent::validate($payload);
        } catch (ValidationException $e) {
            $errors = array_values(array_map(reset(...), $e->errors()));
            throw ValidationException::withMessages($errors);
        }
    }
}
