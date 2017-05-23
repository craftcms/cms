<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\helpers\DateTimeHelper;
use yii\base\Component;
use GuzzleHttp\Client;
use DateTime;
use DateInterval;
use craft\models\PluginStoreToken;
use craft\errors\TokenNotFoundException;
use craft\records\PluginStoreToken as PluginStoreTokenRecord;

/**
 * Class PluginStore service.
 *
 * An instance of the PluginStore service is globally accessible in Craft via [[Application::pluginStore `Craft::$app->getPluginStore()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PluginStore extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get authenticated client.
     *
     * @return Client
     */
    public function getClient()
    {
        $options = [
            'base_uri' => 'https://craftid.dev/api/',
        ];

        $token = $this->getToken();

        if ($token) {
            if (isset($token->accessToken)) {
                $options['headers']['Authorization'] = 'Bearer '.$token->accessToken;
            }
        }

        return Craft::createGuzzleClient($options);
    }

    /**
     * Save OAuth token.
     *
     * @param array $tokenArray
     */
    public function saveToken(array $tokenArray)
    {
        $oneDay = new DateTime();
        $oneDay->add(new DateInterval('P1D'));

        $expiresIn = new DateTime();
        $expiresInInterval = DateTimeHelper::secondsToInterval($tokenArray['expires_in']);
        $expiresIn->add($expiresInInterval);

        $saveToSession = true;

        if ($expiresIn->getTimestamp() > $oneDay->getTimestamp()) {
            $saveToSession = false;
        }

        $token = new PluginStoreToken;
        $token->userId = Craft::$app->getUser()->getIdentity()->id;
        $token->accessToken = $tokenArray['access_token'];
        $token->tokenType = $tokenArray['token_type'];
        $token->expiresIn = $tokenArray['expires_in'];

        $expiryDate = new DateTime();
        $expiryDateInterval = DateTimeHelper::secondsToInterval($tokenArray['expires_in']);
        $expiryDate->add($expiryDateInterval);
        $token->expiryDate = $expiryDate;

        if ($saveToSession) {
            // Save token to session
            Craft::$app->getSession()->set('pluginStore.token', $token);
        } else {
            // Save token to database
            $this->_saveToken($token);
        }
    }

    /**
     * Get OAuth token.
     *
     * @return mixed
     */
    public function getToken()
    {
        $userId = Craft::$app->getUser()->getIdentity()->id;


        // Get the token from the session

        $token = Craft::$app->getSession()->get('pluginStore.token');


        // Or use the token from the database otherwise

        if (!$token || ($token && $token->hasExpired())) {
            $dbToken = $this->getTokenByUserId($userId);

            if ($dbToken) {
                return $dbToken;
            }
        }

        return $token;
    }

    /**
     * Delete OAuth token.
     */
    public function deleteToken()
    {
        $userId = Craft::$app->getUser()->getIdentity()->id;

        // Delete cache token
        $this->deleteTokenByUserId($userId);

        // Delete session token
        Craft::$app->getSession()->remove('pluginStore.token');
    }

    /**
     * Delete token from its user ID.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function deleteTokenByUserId(int $userId): bool
    {
        $token = $this->getTokenByUserId($userId);

        if (!$token) {
            return false;
        }

        Craft::$app->getDb()->createCommand()
            ->delete('{{%plugin_store_tokens}}', ['userId' => $userId])
            ->execute();

        return true;
    }

    /**
     * Get token by user ID.
     *
     * @param $userId
     *
     * @return PluginStoreToken
     */
    public function getTokenByUserId($userId)
    {
        $record = PluginStoreTokenRecord::findOne(['userId' => $userId]);

        if ($record) {
            return new PluginStoreToken($record->getAttributes());
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a plugin store token record based on its ID.
     *
     * @param int $id
     *
     * @return PluginStoreTokenRecord
     */
    private function _getPluginStoreTokenRecordById($id = null)
    {
        if ($id) {
            $record = PluginStoreTokenRecord::findOne($id);
            if (!$record) {
                throw new TokenNotFoundException("No token exists with the ID '{$id}'");
            }
        } else {
            $record = new PluginStoreTokenRecord();
        }

        return $record;
    }


    /**
     * Save token to DB.
     *
     * @param PluginStoreToken $token
     *
     * @return bool
     */
    private function _saveToken(PluginStoreToken $token)
    {
        // is new ?
        $isNewToken = !$token->id;

        // populate record
        $record = $this->_getPluginStoreTokenRecordById($token->id);
        $record->userId = $token->userId;
        $record->accessToken = $token->accessToken;
        $record->tokenType = $token->tokenType;
        $record->expiresIn = $token->expiresIn;
        $record->expiryDate = $token->expiryDate;
        $record->refreshToken = $token->refreshToken;

        // save record
        if ($record->save(false)) {
            // populate id
            if ($isNewToken) {
                $token->id = $record->id;
            }

            return true;
        }

        return false;
    }
}
