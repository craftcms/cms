<?php

declare(strict_types=1);

namespace CraftCms\Cms\RouteToken;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\RouteToken\Model\RouteToken;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use DateTimeInterface;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

#[Scoped]
class RouteTokens
{
    private bool $deletedExpiredTokens = false;

    /**
     * @var array<string,int|null>
     *
     * @see getRemainingTokenUsages()
     */
    private array $remainingTokenUsages = [];

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
     * @param  array<mixed>|string  $route  Where matching requests should be routed to.
     * @param  int|null  $usageLimit  The maximum number of times this token can be
     *                                used. Defaults to no limit.
     * @param  DateTimeInterface|null  $expiryDate  The date that the token expires.
     *                                              Defaults to the 'defaultTokenDuration' config setting.
     * @param  string|null  $token  The token to use, if it was pre-generated. Must be exactly 32 characters.
     * @return string|false The generated token, or `false` if there was an error.
     */
    public function createToken(array|string $route, ?int $usageLimit = null, ?DateTimeInterface $expiryDate = null, ?string $token = null): string|false
    {
        if ($token !== null && strlen($token) !== 32) {
            throw new InvalidArgumentException("Invalid token: $token");
        }

        $tokenModel = new RouteToken;
        $tokenModel->token = $token ?? Str::random(32, extendedChars: true);
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
        $config = Cms::config();
        $duration = $config->previewTokenDuration ?? $config->defaultTokenDuration;
        $expiryDate = now()->addSeconds($duration);

        return $this->createToken($route, $usageLimit, $expiryDate, $token);
    }

    /**
     * Searches for a token, and possibly returns a route for the request.
     */
    /**
     * @return array<mixed>|false
     */
    public function getTokenRoute(string $token): array|false
    {
        // Take the opportunity to delete any expired tokens
        $this->deleteExpiredTokens();

        try {
            return Cache::lock("token:$token")->block(5, function () use ($token) {
                $result = RouteToken::where('token', $token)->first();

                if (! $result) {
                    $this->remainingTokenUsages[$token] = 0;

                    return false;
                }

                // Usage limit enforcement (for future requests)
                if ($result->usageLimit) {
                    // Does it have any more life after this?
                    $newUsageCount = $result->usageCount + 1;
                    if ($newUsageCount < $result->usageLimit) {
                        // Increment its count
                        $this->incrementTokenUsageCountById($result->id);
                        $this->remainingTokenUsages[$token] = $result->usageLimit - $newUsageCount;
                    } else {
                        // Just delete it
                        $this->deleteTokenById($result->id);
                        $this->remainingTokenUsages[$token] = 0;

                        Context::forgetHidden(HandleTokenRequest::TOKEN_KEY);
                    }
                } else {
                    $this->remainingTokenUsages[$token] = null;
                }

                return (array) Json::decodeIfJson($result->route);
            });
        } catch (LockTimeoutException) {
            return false;
        }
    }

    /**
     * Returns the remaining usage count for a given token, if it has a limit.
     */
    public function getRemainingTokenUsages(string $token): ?int
    {
        if (! array_key_exists($token, $this->remainingTokenUsages)) {
            $result = DB::table(Table::ROUTETOKENS)
                ->select('usageLimit', 'usageCount')
                ->where('token', $token)
                ->first();

            if ($result) {
                if ($result->usageLimit) {
                    $this->remainingTokenUsages[$token] = $result->usageLimit - $result->usageCount;
                } else {
                    $this->remainingTokenUsages[$token] = null;
                }
            } else {
                $this->remainingTokenUsages[$token] = 0;
            }
        }

        return $this->remainingTokenUsages[$token];
    }

    public function incrementTokenUsageCountById(int $tokenId): bool
    {
        return (bool) DB::table(Table::ROUTETOKENS)
            ->where('id', $tokenId)
            ->increment('usageCount');
    }

    public function deleteTokenById(int $tokenId): bool
    {
        DB::table(Table::ROUTETOKENS)->delete($tokenId);

        return true;
    }

    public function deleteExpiredTokens(): bool
    {
        // Ignore if we've already done this once during the request
        if ($this->deletedExpiredTokens) {
            return false;
        }

        $affectedRows = DB::table(Table::ROUTETOKENS)
            ->where('expiryDate', '<=', now())
            ->delete();

        $this->deletedExpiredTokens = true;

        return (bool) $affectedRows;
    }
}
