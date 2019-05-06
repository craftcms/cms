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
use DateTime;
use DateTimeZone;
use yii\db\Exception;

/**
 * Unit tests for CommandTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class CommandTest extends Unit
{
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

        $date = new DateTime('now', new DateTimeZone('UTC'));

        $this->assertSame($session['dateCreated'], $date->format('Y-m-d H:i:s'));
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
        $date = new DateTime('now', $dateTimeZone);
        $oldDate  = new DateTime($session['dateUpdated'], $dateTimeZone);

        // TODO: can $this->greaterThan be used? Might need more research....
        $this->assertGreaterThan($oldDate, $date);

        // Save it again. Ensure dateUpdated is now current.
        $session = $this->updateSession($session);

        $this->assertSame($session['dateUpdated'], $date->format('Y-m-d H:i:s'));
    }


    /**
     * Ensure a session row exists
     * @return array
     * @throws Exception
     */
    public function ensureSession() : array
    {
        $command = Craft::$app->getDb()->createCommand()
            ->insert('{{%sessions}}',
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
     * Updates a session row
     * @param $values
     * @return array
     * @throws Exception
     */
    public function updateSession($values): array
    {
        $command = Craft::$app->getDb()->createCommand()
            ->update('{{%sessions}}', $values)->execute();

        $this->assertGreaterThan(0, $command);

        return $this->getSession([
            'userId' => $values['userId'],
            'token' => $values['token']
        ]);
    }

    /**
     * Gets a session row
     * @param array $params
     * @return array
     */
    public function getSession(array $params) : array
    {
        return (new Query())->select('*')->from('{{%sessions}}')->where($params)->one();
    }
}