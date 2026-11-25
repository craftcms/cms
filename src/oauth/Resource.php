<?php

namespace craft\oauth;

use Closure;
use craft\elements\User;
use craft\helpers\App;
use yii\base\BaseObject;

/** A protected API's OAuth scopes, permission policy, and canonical URL. @since 5.12.0 */
class Resource extends BaseObject
{
    public string $id;
    /** @var array<string,string> Allowed scope IDs mapped to consent descriptions. */
    public array $scopes = [];
    /** @var string[] Scopes requested when the client omits scope. */
    public array $defaultScopes = [];
    /**
     * @var string[] Scopes that form access levels, ordered from narrowest to broadest. Each level includes the ones
     * before it, and the user chooses one level on the consent screen.
     */
    public array $scopeLevels = [];
    /** @var array<string,string> Consent screen labels for access levels. Levels without a label show their scope ID. */
    public array $scopeLabels = [];
    /**
     * @var Closure|null Returns the scope IDs, out of those given, that a user may be granted.
     * Signature: `function(User $user, string[] $scopes): string[]`
     */
    public ?Closure $userScopes = null;
    public array $permissions = [];
    public string|Closure $resourceUrl;
    public string $consentDescription = '';

    /** Whether the scope IDs form a nonempty subset of this resource's allowed scopes. */
    public function allowsScopes(array $scopes): bool
    {
        if (!$scopes || !array_is_list($scopes)) {
            return false;
        }
        foreach ($scopes as $scope) {
            if (!is_string($scope) || !isset($this->scopes[$scope])) {
                return false;
            }
        }

        return true;
    }

    /**
     * The scope IDs, out of those given, that a user may be granted.
     * @param string[] $scopes
     * @return string[]
     */
    public function scopesForUser(User $user, array $scopes): array
    {
        return $this->userScopes ? array_values(array_intersect($scopes, ($this->userScopes)($user, $scopes))) : $scopes;
    }

    /**
     * The access levels a user may choose between for the requested scopes, narrowest first.
     * @param string[] $requested
     * @return string[]
     */
    public function levelChoices(array $requested, User $user): array
    {
        return $this->scopesForUser($user, array_values(array_intersect($this->scopeLevels, $requested)));
    }

    /**
     * The broadest access level in the given scopes.
     * @param string[] $scopes
     */
    public function highestLevel(array $scopes): ?string
    {
        $levels = array_intersect($this->scopeLevels, $scopes);
        return $levels ? end($levels) : null;
    }

    /**
     * The scope IDs granted by choosing an access level: the level and the narrower ones.
     * @return string[]
     */
    public function grantedLevels(string $level): array
    {
        $index = array_search($level, $this->scopeLevels, true);
        return $index === false ? [] : array_slice($this->scopeLevels, 0, $index + 1);
    }

    /**
     * The scope IDs that satisfy an access level: the level and the broader ones.
     * @return string[]
     */
    public function satisfyingLevels(string $level): array
    {
        $index = array_search($level, $this->scopeLevels, true);
        return $index === false ? [] : array_slice($this->scopeLevels, $index);
    }

    /**
     * Removes the requested access levels that `$level` doesn't grant, keeping scopes that aren't levels.
     * @param string[] $scopes
     * @return string[]
     */
    public function narrowScopes(array $scopes, ?string $level): array
    {
        $granted = $level !== null ? $this->grantedLevels($level) : [];

        return array_values(array_filter(
            $scopes,
            fn(string $scope) => !in_array($scope, $this->scopeLevels, true) || in_array($scope, $granted, true),
        ));
    }

    /**
     * Whether earlier grants cover the requested scopes. Scopes that aren't levels must all have been granted, and
     * requested levels must overlap the levels granted by the broadest level chosen before.
     * @param string[] $granted
     * @param string[] $requested
     */
    public function grantsCover(array $granted, array $requested): bool
    {
        $level = $this->highestLevel($granted);
        $requestedLevels = array_intersect($requested, $this->scopeLevels);

        if (array_diff($requested, $requestedLevels, $granted)) {
            return false;
        }

        if (!$requestedLevels) {
            return true;
        }

        return $level !== null && array_intersect($requestedLevels, $this->grantedLevels($level));
    }

    public function getResourceUrl(): string
    {
        return $this->resourceUrl instanceof Closure ? ($this->resourceUrl)() : App::parseEnv($this->resourceUrl);
    }
}
