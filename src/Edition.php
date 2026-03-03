<?php

declare(strict_types=1);

namespace CraftCms\Cms;

use CraftCms\Cms\Edition\Events\EditionChanged;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;

/**
 * Edition defines all available Craft CMS editions
 */
enum Edition: int implements Arrayable
{
    case Solo = 0;
    case Team = 1;
    case Pro = 2;
    case Enterprise = 3;

    public static function fromHandle(string $handle): self
    {
        foreach (self::cases() as $case) {
            if ($case->handle() === $handle) {
                return $case;
            }
        }
        throw new InvalidArgumentException("Invalid Craft CMS edition handle: $handle");
    }

    public function handle(): string
    {
        return strtolower($this->name);
    }

    /**
     * Get the installed Craft CMS edition.
     */
    public static function get(): self
    {
        if ($edition = Context::getHidden(self::class)) {
            return $edition;
        }

        $edition = Env::get('CRAFT_EDITION', ProjectConfig::get('system.edition'));
        $edition = $edition ? self::fromHandle($edition) : self::Solo;

        Context::addHidden(self::class, $edition);

        return $edition;
    }

    /**
     * Returns the edition Craft is actually licensed to run in.
     */
    public static function getLicensed(): ?self
    {
        $licenseInfo = Cache::get(License::CACHE_KEY_LICENSE_INFO, []);

        if (! isset($licenseInfo['craft']['edition'])) {
            return null;
        }

        return self::fromHandle($licenseInfo['craft']['edition']);
    }

    public static function isWrong(): bool
    {
        $licensedEdition = self::getLicensed();

        return
            $licensedEdition !== null &&
            $licensedEdition !== self::get() &&
            ! self::canTest();
    }

    /**
     * Sets the installed Craft CMS edition.
     */
    public static function set(Edition|int $edition): void
    {
        if (is_int($edition)) {
            $edition = self::from($edition);
        }

        $oldEdition = self::get();

        if ($oldEdition === $edition) {
            return;
        }

        ProjectConfig::set('system.edition', $edition->handle(), 'Craft CMS edition change');

        Context::addHidden(self::class, $edition);

        event(new EditionChanged($oldEdition, $edition));
    }

    /** @internal */
    public static function canTest(): bool
    {
        if (Env::normalizeBooleanValue(Env::get('CRAFT_NO_TRIALS'))) {
            return false;
        }

        $cacheKey = sprintf('editionTestableDomain@%s', Request::host());

        if (! Cache::has($cacheKey)) {
            // err on the side of allowing it
            return true;
        }

        return (bool) Cache::get($cacheKey);
    }

    public static function canUpgrade(): bool
    {
        if (! Auth::user()?->isAdmin()) {
            return false;
        }

        if (! Cms::config()->allowAdminChanges) {
            return false;
        }

        $licensedEdition = self::getLicensed();

        return
            (self::get()->value < self::Pro->value) ||
            ($licensedEdition !== null && $licensedEdition->value < self::Pro->value);
    }

    public static function isAtLeast(Edition|int|string $edition, bool $orBetter = true): bool
    {
        try {
            self::require($edition, $orBetter);

            return true;
        } catch (WrongEditionException) {
            return false;
        }
    }

    public static function require(Edition|int|string $edition, bool $orBetter = true): void
    {
        if (is_int($edition)) {
            $edition = self::from($edition);
        } elseif (is_string($edition)) {
            $edition = self::fromHandle($edition);
        }

        if (! Cms::isInstalled()) {
            return;
        }

        if (ProjectConfig::isApplyingExternalChanges()) {
            return;
        }

        if (! match ($orBetter) {
            true => self::get()->value >= $edition->value,
            false => self::get() === $edition
        }) {
            throw new WrongEditionException("Craft $edition->name is required for this.");
        }
    }

    public function registersFrontendUserRoutes(): bool
    {
        return $this->value >= self::Pro->value;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'handle' => $this->handle(),
        ];
    }
}
