<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Table;
use craft\errors\TokenNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\models\CraftIdToken;
use craft\records\CraftIdToken as OauthTokenRecord;
use DateInterval;
use DateTime;
use yii\base\Component;

/**
 * Plugin Store service.
 * An instance of the Plugin Store service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getPluginStore()|`Craft::$app->pluginStore`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
    public $craftOauthEndpoint = 'https://id.craftcms.com/oauth';

    /**
     * @var string API endpoint
     */
    public $craftApiEndpoint = 'https://api.craftcms.com/v1';

    /**
     * @var string CraftIdOauthClientId
     */
    public $craftIdOauthClientId = '6DvEra7eqRKLYic9fovyD2FWFjYxRwZn';

    /**
     * @var string Dev server manifest path
     */
    public $devServerManifestPath = 'https://localhost:8082/';

    /**
     * @var string Dev server public path
     */
    public $devServerPublicPath = 'https://localhost:8082/';

    /**
     * @var bool Enable dev server
     */
    public $useDevServer = false;

    // Public Methods
    // =========================================================================

    /**
     * Returns the Craft ID account.
     *
     * @return array|null
     * @throws \Exception
     */
    public function getCraftIdAccount()
    {
        $craftIdToken = $this->getToken();

        if (!$craftIdToken) {
            return null;
        }

        $client = Craft::$app->getApi()->client;
        $options = $this->getApiRequestOptions();
        $craftIdAccountResponse = $client->get('account', $options);
        $craftIdAccount = json_decode($craftIdAccountResponse->getBody(), true);

        if (isset($craftIdAccount['error'])) {
            throw new \Exception("Couldn’t get Craft ID account: " . $craftIdAccount['error']);
        }

        return $craftIdAccount;
    }

    /**
     * Returns the options for authenticated API requests.
     *
     * @return array
     */
    public function getApiRequestOptions(): array
    {
        $options = [];

        $token = $this->getToken();
        if ($token && $token->accessToken !== null) {
            $options['headers']['Authorization'] = 'Bearer ' . $token->accessToken;
        }

        return $options;
    }

    /**
     * Saves the OAuth token.
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

        $oauthToken = new CraftIdToken();
        $oauthToken->userId = $userId;
        $oauthToken->accessToken = $tokenArray['access_token'];

        $expiryDate = new DateTime();
        $expiryDateInterval = DateTimeHelper::secondsToInterval($tokenArray['expires_in']);
        $expiryDate->add($expiryDateInterval);
        $oauthToken->expiryDate = $expiryDate;

        if ($saveToSession) {
            // Save token to session
            Craft::$app->getSession()->set('pluginStore.token', $oauthToken);
        } else {
            // Save token to database

            $oauthTokenRecord = OauthTokenRecord::find()
                ->where(['userId' => $userId])
                ->one();

            if ($oauthTokenRecord) {
                $oauthTokenRecord->delete();
            }

            $oauthTokenRecord = new OauthTokenRecord();
            $oauthTokenRecord->userId = $oauthToken->userId;
            $oauthTokenRecord->accessToken = $oauthToken->accessToken;
            $oauthTokenRecord->expiryDate = $oauthToken->expiryDate;
            $oauthTokenRecord->save();
        }
    }

    /**
     * Returns the OAuth token.
     *
     * @return CraftIdToken|null
     */
    public function getToken()
    {
        $userId = Craft::$app->getUser()->getIdentity()->id;

        // Get the token from the session
        $token = Craft::$app->getSession()->get('pluginStore.token');

        if ($token && !$token->hasExpired()) {
            return $token;
        }

        // Or use the token from the database otherwise
        $oauthTokenRecord = OauthTokenRecord::find()
            ->where(['userId' => $userId])
            ->one();

        if (!$oauthTokenRecord) {
            return null;
        }

        $token = new CraftIdToken($oauthTokenRecord->getAttributes());

        if (!$token || ($token && $token->hasExpired())) {
            return null;
        }

        return $token;
    }

    /**
     * Deletes an OAuth token.
     */
    public function deleteToken()
    {
        // Delete DB token

        $userId = Craft::$app->getUser()->getIdentity()->id;

        $oauthToken = OauthTokenRecord::find()
            ->where(['userId' => $userId])
            ->one();

        if ($oauthToken) {
            $oauthToken->delete();
        }


        // Delete session token

        Craft::$app->getSession()->remove('pluginStore.token');
    }

    /**
     * Deletes the token from its user ID.
     *
     * @param int $userId
     * @return bool
     */
    public function deleteTokenByUserId(int $userId): bool
    {
        $token = $this->getTokenByUserId($userId);

        if (!$token) {
            return false;
        }

        Craft::$app->getDb()->createCommand()
            ->delete(Table::CRAFTIDTOKENS, ['userId' => $userId])
            ->execute();

        return true;
    }

    /**
     * Returns the token by user ID.
     *
     * @param $userId
     * @return CraftIdToken|null
     */
    public function getTokenByUserId($userId)
    {
        $record = OauthTokenRecord::findOne(['userId' => $userId, 'provider' => 'craftid']);

        if (!$record) {
            return null;
        }

        return new CraftIdToken($record->getAttributes());
    }
}
