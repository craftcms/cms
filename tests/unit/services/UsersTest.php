<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\base\Element;
use craft\db\Table;
use craft\elements\User;
use craft\services\Users;
use UnitTester;
use yii\base\InvalidArgumentException;

/**
 * Unit tests for the Users service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UsersTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Users
     */
    protected $users;

    /**
     * @var User
     */
    protected $pendingUser;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    public function testUserActivation()
    {
        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser();

        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertSame('jsmith', $user->username);
    }

    public function testUserActivationEmailAsUsernameWithAnUnverifedEmail()
    {
        // Set useEmailAsUsername to true and add an unverified email.
        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = true;
        $this->saveElement($this->pendingUser);

        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser();

        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertSame('jsmith@gmail.com', $user->username);
    }

    public function testUserActivationEmailAsUsernameWithNoUnverifedEmail()
    {
        // Run the same test as above but without an unverified email.
        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = true;

        // Remove the unverifiedEmail property from the user record - meaning no username will be set.
        $this->pendingUser->unverifiedEmail = null;

        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser();

        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertSame('jsmith', $user->username);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param int|null $userId
     * @return User|null
     */
    protected function getUser(int $userId = null)
    {
        if (!$userId) {
            $userId = $this->pendingUser->id;
        }

        return Craft::$app->getUsers()->getUserById($userId);
    }

    /**
     * @todo Use fixtures?
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->users = Craft::$app->getUsers();

        $this->pendingUser = new User(
            [
                'firstName' => 'John',
                'lastName' => 'Smith',
                'username' => 'jsmith',
                'unverifiedEmail' => 'jsmith@gmail.com',
                'email' => 'jsmith@gmail.com',
                'pending' => true
            ]
        );

        $this->saveElement($this->pendingUser);
    }

    /**
     * @param Element $element
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    protected function saveElement(Element $element)
    {
        if (!Craft::$app->getElements()->saveElement($element)) {
            throw new InvalidArgumentException(
                implode(', ', $element->getErrorSummary(true))
            );
        }

        return true;
    }
}
