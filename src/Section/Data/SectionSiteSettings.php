<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Section\Validation\SectionSiteSettingsRules;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use RuntimeException;

#[Ruleset(SectionSiteSettingsRules::class)]
final class SectionSiteSettings extends Component
{
    private ?Section $section = null;

    public ?int $id = null;

    public ?int $sectionId = null;

    public ?int $siteId = null;

    public bool $enabledByDefault = true;

    public bool $hasUrls = false;

    public ?string $uriFormat = null;

    public ?string $template = null;

    /**
     * Returns the section.
     *
     * @throws \RuntimeException if [[sectionId]] is missing or invalid
     */
    public function getSection(): Section
    {
        if (isset($this->section)) {
            return $this->section;
        }

        if (! $this->sectionId) {
            throw new RuntimeException('Section site settings model is missing its section ID');
        }

        if (($this->section = Sections::getSectionById($this->sectionId)) === null) {
            throw new RuntimeException('Invalid section ID: '.$this->sectionId);
        }

        return $this->section;
    }

    /**
     * Sets the section.
     */
    public function setSection(Section $section): void
    {
        $this->section = $section;
    }

    /**
     * Returns the site.
     *
     * @throws RuntimeException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if (! $this->siteId) {
            throw new RuntimeException('Section site settings model is missing its site ID');
        }

        if (($site = Sites::getSiteById($this->siteId)) === null) {
            throw new RuntimeException('Invalid site ID: '.$this->siteId);
        }

        return $site;
    }
}
