<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use craft\helpers\UrlHelper;
use craft\test\TestCase;
use craft\web\RedirectRule;
use Psr\Http\Message\UriInterface;
use UnitTester;

/**
 * Unit tests for RedirectRuleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class RedirectRuleTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider getMatchDataProvider
     * @param string|null $expected
     * @param array $config
     */
    public function testGetMatch(?string $expected, string $path, ?string $url, array $config): void
    {
        $this->tester->mockCraftMethods('request', [
            'getFullPath' => $path,
            'getAbsoluteUrl' => $url,
        ]);
        $rule = new RedirectRule($config);
        $this->assertSame($expected, $rule->getMatch());
    }

    public static function getMatchDataProvider(): array
    {
        return [
            // Path match (case-insensitive by default)
            [
                null,
                'nope',
                UrlHelper::url('nope'),
                [
                    'from' => 'redirect/from',
                    'to' => 'redirect/to',
                ],
            ],
            [
                'redirect/to',
                'redirect/from',
                UrlHelper::url('redirect/from'),
                [
                    'from' => 'redirect/from',
                    'to' => 'redirect/to',
                ],
            ],
            [
                'redirect/to',
                'redirect/from/$special.chars',
                UrlHelper::url('redirect/from/$special.chars'),
                [
                    'from' => 'redirect/from/$special.chars',
                    'to' => 'redirect/to',
                ],
            ],

            // Path match with Yii URL Rule named parameters
            // https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#named-parameters
            [
                'redirect/to/abc123',
                'redirect/from/foo/abc123',
                UrlHelper::url('redirect/from/foo/abc123'),
                [
                    'from' => 'redirect/from/foo/<bar:{handle}>',
                    'to' => 'redirect/to/<bar>',
                ],
            ],
            [
                'redirect/to/abc-123',
                'redirect/from/foo/abc-123',
                UrlHelper::url('redirect/from/foo/abc-123'),
                [
                    'from' => 'redirect/from/foo/<bar:{slug}>',
                    'to' => 'redirect/to/<bar>',
                ],
            ],
            [
                'redirect/to/55a89943-19a6-4f5e-8db7-8950f7f66e98',
                'redirect/from/foo/55a89943-19a6-4f5e-8db7-8950f7f66e98',
                UrlHelper::url('redirect/from/foo/55a89943-19a6-4f5e-8db7-8950f7f66e98'),
                [
                    'from' => 'redirect/from/foo/<bar:{uid}>',
                    'to' => 'redirect/to/<bar>',
                ],
            ],

            // Path match (case-sensitive)
            [
                'https://redirect.to/2025/01',
                'redirect/FROM/2025/01',
                UrlHelper::url('redirect/FROM/2025/01'),
                [
                    'from' => 'redirect/FROM/<year:\d{4}>/<month>',
                    'to' => 'https://redirect.to/<year>/<month>',
                    'caseSensitive' => true,
                ],
            ],
            [
                null,
                'redirect/from/2025/01',
                UrlHelper::url('redirect/from/2025/01'),
                [
                    'from' => 'redirect/FROM/<year:\d{4}>/<month>',
                    'to' => 'https://redirect.to/<year>/<month>',
                    'caseSensitive' => true,
                ],
            ],

            // Custom match callback
            [
                'redirect/to/abc123',
                'redirect/from',
                UrlHelper::url('redirect/from', ['bar' => 'abc123']),
                [
                    'match' => function(UriInterface $url): ?string {
                        parse_str($url->getQuery(), $params);
                        return isset($params['bar'])
                            ? sprintf('redirect/to/%s', $params['bar'])
                            : null;
                    },
                ],
            ],
        ];
    }
}
