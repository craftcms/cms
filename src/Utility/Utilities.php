<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Utility\Utilities\ProjectConfig as ProjectConfigUtility;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

use function CraftCms\Cms\currentUser;

#[Singleton]
readonly class Utilities
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private UtilityTypes $utilityTypes,
    ) {}

    /**
     * Returns all available utility type classes.
     *
     * @return Collection<int, class-string<Utility>>
     */
    public function getAllUtilityTypes(): Collection
    {
        return $this->utilityTypes->types();
    }

    /**
     * Returns all utility type classes that the user has permission to use.
     *
     * @return Collection<int, class-string<Utility>>
     */
    public function getAuthorizedUtilityTypes(): Collection
    {
        return Collection::make($this->getAllUtilityTypes())
            ->filter(fn (string $class) => $this->checkAuthorization($class));
    }

    /**
     * Returns whether the current user is authorized to use a given utility.
     *
     * @param  class-string<Utility>  $class  The utility class
     */
    public function checkAuthorization(string $class): bool
    {
        $user = currentUser();

        // The Project Config utility is for admins only!
        if ($class === ProjectConfigUtility::class && ! $user?->isAdmin()) {
            return false;
        }

        $utilityId = $class::id();

        if (! $user?->can("utility:$utilityId")) {
            return false;
        }

        // Make sure the utility isn't disabled
        if (in_array($utilityId, $this->generalConfig->disabledUtilities)) {
            return false;
        }

        return true;
    }

    /**
     * Returns a utility class by its ID
     *
     * @return class-string<Utility>|null
     */
    public function getUtilityTypeById(string $id): ?string
    {
        return $this->getAllUtilityTypes()
            /** @var class-string<Utility> $class */
            ->first(fn (string $class) => $class::id() === $id);
    }

    /**
     * Retrieves the total badge count for all utilities
     * the current user is authorized to access.
     */
    public function getUtilitiesBadgeCount(): int
    {
        return $this->getAuthorizedUtilityTypes()
            /** @var class-string<Utility> $class */
            ->sum(fn (string $class) => $class::badgeCount());
    }
}
