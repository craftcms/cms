<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\db;


use Codeception\Test\Unit;
use craft\db\Command;
use craft\db\Query;

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
        $this->assertInstanceOf(Command::class, \Craft::$app->getDb()->createCommand());
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testInsertDateCreated()
    {
        $sesh = $this->ensureSesh();

        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->assertSame($sesh['dateCreated'], $date->format('Y-m-d H:i:s'));
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testDateUpdatedOnInsertAndUpdate()
    {
        $sesh = $this->ensureSesh();

        // Ensure that there is a diff in dates....
        sleep(5);

        $dateTimeZone = new \DateTimeZone('UTC');
        $date = new \DateTime('now', $dateTimeZone);
        $oldDate  = new \DateTime($sesh['dateUpdated'], $dateTimeZone);

        // TODO: can $this->greaterThan be used? Might need more research....
        $this->assertGreaterThan($oldDate, $date);

        // Save it again. Ensure dateUpdated is now current.
        $sesh = $this->updateSesh($sesh);

        $this->assertSame($sesh['dateUpdated'], $date->format('Y-m-d H:i:s'));
    }


    /**
     * Ensure a session row exists
     * @return array
     * @throws \yii\db\Exception
     */
    public function ensureSesh() : array
    {
        $command = \Craft::$app->getDb()->createCommand()
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
     * @throws \yii\db\Exception
     */
    public function updateSesh($values)
    {
        $command = \Craft::$app->getDb()->createCommand()
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