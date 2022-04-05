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
use craft\test\TestCase;
use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Unit tests for the token service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TokenTest extends TestCase
{
    /**
     * @var Tokens
     */
    protected Tokens $token;

    /**
     * @throws Exception
     */
    public function testCreateToken(): void
    {
        $dt = (new DateTime('now', new DateTimeZone('UTC')))->add(new DateInterval('P1D'));
        $token = $this->token->createToken('do/stuff', 1, $dt);

        // What actually exists now?
        $tokenRec = Token::findOne(['token' => $token]);

        // And does it match
        self::assertSame('do/stuff', $tokenRec->route);
        self::assertSame(1, $tokenRec->usageLimit);
        self::assertSame(0, $tokenRec->usageCount);
        self::assertSame($dt->format('Y-m-d H:i:s'), $tokenRec->expiryDate);
        self::assertEquals(32, strlen($token));

        $tokenRec->delete();
    }

    /**
     * @throws Exception
     */
    public function testCreateTokenDefaults(): void
    {
        Craft::$app->getConfig()->getGeneral()->defaultTokenDuration = 10000;

        // Determine what the expiry date is *supposed* to be
        $expiryDate = (new DateTime('now', new DateTimeZone('UTC')))->add(new DateInterval('PT10000S'));

        // Create the token
        $token = $this->token->createToken('do/stuff');
        self::assertSame(32, strlen($token));

        // What actually exists now?
        $tokenRec = Token::findOne(['token' => $token]);

        // And does it match
        self::assertNull($tokenRec->usageLimit);
        self::assertNull($tokenRec->usageCount);
        self::assertSame($expiryDate->format('Y-m-d H:i:s'), $tokenRec->expiryDate);

        $tokenRec->delete();
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->token = Craft::$app->getTokens();
    }
}
