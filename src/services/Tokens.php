<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\records\Token as TokenRecord;
use DateTime;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\db\Expression;

/**
 * The Tokens service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getTokens()|`Craft::$app->getTokens()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Tokens extends Component
{
    /**
     * @var bool
     */
    private bool $_deletedExpiredTokens = false;

    /**
     * @var array<string,int|null>
     * @see getRemainingTokenUsages()
     */
    private array $_remainingTokenUsages = [];

    /**
     * Creates a new token and returns it.
     * ---
     * ```php
     * // Route to a controller action
     * Craft::$app->tokens->createToken('action/path');
     *
     * // Route to a controller action with params
     * Craft::$app->tokens->createToken(['action/path', [
     *     'foo' => 'bar'
     * ]]);
     *
     * // Route to a template
     * Craft::$app->tokens->createToken([
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
        if ($token !== null && strlen($token) !== 32) {
            throw new InvalidArgumentException("Invalid token: $token");
        }

        if (!$expiryDate) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->defaultTokenDuration);
            $expiryDate = DateTimeHelper::currentUTCDateTime();
            $expiryDate->add($interval);
        }

        $tokenRecord = new TokenRecord();
        $tokenRecord->token = $token ?? Craft::$app->getSecurity()->generateRandomString();
        $tokenRecord->route = $route;

        if ($usageLimit !== null) {
            $tokenRecord->usageCount = 0;
            $tokenRecord->usageLimit = $usageLimit;
        }

        $tokenRecord->expiryDate = Db::prepareDateForDb($expiryDate);
        $success = $tokenRecord->save();

        if ($success) {
            return $tokenRecord->token;
        }

        return false;
    }

    /**
     * Creates a new token for previewing content, using the <config4:previewTokenDuration> to determine the duration, if set.
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
        $interval = DateTimeHelper::secondsToInterval(Craft::$app->getConfig()->getGeneral()->previewTokenDuration);
        $expiryDate = DateTimeHelper::currentUTCDateTime()->add($interval);
        return $this->createToken($route, $usageLimit, $expiryDate, $token);
    }

    /**
     * Searches for a token, and possibly returns a route for the request.
     *
     * @param string $token
     * @return array|false
     */
    public function getTokenRoute(string $token): array|false
    {
        // Take the opportunity to delete any expired tokens
        $this->deleteExpiredTokens();

        $mutex = Craft::$app->getMutex();
        $lockKey = "token:$token";
        if (!$mutex->acquire($lockKey, 5)) {
            return false;
        }

        try {
            $result = (new Query())
                ->select(['id', 'route', 'usageLimit', 'usageCount'])
                ->from([Table::TOKENS])
                ->where(['token' => $token])
                ->one();

            if (!$result) {
                $this->_remainingTokenUsages[$token] = 0;
                return false;
            }

            // Usage limit enforcement (for future requests)
            if ($result['usageLimit']) {
                // Does it have any more life after this?
                $newUsageCount = $result['usageCount'] + 1;
                if ($newUsageCount < $result['usageLimit']) {
                    // Increment its count
                    $this->incrementTokenUsageCountById($result['id']);
                    $this->_remainingTokenUsages[$token] = $result['usageLimit'] - $newUsageCount;
                } else {
                    // Just delete it
                    $this->deleteTokenById($result['id']);
                    $this->_remainingTokenUsages[$token] = 0;
                }
            } else {
                $this->_remainingTokenUsages[$token] = null;
            }

            return (array)Json::decodeIfJson($result['route']);
        } finally {
            $mutex->release($lockKey);
        }
    }

    /**
     * Returns the remaining usage count for a given token, if it has a limit.
     *
     * @param string $token
     * @return int|null
     * @since 4.17.6
     */
    public function getRemainingTokenUsages(string $token): ?int
    {
        if (!array_key_exists($token, $this->_remainingTokenUsages)) {
            $result = (new Query())
                ->select(['usageLimit', 'usageCount'])
                ->from([Table::TOKENS])
                ->where(['token' => $token])
                ->one();

            if ($result) {
                if ($result['usageLimit']) {
                    $this->_remainingTokenUsages[$token] = $result['usageLimit'] - $result['usageCount'];
                } else {
                    $this->_remainingTokenUsages[$token] = null;
                }
            } else {
                $this->_remainingTokenUsages[$token] = 0;
            }
        }

        return $this->_remainingTokenUsages[$token];
    }

    /**
     * Increments a token's usage count.
     *
     * @param int $tokenId
     * @return bool
     */
    public function incrementTokenUsageCountById(int $tokenId): bool
    {
        return (bool)Db::update(Table::TOKENS, [
            'usageCount' => new Expression('[[usageCount]] + 1'),
        ], [
            'id' => $tokenId,
        ]);
    }

    /**
     * Deletes a token by its ID.
     *
     * @param int $tokenId
     * @return bool
     */
    public function deleteTokenById(int $tokenId): bool
    {
        Db::delete(Table::TOKENS, [
            'id' => $tokenId,
        ]);

        return true;
    }

    /**
     * Deletes any expired tokens.
     *
     * @return bool
     */
    public function deleteExpiredTokens(): bool
    {
        // Ignore if we've already done this once during the request
        if ($this->_deletedExpiredTokens) {
            return false;
        }

        $affectedRows = Db::delete(Table::TOKENS, ['<=', 'expiryDate', Db::prepareDateForDb(new DateTime())]);

        $this->_deletedExpiredTokens = true;

        return (bool)$affectedRows;
    }
}
