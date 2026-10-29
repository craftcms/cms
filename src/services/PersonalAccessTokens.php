<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Closure;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\oauth\ScopeGroup;
use DateInterval;
use DateTime;
use yii\base\Component;
use yii\base\InvalidArgumentException;

/**
 * Personal access tokens, which users create from **My Account → Tokens** for apps and scripts that can’t complete an
 * OAuth authorization flow.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getPersonalAccessTokens()|`Craft::$app->getPersonalAccessTokens()`]].
 *
 * Tokens are OAuth personal access tokens, as in Laravel Passport. APIs offer their OAuth scopes for them by
 * registering a [[ScopeGroup]], and the Tokens screen is only shown to users who may grant at least one registered
 * scope:
 *
 * ```php
 * use craft\oauth\ScopeGroup;
 *
 * Craft::$app->getPersonalAccessTokens()->registerScopeGroup('my-plugin', fn() => new ScopeGroup(
 *     handle: 'my-plugin',
 *     label: 'My Plugin',
 *     scopes: ['my-plugin:read' => 'Read my plugin’s data'],
 * ));
 * ```
 *
 * The scopes must be defined by a registered OAuth resource.
 *
 * @since 5.12.0
 */
class PersonalAccessTokens extends Component
{
    /**
     * @var array<string,Closure>
     */
    private array $factories = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->registerScopeGroup('graphql', function() {
            if (!Craft::$app->getConfig()->getGeneral()->enableGql) {
                return null;
            }

            $resource = Craft::$app->getOAuth()->getResource('graphql');
            return new ScopeGroup(
                handle: 'graphql',
                label: Craft::t('app', 'GraphQL'),
                scopes: $resource->scopes,
                userScopes: fn(User $user, array $scopes) => $resource->scopesForUser($user, $scopes),
            );
        });
    }

    /**
     * Registers a scope group.
     *
     * @param string $handle
     * @param Closure $factory Returns the [[ScopeGroup]] with that handle, or `null` if it isn’t available.
     */
    public function registerScopeGroup(string $handle, Closure $factory): void
    {
        if (isset($this->factories[$handle])) {
            throw new InvalidArgumentException("Personal access token scope group [$handle] is already registered.");
        }

        $this->factories[$handle] = $factory;
    }

    /**
     * Returns the registered scope groups, indexed by handle.
     *
     * @return array<string,ScopeGroup>
     */
    public function scopeGroups(): array
    {
        $groups = [];

        foreach ($this->factories as $handle => $factory) {
            $group = $factory();

            if ($group === null) {
                continue;
            }

            if (!$group instanceof ScopeGroup || $group->handle !== $handle) {
                throw new InvalidArgumentException("Personal access token scope group factory [$handle] must return a scope group with that handle.");
            }

            $groups[$handle] = $group;
        }

        return $groups;
    }

    /**
     * Returns the scope groups the user may grant at least one scope from, indexed by handle.
     *
     * @return array<string,ScopeGroup>
     */
    public function scopeGroupsFor(User $user): array
    {
        return array_filter($this->scopeGroups(), fn(ScopeGroup $group) => $group->scopesFor($user) !== []);
    }

    /**
     * Creates a personal access token, issued through the personal access client like Passport’s
     * `$user->createToken()`.
     *
     * The scopes can come from several scope groups, so one token can access several APIs.
     *
     * @param string[] $scopes
     * @return string The access token. It isn’t stored, so it can’t be retrieved again.
     * @throws InvalidArgumentException if the user may not grant the scopes
     */
    public function create(User $user, string $name, array $scopes, DateInterval $expiresIn): string
    {
        $grantable = array_merge(...array_values(array_map(
            fn(ScopeGroup $group) => $group->scopesFor($user),
            $this->scopeGroupsFor($user),
        )));

        if ($scopes === [] || array_diff($scopes, $grantable) !== []) {
            throw new InvalidArgumentException('Invalid scopes.');
        }

        return Craft::$app->getOAuth()->createPersonalAccessToken($user, $name, $scopes, $expiresIn);
    }

    /**
     * Returns the user’s active personal access tokens, and the apps they’ve authorized, for the registered scope
     * groups.
     *
     * Personal access tokens are listed individually, with their `tokenId`. Apps are listed once per client, with a
     * `tokenId` of `null`, and stay listed while they can still refresh their access. Scopes are grouped by the
     * handle of the scope group that defines them, and scopes outside the registered groups aren’t listed.
     *
     * @return array{clientId:string,tokenId:string|null,name:string,scopes:array<string,string[]>,expiresAt:DateTime,personal:bool}[]
     */
    public function grants(User $user): array
    {
        $groups = $this->scopeGroups();
        $now = Db::prepareDateForDb(new DateTime());
        $rows = (new Query())
            ->select([
                'access.id',
                'access.client_id',
                'access.name',
                'access.scopes',
                'access.expires_at',
                'refreshExpiresAt' => 'refresh.expires_at',
                'clientName' => 'clients.name',
                'clients.grant_types',
            ])
            ->from(['access' => Table::OAUTH_ACCESS_TOKENS])
            ->innerJoin(['clients' => Table::OAUTH_CLIENTS], '[[clients.id]] = [[access.client_id]]')
            ->leftJoin(['refresh' => Table::OAUTH_REFRESH_TOKENS], [
                'and',
                '[[refresh.access_token_id]] = [[access.id]]',
                ['refresh.revoked' => false],
                ['>', 'refresh.expires_at', $now],
            ])
            ->where([
                'access.user_id' => $user->id,
                'access.revoked' => false,
                'clients.revoked' => false,
            ])
            ->andWhere([
                'or',
                ['>', 'access.expires_at', $now],
                ['not', ['refresh.id' => null]],
            ])
            ->orderBy(['access.created_at' => SORT_DESC])
            ->all();

        $grants = [];

        foreach ($rows as $row) {
            $scopes = json_decode($row['scopes'] ?? '', true) ?: [];

            if (!$this->groupScopes($groups, $scopes)) {
                continue;
            }

            $personal = in_array('personal_access', json_decode($row['grant_types'] ?? '', true) ?: [], true);
            $key = $personal ? "token:{$row['id']}" : "client:{$row['client_id']}";
            $grant = $grants[$key] ?? null;
            // When the token stops working: when it expires, or for an app that can refresh it, when its refresh token does.
            $expiresAt = DateTimeHelper::toDateTime(max($row['expires_at'], $row['refreshExpiresAt'] ?? ''));

            $grants[$key] = [
                'clientId' => $row['client_id'],
                'tokenId' => $personal ? $row['id'] : null,
                'name' => ($personal ? $row['name'] : null) ?? $row['clientName'],
                'scopes' => array_values(array_unique([...$grant['scopes'] ?? [], ...$scopes])),
                'expiresAt' => $grant ? max($grant['expiresAt'], $expiresAt) : $expiresAt,
                'personal' => $personal,
            ];
        }

        foreach ($grants as &$grant) {
            $grant['scopes'] = $this->groupScopes($groups, $grant['scopes']);
        }
        unset($grant);

        return array_values($grants);
    }

    /**
     * Renames one of the user’s personal access tokens.
     *
     * A token’s scopes are signed into it, so they can’t be changed after it’s created.
     *
     * @throws InvalidArgumentException if the token isn’t one of the user’s active personal access tokens
     */
    public function rename(User $user, string $tokenId, string $name): void
    {
        if (!ArrayHelper::firstWhere($this->grants($user), 'tokenId', $tokenId, true)) {
            throw new InvalidArgumentException('Invalid token.');
        }

        Db::update(Table::OAUTH_ACCESS_TOKENS, [
            'name' => $name,
            'updated_at' => Db::prepareDateForDb(new DateTime()),
        ], ['id' => $tokenId], updateTimestamp: false);
    }

    /**
     * Revokes one of the user’s personal access tokens, or if `$tokenId` is `null`, the access and refresh tokens
     * they’ve granted an app for the registered scope groups.
     */
    public function revoke(User $user, string $clientId, ?string $tokenId = null): void
    {
        $groups = $this->scopeGroups();
        $condition = ['user_id' => $user->id, 'client_id' => $clientId];
        if ($tokenId !== null) {
            $condition['id'] = $tokenId;
        }
        $rows = (new Query())->select(['id', 'scopes'])->from(Table::OAUTH_ACCESS_TOKENS)->where($condition)->pairs();
        $ids = array_keys(array_filter($rows, fn(?string $scopes) => $this->groupScopes($groups, json_decode($scopes ?? '', true) ?: []) !== []));

        if (!$ids) {
            return;
        }

        Db::update(Table::OAUTH_ACCESS_TOKENS, [
            'revoked' => true,
            'updated_at' => Db::prepareDateForDb(new DateTime()),
        ], ['id' => $ids], updateTimestamp: false);
        Db::update(Table::OAUTH_REFRESH_TOKENS, ['revoked' => true], ['access_token_id' => $ids], updateTimestamp: false);
    }

    /**
     * Groups scope IDs by the handle of the scope group that defines them, leaving out scopes outside the groups.
     *
     * @param array<string,ScopeGroup> $groups
     * @param string[] $scopes
     * @return array<string,string[]>
     */
    private function groupScopes(array $groups, array $scopes): array
    {
        $grouped = [];

        foreach ($groups as $handle => $group) {
            $groupScopes = array_values(array_intersect($scopes, array_keys($group->scopes)));

            if ($groupScopes) {
                $grouped[$handle] = $groupScopes;
            }
        }

        return $grouped;
    }
}
