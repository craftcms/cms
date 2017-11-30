<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\errors\TokenNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\models\OauthToken;
use craft\records\OauthToken as OauthTokenRecord;
use craft\records\PluginStoreToken;
use DateInterval;
use DateTime;
use GuzzleHttp\Client;
use yii\base\Component;

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
    // Properties
    // =========================================================================

    /**
     * @var string Craft ID endpoint
     */
    public $craftIdEndpoint = 'https://id.craftcms.com';

    /**
     * @var string OAuth endpoint
     */
    public $craftOauthEndpoint = 'https://api.craftcms.com/oauth';

    /**
     * @var string API endpoint
     */
    public $craftApiEndpoint = 'https://api.craftcms.com/v1';

    /**
     * @var string CraftIdOauthClientId
     */
    public $craftIdOauthClientId = '6DvEra7eqRKLYic9fovyD2FWFjYxRwZn';

    /**
     * @var string Stripe API key
     */
    public $stripeApiKey;

    /**
     * @var bool Enabled Craft ID
     */
    public $enableCraftId = false;

    // Public Methods
    // =========================================================================

    /**
     * Returns the Craft ID account.
     *
     * @return array|null
     */
    public function getCraftIdAccount()
    {
        try {
            $craftIdToken = $this->getToken();

            if ($craftIdToken && $craftIdToken->hasExpired()) {
                $craftIdToken = null;
            }

            $client = $this->getClient();

            if ($craftIdToken) {
                $craftIdAccountResponse = $client->request('GET', 'account');
                $craftIdAccount = json_decode($craftIdAccountResponse->getBody(), true);

                if (!isset($craftIdAccount['error'])) {
                    return $craftIdAccount;
                }
            }
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // Todo: Handle exception
        }
    }

    /**
     * Get authenticated client.
     *
     * @return Client
     */
    public function getClient()
    {
        $options = [
            'base_uri' => $this->craftApiEndpoint.'/',
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

        $userId = Craft::$app->getUser()->getIdentity()->id;

        $oauthToken = new OauthToken;
        $oauthToken->userId = $userId;
        $oauthToken->provider = 'craftid';
        $oauthToken->accessToken = $tokenArray['access_token'];
        $oauthToken->tokenType = $tokenArray['token_type'];
        $oauthToken->expiresIn = $tokenArray['expires_in'];

        $expiryDate = new DateTime();
        $expiryDateInterval = DateTimeHelper::secondsToInterval($tokenArray['expires_in']);
        $expiryDate->add($expiryDateInterval);
        $oauthToken->expiryDate = $expiryDate;

        if ($saveToSession) {
            // Save token to session
            Craft::$app->getSession()->set('pluginStore.token', $oauthToken);
        } else {
            // Save token to database
            // $this->_saveToken($oauthToken);

            $pluginstoreToken = PluginStoreToken::find()
                ->where(['userId' => $userId])
                ->one();

            if ($pluginstoreToken && $pluginstoreToken->oauthTokenId) {
                $oauthTokenRecord = OauthTokenRecord::find()
                    ->where(['id' => $pluginstoreToken->oauthTokenId])
                    ->one();

                if ($oauthTokenRecord) {
                    $oauthTokenRecord->delete();

                    $pluginstoreToken = false;
                }
            }

            if (!$pluginstoreToken) {
                $pluginstoreToken = new PluginStoreToken();
                $pluginstoreToken->userId = $userId;
            }

            $oauthTokenRecord = new OauthTokenRecord();
            $oauthTokenRecord->userId = $oauthToken->userId;
            $oauthTokenRecord->provider = $oauthToken->provider;
            $oauthTokenRecord->accessToken = $oauthToken->accessToken;
            $oauthTokenRecord->tokenType = $oauthToken->tokenType;
            $oauthTokenRecord->expiresIn = $oauthToken->expiresIn;
            $oauthTokenRecord->expiryDate = $oauthToken->expiryDate;
            $oauthTokenRecord->refreshToken = $oauthToken->refreshToken;
            $oauthTokenRecord->save();

            $pluginstoreToken->oauthTokenId = $oauthTokenRecord->id;
            $pluginstoreToken->save();
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

            $pluginstoreToken = PluginStoreToken::find()
                ->where(['userId' => $userId])
                ->one();

            if ($pluginstoreToken) {
                $oauthTokenRecord = $pluginstoreToken->getOauthToken()->one();

                if ($oauthTokenRecord) {
                    return new OauthToken($oauthTokenRecord->getAttributes());
                }
            }
        }

        return $token;
    }

    /**
     * Delete OAuth token.
     */
    public function deleteToken()
    {
        // Delete DB token

        $userId = Craft::$app->getUser()->getIdentity()->id;

        $pluginstoreToken = PluginStoreToken::find()
            ->where(['userId' => $userId])
            ->one();

        if ($pluginstoreToken) {
            $oauthToken = $pluginstoreToken->getOauthToken()->one();

            if ($oauthToken) {
                $oauthToken->delete();
            }
        }


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
            ->delete('{{%oauthtokens}}', ['userId' => $userId])
            ->execute();

        return true;
    }

    /**
     * Get token by user ID.
     *
     * @param $userId
     *
     * @return OauthToken
     */
    public function getTokenByUserId($userId)
    {
        $record = OauthTokenRecord::findOne(['userId' => $userId, 'provider' => 'craftid']);

        if ($record) {
            return new OauthToken($record->getAttributes());
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a plugin store token record based on its ID.
     *
     * @param int $id
     *
     * @return OauthTokenRecord
     */
    private function _getOauthTokenRecordById($id = null)
    {
        if ($id) {
            $record = OauthTokenRecord::findOne($id);
            if (!$record) {
                throw new TokenNotFoundException("No token exists with the ID '{$id}'");
            }
        } else {
            $record = new OauthTokenRecord();
        }

        return $record;
    }


    /**
     * Save token to DB.
     *
     * @param OauthToken $token
     *
     * @return bool
     */
    private function _saveToken(OauthToken $token)
    {
        // is new ?
        $isNewToken = !$token->id;

        // populate record
        $record = $this->_getOauthTokenRecordById($token->id);
        $record->userId = $token->userId;
        $record->provider = $token->provider;
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
