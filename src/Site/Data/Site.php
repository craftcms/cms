<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Data;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Shared\Rules\HandleRule;
use CraftCms\Cms\Shared\Rules\LanguageRule;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Translation\Locale;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Unique;
use RuntimeException;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Dto;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Stringable;

use function CraftCms\Cms\t;

final class Site extends Dto implements Chippable, Stringable
{
    public function __construct(
        private string $name,
        #[Rule(new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']))]
        public string $handle,
        private string $language,
        #[MapInputName('siteId')]
        public ?int $id = null,
        #[MapInputName('group')]
        public ?int $groupId = null,
        #[Url] private ?string $baseUrl = null,
        public ?bool $primary = false {
            get => (bool) $this->primary;
        },
        public bool $hasUrls = true,
        public int $sortOrder = 1,
        public ?string $uid = null,
        #[WithCast(DateTimeInterfaceCast::class, format: ['Y-m-d H:i:s'], type: Carbon::class)]
        public ?DateTimeInterface $dateCreated = null,
        #[WithCast(DateTimeInterfaceCast::class, format: ['Y-m-d H:i:s'], type: Carbon::class)]
        public ?DateTimeInterface $dateUpdated = null,
        private bool|string $enabled = true
    )
    {
    }

    public static function get(int|string $id): ?static
    {
        return Sites::getSiteById($id);
    }

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'language' => [
                'required',
                new LanguageRule(false),
            ],
            'handle' => array_filter([
                'required',
                'string',
                new HandleRule(['id', 'dateCreated', 'dateUpdated', 'uid', 'title']),
                Info::isInstalled() ? new Unique(Table::SITES, 'handle')->ignore($context?->payload['id'] ?? null) : null,
            ]),
            'name' => array_filter([
                'required',
                'string',
                Info::isInstalled() ? new Unique(Table::SITES, 'name')->ignore($context?->payload['id'] ?? null) : null,
            ]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getUiLabel(): string
    {
        return t($this->getName(), category: 'site');
    }

    /**
     * @param  bool  $parse  Whether to parse the name for an environment variable
     */
    public function getName(bool $parse = true): string
    {
        return ($parse ? Env::parse($this->name) : $this->name) ?? '';
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the site’s base URL.
     *
     * @param  bool  $parse  Whether to parse the name for an alias or environment variable
     */
    public function getBaseUrl(bool $parse = true): ?string
    {
        if (! $this->baseUrl) {
            return null;
        }

        if (! $parse) {
            return $this->baseUrl;
        }

        $parsed = Env::parse($this->baseUrl);

        return $parsed ? rtrim($parsed, '/').'/' : null;
    }

    public function setBaseUrl(?string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getEnabled(bool $parse = true): bool|string
    {
        if ($this->primary) {
            return true;
        }

        if ($parse) {
            return Env::parseBoolean($this->enabled) ?? true;
        }

        return $this->enabled;
    }

    public function setEnabled(bool|string $name): void
    {
        $this->enabled = $name;
    }

    /**
     * Returns the site’s language.
     *
     * @param  bool  $parse  Whether to parse the language for an environment variable
     */
    public function getLanguage(bool $parse = true): string
    {
        return ($parse ? Env::parse($this->language) : $this->language) ?? '';
    }

    /**
     * Sets the site’s language.
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * Use the translated group name as the string representation.
     */
    public function __toString(): string
    {
        return $this->getUiLabel() ?: self::class;
    }

    /**
     * Returns the site's group
     *
     * @throws RuntimeException if [[groupId]] is missing or invalid
     */
    public function getGroup(): SiteGroup
    {
        if (! isset($this->groupId)) {
            throw new RuntimeException('Site is missing its group ID');
        }

        if (($group = SiteGroups::getGroupById($this->groupId)) === null) {
            throw new RuntimeException('Invalid site group ID: '.$this->groupId);
        }

        return $group;
    }

    public function getLocale(): Locale
    {
        if ($this->language === app()->getLocale()) {
            return I18N::getLocale();
        }

        return I18N::getLocaleById($this->language);
    }

    public function getConfig(): array
    {
        return [
            'siteGroup' => $this->getGroup()->uid,
            'name' => $this->name,
            'handle' => $this->handle,
            'language' => $this->getLanguage(false),
            'hasUrls' => $this->hasUrls,
            'baseUrl' => $this->baseUrl ?: null,
            'sortOrder' => $this->sortOrder,
            'primary' => $this->primary,
            'enabled' => $this->getEnabled(false),
        ];
    }
}
