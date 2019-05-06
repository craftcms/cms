<?php
/**
 * @copyright Copyright (c) Global Network Group
 */


namespace craftunit\services;


use Codeception\Test\Unit;
use Craft;
use craft\records\Token;
use craft\services\Tokens;
use DateInterval;
use DateTime;
use DateTimeZone;
use UnitTester;

/**
 * Class TokenServiceTest
 *
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 */
class TokenTest extends Unit
{
    /**
     * @var UnitTester $tester
     */
    protected $tester;

    /**
     * @var Tokens $token
     */
    protected $token;

    public function _before()
    {
        parent::_before();

        $this->token = Craft::createObject(Tokens::class);
    }

    public function testCreateToken()
    {
        // Dont allow modification of the DateTime by ActiveRecord's before save
        Craft::$app->setTimeZone('UTC');

        $dt = new DateTime('2019-12-12 13:00:00');
        $token = $this->token->createToken('do/stuff', 1, $dt);

        // What actually exists now?
        $tokenRec = Token::findOne(['token' => $token]);

        // And does it match
        $this->assertSame('do/stuff', $tokenRec->route);
        $this->assertSame(1, $tokenRec->usageLimit);
        $this->assertSame(0, $tokenRec->usageCount);
        $this->assertSame('2019-12-12 13:00:00', $tokenRec->expiryDate);
        $this->assertEquals(32, strlen($token));
    }

    public function testCreateTokenDefaults()
    {
        Craft::$app->getConfig()->getGeneral()->defaultTokenDuration = 10000;

        // Dont allow modification of the DateTime by ActiveRecord's before save
        Craft::$app->setTimeZone('UTC');
        $token = $this->token->createToken('do/stuff');

        // What actually exists now?
        $tokenRec = Token::findOne(['token' => $token]);

        // Determine what the expiry date is *supposed* to be
        $interval = new DateInterval('PT10000S');
        $expiryDate = new DateTime(null, new DateTimeZone('UTC'));
        $expiryDate->add($interval);

        // And does it match
        $this->assertNull($tokenRec->usageLimit);
        $this->assertNull($tokenRec->usageCount);
        $this->assertSame($expiryDate->format('Y-m-d H:i:s'), $tokenRec->expiryDate);
        $this->assertEquals(32, strlen($token));

    }

}
