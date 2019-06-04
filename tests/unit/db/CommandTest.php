<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\db;

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
    // Public Properties
    // =========================================================================

    /**
     * @var DateTime
     */
    protected $sessionDate;

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testEnsureCommand()
    {
        $this->assertInstanceOf(Command::class, Craft::$app->getDb()->createCommand());
    }

    /**
     * @throws Exception
     */
    public function testInsertDateCreated()
    {
        $session = $this->ensureSession();

        $this->assertSame($session['dateCreated'], $this->sessionDate->format('Y-m-d H:i:s'));
    }

    /**
     * @throws Exception
     */
    public function testDateUpdatedOnInsertAndUpdate()
    {
        $session = $this->ensureSession();

        // Ensure that there is a diff in dates....
        sleep(5);

        $dateTimeZone = new DateTimeZone('UTC');
        $oldDate = new DateTime($session['dateUpdated'], $dateTimeZone);

        // Save it again. Ensure dateUpdated is the same, as nothing has changed.
        $session = $this->updateSession($session);
        $this->assertSame($oldDate->format('Y-m-d H:i:s'), $session['dateUpdated']);

        // Save it again without a dateUpdated value. Ensure dateUpdated is now current.
        $date = new DateTime('now', $dateTimeZone);

        unset($session['dateUpdated']);
        $session = $this->updateSession($session);

        $this->assertSame($date->format('Y-m-d H:i:s'), $session['dateUpdated']);
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
            ->insert(Table::SESSIONS,
                [
                    'userId' => 1,
                    'token' => 'test'
                ]
            )->execute();

        $this->assertGreaterThan(0, $command);

        return $this->getSession([
            'userId' => 1,
            'token' => 'test'
        ]);
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
        $command = Craft::$app->getDb()->createCommand()
            ->update(Table::SESSIONS, $values)->execute();

        $this->assertGreaterThan(0, $command);

        return $this->getSession([
            'userId' => $values['userId'],
            'token' => $values['token']
        ]);
    }

    /**
     * Gets a session row
     *
     * @param array $params
     * @return array
     */
    public function getSession(array $params): array
    {
        return (new Query())->from([Table::SESSIONS])->where($params)->one();
    }
}
