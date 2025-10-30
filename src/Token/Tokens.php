<?php

declare(strict_types=1);

namespace CraftCms\Cms\Token;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Token\Model\Token;
use DateTime;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use yii\base\InvalidArgumentException;

#[Singleton]
final class Tokens
{
    private bool $deletedExpiredTokens = false;

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
     * @param  array|string  $route  Where matching requests should be routed to.
     * @param  int|null  $usageLimit  The maximum number of times this token can be
     *                                used. Defaults to no limit.
     * @param  DateTime|null  $expiryDate  The date that the token expires.
     *                                     Defaults to the 'defaultTokenDuration' config setting.
     * @param  string|null  $token  The token to use, if it was pre-generated. Must be exactly 32 characters.
     * @return string|false The generated token, or `false` if there was an error.
     */
    public function createToken(array|string $route, ?int $usageLimit = null, ?DateTime $expiryDate = null, ?string $token = null): string|false
    {
        if ($token !== null && strlen($token) !== 32) {
            throw new InvalidArgumentException("Invalid token: $token");
        }

        $tokenModel = new Token;
        $tokenModel->token = $token ?? \Craft::$app->getSecurity()->generateRandomString();
        $tokenModel->route = $route;
        $tokenModel->expiryDate = Date::parse($expiryDate ?? now()->addSeconds(Cms::config()->defaultTokenDuration));

        if ($usageLimit !== null) {
            $tokenModel->usageCount = 0;
            $tokenModel->usageLimit = $usageLimit;
        }

        if ($tokenModel->save()) {
            return $tokenModel->token;
        }

        return false;
    }

    /**
     * Creates a new token for previewing content, using the <config5:previewTokenDuration> to determine the duration, if set.
     *
     * @param  mixed  $route  Where matching requests should be routed to.
     * @param  int|null  $usageLimit  The maximum number of times this token can be
     *                                used. Defaults to no limit.
     * @param  string|null  $token  The token to use, if it was pre-generated. Must be exactly 32 characters.
     * @return string|false The generated token, or `false` if there was an error.
     */
    public function createPreviewToken(mixed $route, ?int $usageLimit = null, ?string $token = null): string|false
    {
        return $this->createToken($route, $usageLimit, null, $token);
    }

    /**
     * Searches for a token, and possibly returns a route for the request.
     */
    public function getTokenRoute(string $token): array|false
    {
        // Take the opportunity to delete any expired tokens
        $this->deleteExpiredTokens();

        $result = Token::where('token', $token)->first();

        if (! $result) {
            // Remove it from the request  so it doesn’t get added to generated URLs
            \Craft::$app->getRequest()->setToken(null);

            return false;
        }

        // Usage limit enforcement (for future requests)
        if ($result->usageLimit) {
            // Does it have any more life after this?
            if ($result->usageCount < $result->usageLimit - 1) {
                // Increment its count
                $this->incrementTokenUsageCountById($result->id);
            } else {
                // Just delete it
                $this->deleteTokenById($result->id);

                Context::forgetHidden(HandleTokenRequest::TOKEN_KEY);
            }
        }

        return (array) Json::decodeIfJson($result->route);
    }

    public function incrementTokenUsageCountById(int $tokenId): bool
    {
        return (bool) DB::table(Table::TOKENS)
            ->where('id', $tokenId)
            ->increment('usageCount');
    }

    public function deleteTokenById(int $tokenId): bool
    {
        DB::table(Table::TOKENS)->delete($tokenId);

        return true;
    }

    public function deleteExpiredTokens(): bool
    {
        // Ignore if we've already done this once during the request
        if ($this->deletedExpiredTokens) {
            return false;
        }

        $affectedRows = DB::table(Table::TOKENS)
            ->where('expiryDate', '<=', now())
            ->delete();

        $this->deletedExpiredTokens = true;

        return (bool) $affectedRows;
    }
}
