<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\UsersController;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\mail\Message;
use craft\records\User as UserRecord;
use craft\test\TestCase;
use UnitTester;
use yii\web\Response;

/**
 * Unit tests for UsersController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.4
 */
class UsersControllerTest extends TestCase
{
    private const EMAIL = 'registrant@example.com';

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private array $originalUserSettings;
    private bool $originalDeferPassword;
    private bool $originalAutoLogin;
    private bool $originalRequireUserAgentAndIp;
    private ?string $originalRequestMethod = null;

    /**
     * @dataProvider publicRegistrationDataProvider
     */
    public function testPublicRegistration(
        bool $deactivateByDefault,
        bool $requireEmailVerification,
        bool $deferPassword,
        string $expectedStatus,
        ?string $expectedLink,
        bool $expectLoggedIn,
    ): void {
        $this->setUserSettings([
            'allowPublicRegistration' => true,
            'deactivateByDefault' => $deactivateByDefault,
            'requireEmailVerification' => $requireEmailVerification,
        ]);

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->deferPublicRegistrationPassword = $deferPassword;
        $generalConfig->autoLoginAfterAccountActivation = true;
        // Test requests don't have a user agent or IP
        $generalConfig->requireUserAgentAndIpForSession = false;

        $params = [
            'username' => 'registrant',
            'email' => self::EMAIL,
        ];
        if (!$deferPassword) {
            $params['password'] = 'SuperSecret123!';
        }

        $response = $this->saveUser($params);
        self::assertSame(200, $response->getStatusCode(), (string)json_encode($response->data));

        $user = User::find()
            ->email(self::EMAIL)
            ->status(null)
            ->addSelect(['users.password'])
            ->one();

        self::assertNotNull($user, 'The user was saved.');
        self::assertSame(self::EMAIL, $user->email);
        self::assertSame($deferPassword, $user->password === null);
        self::assertSame($expectedStatus, $user->getStatus());

        // Activated users shouldn't have a lingering unverified email
        if ($expectedStatus === User::STATUS_ACTIVE) {
            self::assertNull($user->unverifiedEmail);
        }

        $emails = $this->tester->grabSentEmails();
        if ($expectedLink === null) {
            self::assertEmpty($emails, 'No activation email was sent.');
        } else {
            self::assertCount(1, $emails);
            /** @var Message $email */
            $email = $emails[0];
            self::assertSame('account_activation', $email->key);
            self::assertSame([self::EMAIL], array_keys($email->getTo()));
            self::assertStringContainsString($expectedLink, urldecode($email->variables['link']));
        }

        $identity = Craft::$app->getUser()->getIdentity();
        if ($expectLoggedIn) {
            self::assertNotNull($identity, 'The user was logged in.');
            self::assertSame($user->id, $identity->id);
        } else {
            self::assertNull($identity, 'The user was not logged in.');
        }
    }

    /**
     * @return array
     */
    public static function publicRegistrationDataProvider(): array
    {
        return [
            // deactivateByDefault, requireEmailVerification, deferPassword, status, link, logged in
            'verification required' => [false, true, false, User::STATUS_PENDING, 'verifyemail?code=', false],
            'verification required, deferred password' => [false, true, true, User::STATUS_PENDING, 'setpassword?code=', false],
            'no verification' => [false, false, false, User::STATUS_ACTIVE, null, true],
            // https://github.com/craftcms/cms/issues/19610
            'no verification, deferred password' => [false, false, true, User::STATUS_ACTIVE, 'setpassword?code=', true],
            'deactivated, verification required' => [true, true, false, User::STATUS_INACTIVE, null, false],
            'deactivated, verification required, deferred password' => [true, true, true, User::STATUS_INACTIVE, null, false],
            'deactivated, no verification' => [true, false, false, User::STATUS_INACTIVE, null, false],
            'deactivated, no verification, deferred password' => [true, false, true, User::STATUS_INACTIVE, null, false],
        ];
    }

    public function testFailedPublicRegistrationDoesNotExposeOrModifyInactiveUser(): void
    {
        $this->setUserSettings([
            'allowPublicRegistration' => true,
            'requireEmailVerification' => true,
        ]);

        $existingUser = $this->createInactiveUser();
        Craft::$app->getUserPermissions()->saveUserPermissions($existingUser->id, ['accesscp']);

        $response = $this->saveUser([
            'username' => 'registrant',
            'email' => self::EMAIL,
            'password' => 'x',
        ]);

        self::assertSame(400, $response->getStatusCode());
        self::assertArrayHasKey('password', $response->data['errors']);
        self::assertNull($response->data['user']['id']);
        self::assertStringNotContainsString('Existing', (string)json_encode($response->data));
        self::assertSame(['accesscp'], Craft::$app->getUserPermissions()->getPermissionsByUserId($existingUser->id));
    }

    public function testPublicRegistrationResetsInactiveUserPermissions(): void
    {
        $this->setUserSettings([
            'allowPublicRegistration' => true,
            'requireEmailVerification' => true,
        ]);

        $existingUser = $this->createInactiveUser();
        Craft::$app->getUserPermissions()->saveUserPermissions($existingUser->id, ['accesscp']);

        $response = $this->saveUser([
            'username' => 'registrant',
            'email' => self::EMAIL,
            'password' => 'SuperSecret123!',
        ]);

        self::assertSame(200, $response->getStatusCode(), (string)json_encode($response->data));
        self::assertStringNotContainsString('Existing', (string)json_encode($response->data));
        self::assertSame([], Craft::$app->getUserPermissions()->getPermissionsByUserId($existingUser->id));
    }

    /**
     * @dataProvider suspendedInactiveUserDataProvider
     */
    public function testPublicRegistrationIgnoresSuspendedInactiveUser(bool $requireEmailVerification): void
    {
        $this->setUserSettings([
            'allowPublicRegistration' => true,
            'requireEmailVerification' => $requireEmailVerification,
        ]);

        $existingUser = $this->createInactiveUser(['suspended' => true]);

        $this->saveUser([
            'username' => 'registrant',
            'email' => self::EMAIL,
            'password' => 'SuperSecret123!',
        ]);

        // The registration must not take over the suspended account, so it can't be unsuspended by verifying it
        $record = UserRecord::findOne($existingUser->id);
        self::assertTrue((bool)$record->suspended);
        self::assertFalse((bool)$record->active);
        self::assertFalse((bool)$record->pending);
        self::assertNull($record->verificationCode);
        self::assertNull($record->password);
        self::assertSame('existing', $record->username);
    }

    /**
     * @return array
     */
    public static function suspendedInactiveUserDataProvider(): array
    {
        return [
            'verification required' => [true],
            'no verification' => [false],
        ];
    }

    private function createInactiveUser(array $attributes = []): User
    {
        $user = new User(array_merge([
            'username' => 'existing',
            'email' => self::EMAIL,
            'fullName' => 'Existing Person',
            'active' => false,
            'pending' => false,
        ], $attributes));
        self::assertTrue(Craft::$app->getElements()->saveElement($user), (string)json_encode($user->getErrors()));
        return $user;
    }

    private function setUserSettings(array $settings): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->set('users', array_merge($projectConfig->get('users') ?? [], $settings));
    }

    private function saveUser(array $params): Response
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setBodyParams($params);
        $request->getHeaders()->set('Accept', 'application/json');

        $controller = new UsersController('users', Craft::$app);
        return $controller->actionSaveUser();
    }

    protected function _before(): void
    {
        parent::_before();

        Craft::$app->edition = CmsEdition::Pro;
        Craft::$app->getUser()->logout(false);

        $this->originalUserSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->originalDeferPassword = $generalConfig->deferPublicRegistrationPassword;
        $this->originalAutoLogin = $generalConfig->autoLoginAfterAccountActivation;
        $this->originalRequireUserAgentAndIp = $generalConfig->requireUserAgentAndIpForSession;
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->logout(false);

        foreach (User::find()->email(self::EMAIL)->status(null)->all() as $user) {
            $this->tester->deleteElement($user);
        }

        Craft::$app->getProjectConfig()->set('users', $this->originalUserSettings);
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->deferPublicRegistrationPassword = $this->originalDeferPassword;
        $generalConfig->autoLoginAfterAccountActivation = $this->originalAutoLogin;
        $generalConfig->requireUserAgentAndIpForSession = $this->originalRequireUserAgentAndIp;

        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::_after();
    }
}
