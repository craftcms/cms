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
use yii\db\Expression;

/**
 * The Tokens service.
 * An instance of the Tokens service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getTokens()|`Craft::$app->tokens`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Tokens extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    private $_deletedExpiredTokens = false;

    // Public Methods
    // =========================================================================

    /**
     * Creates a new token and returns it.
     * ---
     * ```php
     * // Route to a controller action
     * Craft::$app->tokens->createToken('action/path');
     *
     * // Route to a controller action with params
     * Craft::$app->tokens->createToken('action/path', ['foo' => 'bar']);
     *
     * // Route to a template
     * Craft::$app->tokens->createToken(['template' => 'template/path']);
     * ```
     *
     * @param mixed $route Where matching requests should be routed to.
     * @param int|null $usageLimit The maximum number of times this token can be
     * used. Defaults to no limit.
     * @param DateTime|null $expiryDate The date that the token expires.
     * Defaults to the 'defaultTokenDuration' config setting.
     * @return string|false The generated token, or `false` if there was an error.
     */
    public function createToken($route, int $usageLimit = null, DateTime $expiryDate = null)
    {
        if (!$expiryDate) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->defaultTokenDuration);
            $expiryDate = DateTimeHelper::currentUTCDateTime();
            $expiryDate->add($interval);
        }

        $tokenRecord = new TokenRecord();
        $tokenRecord->token = Craft::$app->getSecurity()->generateRandomString(32);
        $tokenRecord->route = $route;

        if ($usageLimit !== null) {
            $tokenRecord->usageCount = 0;
            $tokenRecord->usageLimit = $usageLimit;
        }

        $tokenRecord->expiryDate = $expiryDate;
        $success = $tokenRecord->save();

        if ($success) {
            return $tokenRecord->token;
        }

        return false;
    }

    /**
     * Searches for a token, and possibly returns a route for the request.
     *
     * @param string $token
     * @return array|false
     */
    public function getTokenRoute(string $token)
    {
        // Take the opportunity to delete any expired tokens
        $this->deleteExpiredTokens();
        $result = (new Query())
            ->select(['id', 'route', 'usageLimit', 'usageCount'])
            ->from([Table::TOKENS])
            ->where(['token' => $token])
            ->one();

        if (!$result) {
            return false;
        }

        // Usage limit enforcement (for future requests)
        if ($result['usageLimit']) {
            // Does it have any more life after this?
            if ($result['usageCount'] < $result['usageLimit'] - 1) {
                // Increment its count
                $this->incrementTokenUsageCountById($result['id']);
            } else {
                // Just delete it
                $this->deleteTokenById($result['id']);
            }
        }

        // Figure out where we should route the request
        $route = $result['route'];

        // Might be JSON, might not be
        $route = Json::decodeIfJson($route);

        return $route;
    }

    /**
     * Increments a token's usage count.
     *
     * @param int $tokenId
     * @return bool
     */
    public function incrementTokenUsageCountById(int $tokenId): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->update(
                Table::TOKENS,
                [
                    'usageCount' => new Expression('[[usageCount]] + 1')
                ],
                [
                    'id' => $tokenId
                ])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Deletes a token by its ID.
     *
     * @param int $tokenId
     * @return bool
     */
    public function deleteTokenById(int $tokenId): bool
    {
        Craft::$app->getDb()->createCommand()
            ->delete(Table::TOKENS, ['id' => $tokenId])
            ->execute();

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

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(Table::TOKENS, ['<=', 'expiryDate', Db::prepareDateForDb(new DateTime())])
            ->execute();

        $this->_deletedExpiredTokens = true;

        return (bool)$affectedRows;
    }
}
