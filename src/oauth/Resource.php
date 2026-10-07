<?php

namespace craft\oauth;

use Closure;
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

    public function getResourceUrl(): string
    {
        return $this->resourceUrl instanceof Closure ? ($this->resourceUrl)() : App::parseEnv($this->resourceUrl);
    }
}
