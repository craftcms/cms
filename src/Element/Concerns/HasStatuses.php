<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Support\Attributes\Importable;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;

use function CraftCms\Cms\t;

/**
 * Provides status functionality for elements.
 *
 * This trait handles methods related to element status, including
 * enabled/disabled state per site and overall status determination.
 *
 * @property bool $enabledForSite Whether the element is enabled for this site
 * @property string|null $status The element’s status
 *
 * @internal
 */
trait HasStatuses
{
    public const string STATUS_ENABLED = 'enabled';

    public const string STATUS_DISABLED = 'disabled';

    public const string STATUS_ARCHIVED = 'archived';

    public const string STATUS_DRAFT = 'draft';

    /**
     * @var bool Whether the element is enabled
     */
    #[AllowedInSandbox]
    #[Importable('enabled', 'Enabled', canBeMatchCriteria: false, canBeCleared: false)]
    public bool $enabled = true;

    /**
     * @var bool Whether the element is archived
     */
    #[AllowedInSandbox]
    public bool $archived = false;

    /**
     * @var bool|bool[]
     *
     * @see getEnabledForSite()
     * @see setEnabledForSite()
     */
    private array|bool $_enabledForSite = true;

    /**
     * Returns whether the element is enabled for the current site, or a given site ID.
     *
     * @param  int|null  $siteId  The site ID to check. Defaults to the current site.
     */
    public function getEnabledForSite(?int $siteId = null): ?bool
    {
        $siteId ??= $this->siteId;

        if (is_array($this->_enabledForSite)) {
            return $this->_enabledForSite[$siteId] ?? ($siteId === $this->siteId ? true : null);
        }

        if ($siteId === $this->siteId) {
            return $this->_enabledForSite;
        }

        return null;
    }

    /**
     * Sets whether the element is enabled for the current site.
     *
     * @param  array<int,bool>|bool|int  $enabledForSite  Whether the element is enabled for the current site,
     *                                                    or an array of site ID => enabled status pairs.
     */
    public function setEnabledForSite(array|bool|int $enabledForSite): void
    {
        /** This gets retrieved as an int from the database in some cases */
        if (is_int($enabledForSite)) {
            $enabledForSite = (bool) $enabledForSite;
        }

        $this->_enabledForSite = is_array($enabledForSite)
            ? array_map(boolval(...), $enabledForSite)
            : $enabledForSite;
    }

    /**
     * Returns the element's status.
     *
     * @return string|null The element's status, or null if not applicable.
     */
    public function getStatus(): ?string
    {
        if ($this->getIsDraft() && ! $this->isProvisionalDraft) {
            return self::STATUS_DRAFT;
        }

        if ($this->archived) {
            return self::STATUS_ARCHIVED;
        }

        if (! $this->enabled || ! $this->getEnabledForSite()) {
            return self::STATUS_DISABLED;
        }

        return self::STATUS_ENABLED;
    }

    public static function hasStatuses(): bool
    {
        return false;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_ENABLED => t('Enabled'),
            self::STATUS_DISABLED => t('Disabled'),
        ];
    }
}
