<?php
namespace Craft;

/**
 * Tokens service.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     2.1
 */
class TokensService extends BaseApplicationComponent
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
	 *                                  `array('action' => "controller/action", 'params' => array('foo' => 'bar'))`.
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
			$expiryDate->add(new DateInterval(craft()->config->get('defaultTokenDuration')));
		}

		$tokenRecord = new TokenRecord();
		$tokenRecord->token = craft()->security->generateRandomString(32);
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

		$result = craft()->db->createCommand()
			->select('id, route, usageLimit, usageCount')
			->from('tokens')
			->where('token = :token', array(':token' => $token))
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
		$affectedRows = craft()->db->createCommand()->update('tokens', array(
			'usageCount' => 'usageCount + 1'
		), array(
			'id' => $tokenId
		));

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
		$affectedRows = craft()->db->createCommand()->delete('tokens', array(
			'id' => $tokenId
		));
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

		$affectedRows = craft()->db->createCommand()->delete('tokens',
			'expiryDate <= :now',
			array('now' => DateTimeHelper::currentTimeForDb())
		);

		$this->_deletedExpiredTokens = true;

		return (bool) $affectedRows;
	}
}
