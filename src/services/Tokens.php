<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\dates\DateInterval;
use craft\app\dates\DateTime;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\JsonHelper;
use craft\app\records\Token as TokenRecord;
use yii\base\Component;

/**
 * The Tokens service.
 *
 * An instance of the Tokens service is globally accessible in Craft via [[Application::tokens `Craft::$app->tokens`]].
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
	 *
	 * @param mixed $route              Where matching requests should be routed to. If you want them to be routed to a
	 *                                  controller action, pass:
	 *                                  `['action' => "controller/action", 'params' => ['foo' => 'bar']]`.
	 * @param int|null      $usageLimit The maximum number of times this token can be used. Defaults to no limit.
	 * @param DateTime|null $expiryDate The date that the token expires. Defaults to the 'defaultTokenDuration' config
	 *                                  setting.
	 *
	 * @return string|false             The generated token, or `false` if there was an error.
	 */
	public function createToken($route, $usageLimit = null, $expiryDate = null)
	{
		if (!$expiryDate)
		{
			$expiryDate = DateTimeHelper::currentUTCDateTime();
			$expiryDate->add(new DateInterval(Craft::$app->config->get('defaultTokenDuration')));
		}

		$tokenRecord = new TokenRecord();
		$tokenRecord->token = Craft::$app->security->generateRandomString(32, false);
		$tokenRecord->route = $route;

		if ($usageLimit)
		{
			$tokenRecord->usageCount = 0;
			$usageLimit->usageLimit = $usageLimit;
		}

		$tokenRecord->expiryDate = $expiryDate;
		$success = $tokenRecord->save();

		if ($success)
		{
			return $tokenRecord->token;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Searches for a token, and possibly returns a route for the request.
	 *
	 * @param string $token
	 *
	 * @return array|false
	 */
	public function getTokenRoute($token)
	{
		// Take the opportunity to delete any expired tokens
		$this->deleteExpiredTokens();

		$result = Craft::$app->db->createCommand()
			->select('id, route, usageLimit, usageCount')
			->from('tokens')
			->where('token = :token', [':token' => $token])
			->queryRow();

		if ($result)
		{
			// Usage limit enforcement (for future requests)
			if ($result['usageLimit'])
			{
				// Does it have any more life after this?
				if ($result['usageCount'] < $result['usageLimit'] - 1)
				{
					// Increment its count
					$this->incrementTokenUsageCountById($result['id']);
				}
				else
				{
					// Just delete it
					$this->deleteTokenById($result['id']);
				}
			}

			// Figure out where we should route the request
			$route = $result['route'];

			if (is_string($route) && mb_strlen($route) && ($route[0] == '[' || $route[0] == '{'))
			{
				$route = JsonHelper::decode($route);
			}

			return $route;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Increments a token's usage count.
	 *
	 * @param int $tokenId
	 *
	 * @return bool
	 */
	public function incrementTokenUsageCountById($tokenId)
	{
		$affectedRows = Craft::$app->db->createCommand()->update('tokens', [
			'usageCount' => 'usageCount + 1'
		], [
			'id' => $tokenId
		]);

		return (bool) $affectedRows;
	}

	/**
	 * Deletes a token by its ID.
	 *
	 * @param int $tokenId
	 *
	 * @return bool
	 */
	public function deleteTokenById($tokenId)
	{
		$affectedRows = Craft::$app->db->createCommand()->delete('tokens', [
			'id' => $tokenId
		]);
	}

	/**
	 * Deletes any expired tokens.
	 *
	 * @return bool
	 */
	public function deleteExpiredTokens()
	{
		// Ignore if we've already done this once during the request
		if ($this->_deletedExpiredTokens)
		{
			return false;
		}

		$affectedRows = Craft::$app->db->createCommand()->delete('tokens',
			'expiryDate <= :now',
			['now' => DateTimeHelper::currentTimeForDb()]
		);

		$this->_deletedExpiredTokens = true;

		return (bool) $affectedRows;
	}
}
