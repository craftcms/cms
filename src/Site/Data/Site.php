<?php

declare(strict_types=1);

namespace CraftCms\Cms\Site\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Site\Validation\SiteRules;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use DateTimeInterface;
use Override;
use RuntimeException;
use Stringable;

use function CraftCms\Cms\t;

#[Ruleset(SiteRules::class)]
final class Site extends Component implements Chippable, Stringable
{
    private ?string $_baseUrl = null;

    public ?string $baseUrl {
        get => $this->getBaseUrl();
        set {
            $this->setBaseUrl($value);
        }
    }

    private bool|string $_enabled = true;

    public bool|string $enabled {
        get => $this->getEnabled();
        set {
            $this->setEnabled($value);
        }
    }

    private ?string $_name = null;

    public ?string $name {
        get => $this->getName();
        set {
            $this->setName($value);
        }
    }

    #[AllowedInSandbox]
    public ?string $handle = null;

    private ?string $_language = null;

    public ?string $language {
        get => $this->getLanguage();
        set {
            $this->setLanguage($value);
        }
    }

    #[AllowedInSandbox]
    public ?int $id = null;

    public ?int $groupId = null;

    #[AllowedInSandbox]
    public ?bool $primary = false {
        get => (bool) $this->primary;
    }

    #[AllowedInSandbox]
    public bool $hasUrls = true;

    public int $sortOrder = 1;

    public ?string $uid = null;

    public ?DateTimeInterface $dateCreated = null;

    public ?DateTimeInterface $dateUpdated = null;

    public static function get(int|string $id): ?static
    {
        return Sites::getSiteById($id);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUiLabel(): string
    {
        return t($this->getName(), category: 'site');
    }

    /**
     * @param  bool  $parse  Whether to parse the name for an environment variable
     */
    #[AllowedInSandbox]
    public function getName(bool $parse = true): string
    {
        return ($parse ? Env::parse($this->_name) : $this->_name) ?? '';
    }

    public function setName(?string $name): void
    {
        $this->_name = $name ?? '';
    }

    /**
     * Returns the site’s base URL.
     *
     * @param  bool  $parse  Whether to parse the name for an alias or environment variable
     */
    #[AllowedInSandbox]
    public function getBaseUrl(bool $parse = true): ?string
    {
        if (! $this->_baseUrl) {
            return null;
        }

        if (! $parse) {
            return $this->_baseUrl;
        }

        $parsed = Env::parse($this->_baseUrl);

        return $parsed ? rtrim($parsed, '/').'/' : null;
    }

    public function setBaseUrl(?string $baseUrl): void
    {
        $this->_baseUrl = $baseUrl;
    }

    #[AllowedInSandbox]
    public function getEnabled(bool $parse = true): bool|string
    {
        if ($this->primary) {
            return true;
        }

        if ($parse) {
            return Env::parseBoolean($this->_enabled) ?? true;
        }

        return $this->_enabled;
    }

    public function setEnabled(bool|string $name): void
    {
        $this->_enabled = $name;
    }

    /**
     * Returns the site’s language.
     *
     * @param  bool  $parse  Whether to parse the language for an environment variable
     */
    #[AllowedInSandbox]
    public function getLanguage(bool $parse = true): string
    {
        return ($parse ? Env::parse($this->_language) : $this->_language) ?? '';
    }

    /**
     * Sets the site’s language.
     */
    public function setLanguage(?string $language): void
    {
        $this->_language = $language ?? '';
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

    #[AllowedInSandbox]
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

    #[Override]
    public function toArray(array $fields = [], array $expand = [], bool $recursive = true): array
    {
        return array_merge(parent::toArray($fields, $expand, $recursive), [
            'nameRaw' => $this->getName(false),
            'uiLabel' => $this->getUiLabel(),
            'languageRaw' => $this->getLanguage(false),
            'locale' => $this->getLocale(),
            'baseUrlRaw' => $this->getBaseUrl(false),
            'enabledRaw' => $this->getEnabled(false),
            'group' => $this->groupId ? $this->getGroup() : null,
        ]);
    }
}
