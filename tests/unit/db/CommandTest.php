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
use craft\test\TestCase;
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
class CommandTest extends TestCase
{
    /**
     * @var DateTime
     */
    protected DateTime $sessionDate;

    /**
     * @var array
     */
    private array $_sessionData = [
        'userId' => 1,
        'token' => 'test',
    ];

    /**
     *
     */
    public function testEnsureCommand(): void
    {
        self::assertInstanceOf(Command::class, Craft::$app->getDb()->createCommand());
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
     * @param array $values
     * @return array
     * @throws Exception
     */
    public function updateSession(array $values): array
    {
        $condition = [
            'userId' => $values['userId'],
            'token' => $values['token'],
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
