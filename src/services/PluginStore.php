<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Session;
use craft\models\CraftIdToken;
use craft\records\CraftIdToken as OauthTokenRecord;
use DateInterval;
use DateTime;
use yii\base\Component;

/**
 * Plugin Store service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getPluginStore()|`Craft::$app->pluginStore`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class PluginStore extends Component
{
    /**
     * @var string Craft ID endpoint
     */
    public string $craftIdEndpoint = 'https://id.craftcms.com';

    /**
     * @var string OAuth endpoint
     */
    public string $craftOauthEndpoint = 'https://id.craftcms.com/oauth';

    /**
     * @var string API endpoint
     */
    public string $craftApiEndpoint = 'https://api.craftcms.com/v1';

    /**
     * @var string CraftIdOauthClientId
     */
    public string $craftIdOauthClientId = '6DvEra7eqRKLYic9fovyD2FWFjYxRwZn';

    /**
     * @var string Dev server manifest path
     */
    public string $devServerManifestPath = 'https://localhost:8082/';

    /**
     * @var string Dev server public path
     */
    public string $devServerPublicPath = 'https://localhost:8082/';

    /**
     * @var bool Enable dev server
     */
    public bool $useDevServer = false;

    /**
     * Saves the OAuth token.
     *
     * @param array $tokenArray
     */
    public function saveToken(array $tokenArray): void
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
            Session::set('pluginStore.token', $oauthToken);
        } else {
            // Save token to database

            OauthTokenRecord::find()
                ->where(['userId' => $userId])
                ->one()
                ?->delete();

            $oauthTokenRecord = new OauthTokenRecord();
            $oauthTokenRecord->userId = $oauthToken->userId;
            $oauthTokenRecord->accessToken = $oauthToken->accessToken;
            $oauthTokenRecord->expiryDate = Db::prepareDateForDb($oauthToken->expiryDate);
            $oauthTokenRecord->save();
        }
    }

    /**
     * Returns the OAuth token.
     *
     * @return CraftIdToken|null
     */
    public function getToken(): ?CraftIdToken
    {
        $userId = Craft::$app->getUser()->getIdentity()->id;

        // Get the token from the session
        $token = Session::get('pluginStore.token');

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

        if ($token->hasExpired()) {
            return null;
        }

        return $token;
    }

    /**
     * Deletes an OAuth token.
     */
    public function deleteToken(): void
    {
        // Delete DB token
        $userId = Craft::$app->getUser()->getIdentity()->id;

        OauthTokenRecord::find()
            ->where(['userId' => $userId])
            ->one()
            ?->delete();

        // Delete session token
        Session::remove('pluginStore.token');
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

        Db::delete(Table::CRAFTIDTOKENS, [
            'userId' => $userId,
        ]);

        return true;
    }

    /**
     * Returns the token by user ID.
     *
     * @param int $userId
     * @return CraftIdToken|null
     */
    public function getTokenByUserId(int $userId): ?CraftIdToken
    {
        $record = OauthTokenRecord::findOne(['userId' => $userId, 'provider' => 'craftid']);

        if (!$record) {
            return null;
        }

        return new CraftIdToken($record->getAttributes());
    }
}
