<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CraftCms\Cms\RouteToken\RouteTokens;
use DateTime;
use yii\base\Component;

/**
 * The Tokens service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getTokens()|`Craft::$app->getTokens()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\RouteToken\RouteTokens} instead.
 */
class Tokens extends Component
{
    /**
     * Creates a new token and returns it.
     * ---
     * ```php
     * // Route to a controller action
     * app(Tokens::class)->createToken('action/path');
     *
     * // Route to a controller action with params
     * app(Tokens::class)->createToken(['action/path', [
     *     'foo' => 'bar'
     * ]]);
     *
     * // Route to a template
     * app(Tokens::class)->createToken([
     *     'templates/render',
     *     [
     *         'template' => 'template/path',
     *     ]
     * ]);
     * ```
     *
     * @param array|string $route Where matching requests should be routed to.
     * @param int|null $usageLimit The maximum number of times this token can be
     * used. Defaults to no limit.
     * @param DateTime|null $expiryDate The date that the token expires.
     * Defaults to the 'defaultTokenDuration' config setting.
     * @param string|null $token The token to use, if it was pre-generated. Must be exactly 32 characters.
     * @return string|false The generated token, or `false` if there was an error.
     */
    public function createToken(array|string $route, ?int $usageLimit = null, ?DateTime $expiryDate = null, ?string $token = null): string|false
    {
        return app(RouteTokens::class)->createToken($route, $usageLimit, $expiryDate, $token);
    }

    /**
     * Creates a new token for previewing content, using the <config5:previewTokenDuration> to determine the duration, if set.
     *
     * @param mixed $route Where matching requests should be routed to.
     * @param int|null $usageLimit The maximum number of times this token can be
     * used. Defaults to no limit.
     * @param string|null $token The token to use, if it was pre-generated. Must be exactly 32 characters.
     * @return string|false The generated token, or `false` if there was an error.
     * @since 3.7.0
     */
    public function createPreviewToken(mixed $route, ?int $usageLimit = null, ?string $token = null): string|false
    {
        return app(RouteTokens::class)->createPreviewToken($route, $usageLimit, $token);
    }

    /**
     * Searches for a token, and possibly returns a route for the request.
     *
     * @param string $token
     * @return array|false
     */
    public function getTokenRoute(string $token): array|false
    {
        return app(RouteTokens::class)->getTokenRoute($token);
    }

    /**
     * Increments a token's usage count.
     *
     * @param int $tokenId
     * @return bool
     */
    public function incrementTokenUsageCountById(int $tokenId): bool
    {
        return app(RouteTokens::class)->incrementTokenUsageCountById($tokenId);
    }

    /**
     * Deletes a token by its ID.
     *
     * @param int $tokenId
     * @return bool
     */
    public function deleteTokenById(int $tokenId): bool
    {
        return app(RouteTokens::class)->deleteTokenById($tokenId);
    }

    /**
     * Deletes any expired tokens.
     *
     * @return bool
     */
    public function deleteExpiredTokens(): bool
    {
        return app(RouteTokens::class)->deleteExpiredTokens();
    }
}
