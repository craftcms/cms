<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use Codeception\Test\Unit;
use Craft;
use craft\db\Command;
use craft\db\Query;
use craft\db\Table;
use DateTime;
use DateTimeZone;
use yii\db\Exception;

/**
 * Unit tests for CommandTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class CommandTest extends Unit
{
    /**
     * @var DateTime
     */
    protected $sessionDate;

    /**
     * @var array
     */
    private $_sessionData = [
        'userId' => 1,
        'token' => 'test'
    ];

    /**
     *
     */
    public function testEnsureCommand()
    {
        self::assertInstanceOf(Command::class, Craft::$app->getDb()->createCommand());
    }

    /**
     * @throws Exception
     */
    public function testInsertDateCreated()
    {
        $session = $this->ensureSession();
        self::assertSame($session['dateCreated'], $this->sessionDate->format('Y-m-d H:i:s'));
        $this->clearSession();
    }

    /**
     * @throws Exception
     */
    public function testDateUpdatedOnInsertAndUpdate()
    {
        $session = $this->ensureSession();

        // Ensure that there is a diff in dates....
        sleep(1);

        // Save it again without a dateUpdated value. Ensure dateUpdated is now current.
        $date = new DateTime('now', new DateTimeZone('UTC'));
        unset($session['dateUpdated']);
        $session = $this->updateSession($session);

        self::assertSame($date->format('Y-m-d H:i:s'), $session['dateUpdated']);
        $this->clearSession();
    }

    /**
     * Ensure a session row exists
     *
     * @return array
     * @throws Exception
     */
    public function ensureSession(): array
    {
        $this->sessionDate = new DateTime('now', new DateTimeZone('UTC'));

        $command = Craft::$app->getDb()->createCommand()
            ->insert(Table::SESSIONS, $this->_sessionData)
            ->execute();

        self::assertGreaterThan(0, $command);

        return $this->getSession($this->_sessionData);
    }

    /**
     * @throws Exception
     */
    public function clearSession()
    {
        Craft::$app->getDb()->createCommand()
            ->truncateTable(Table::SESSIONS)
            ->execute();
    }

    /**
     * Updates a session row.
     *
     * @param $values
     * @return array
     * @throws Exception
     */
    public function updateSession($values): array
    {
        $condition = [
            'userId' => $values['userId'],
            'token' => $values['token']
        ];

        Craft::$app->getDb()->createCommand()
            ->update(Table::SESSIONS, $values, $condition)
            ->execute();

        return $this->getSession($condition);
    }

    /**
     * Gets a session row
     *
     * @param array $condition
     * @return array
     */
    public function getSession(array $condition): array
    {
        return (new Query())->from([Table::SESSIONS])->where($condition)->one();
    }
}
