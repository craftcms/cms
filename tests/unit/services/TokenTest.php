<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\records\Token;
use craft\services\Tokens;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use UnitTester;

/**
 * Unit tests for the token service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TokenTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Tokens
     */
    protected $token;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @throws Exception
     */
    public function testCreateToken()
    {
        // Don't allow modification of the DateTime by ActiveRecord's before save
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

    /**
     * @throws Exception
     */
    public function testCreateTokenDefaults()
    {
        Craft::$app->getConfig()->getGeneral()->defaultTokenDuration = 10000;

        // Determine what the expiry date is *supposed* to be
        $expiryDate = (new DateTime('now', new DateTimeZone('UTC')))->add(new DateInterval('PT10000S'));

        // Create the token
        $token = $this->token->createToken('do/stuff');
        $this->assertSame(32, strlen($token));

        // What actually exists now?
        $tokenRec = Token::findOne(['token' => $token]);

        // And does it match
        $this->assertNull($tokenRec->usageLimit);
        $this->assertNull($tokenRec->usageCount);
        $this->assertSame($expiryDate->format('Y-m-d H:i:s'), $tokenRec->expiryDate);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->token = Craft::$app->getTokens();
    }
}
