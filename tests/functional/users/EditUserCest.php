<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional\users;

use Craft;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\helpers\StringHelper;
use FunctionalTester;

/**
 * Test editing of a user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class EditUserCest
{
    /**
     * @var string
     */
    public string $cpTrigger;

    /**
     * @var User|null
     */
    public ?User $currentUser = null;

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $this->currentUser = User::find()
            ->admin()
            ->one();

        $I->amLoggedInAs($this->currentUser);
        $this->cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;

        Craft::$app->edition = CmsEdition::Pro;
    }

    /**
     * @param FunctionalTester $I
     */
    public function testMyAccountPage(FunctionalTester $I): void
    {
        $I->amOnPage('/' . $this->cpTrigger . '/myaccount');

        $I->see('My Account');

        $I->submitForm('#main-form', [
            'fullName' => 'IM A CHANGED FULLNAME',
        ]);

        $I->seeInTitle('Users');

        /** @var User $user */
        $user = User::find()
            ->id($this->currentUser->id)
            ->one();

        // Check that the Db was updated.
        $I->assertSame(
            'IM A CHANGED FULLNAME',
            $user->fullName
        );
    }

    public function testTokensPageExplainsMissingOAuthKeys(FunctionalTester $I): void
    {
        $config = Craft::$app->getConfig()->getOAuth();
        $config->privateKey = '@undefined/oauth-private.key';
        $config->publicKey = '@storage/missing-oauth-public.key';

        $I->amOnPage('/' . $this->cpTrigger . '/myaccount/tokens');
        $I->seeResponseCodeIs(200);
        $I->see('Tokens can’t be created until Craft has OAuth signing keys.');
        $I->dontSeeElement('#new-token-btn');

        $config->privateKey = $config->publicKey = null;
    }

    public function testTokensPageExplainsInvalidOAuthKeys(FunctionalTester $I): void
    {
        $config = Craft::$app->getConfig()->getOAuth();
        $path = Craft::$app->getPath()->getStoragePath() . '/invalid-oauth.key';
        file_put_contents($path, 'not a key');
        $config->privateKey = $config->publicKey = $path;

        $I->amOnPage('/' . $this->cpTrigger . '/myaccount/tokens');
        $I->seeResponseCodeIs(200);
        $I->see('Tokens can’t be created until Craft has OAuth signing keys.');

        $config->privateKey = $config->publicKey = null;
        unlink($path);
    }

    public function testTokensPageIsHiddenFromNonAdminsWithoutOAuthKeys(FunctionalTester $I): void
    {
        $config = Craft::$app->getConfig()->getOAuth();
        $config->privateKey = $config->publicKey = '@storage/missing-oauth.key';

        $user = new User([
            'username' => 'tokens-' . StringHelper::randomString(6),
            'email' => 'tokens-' . StringHelper::randomString(6) . '@example.com',
            'active' => true,
        ]);
        Craft::$app->getElements()->saveElement($user, false);
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp']);
        $I->amLoggedInAs($user);

        $I->amOnPage('/' . $this->cpTrigger . '/myaccount');
        $I->seeResponseCodeIs(200);
        $I->dontSeeElement('a[href$="/myaccount/tokens"]');

        $I->amOnPage('/' . $this->cpTrigger . '/myaccount/tokens');
        $I->seeResponseCodeIs(403);

        $config->privateKey = $config->publicKey = null;
        Craft::$app->getElements()->deleteElement($user, true);
    }

    public function testTokensPageWithOAuthKeys(FunctionalTester $I): void
    {
        $config = Craft::$app->getConfig()->getOAuth();
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $config->privateKey);
        $config->publicKey = openssl_pkey_get_details($key)['key'];

        $I->amOnPage('/' . $this->cpTrigger . '/myaccount/tokens');
        $I->dontSee('Tokens can’t be created until Craft has OAuth signing keys.');
        $I->seeElement('#new-token-btn');

        $config->privateKey = $config->publicKey = null;
    }
}
