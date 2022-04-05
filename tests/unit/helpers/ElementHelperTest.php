<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Stub;
use Craft;
use craft\db\Command;
use craft\errors\OperationAbortedException;
use craft\helpers\ElementHelper;
use craft\test\mockclasses\elements\ExampleElement;
use craft\test\TestCase;
use crafttests\fixtures\EntryFixture;
use Exception;
use UnitTester;

/**
 * Class ElementHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ElementHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider generateSlugDataProvider
     * @param string $expected
     * @param string $input
     * @param bool|null $ascii
     * @param string|null $language
     */
    public function testGenerateSlug(string $expected, string $input, ?bool $ascii = null, ?string $language = null): void
    {
        $glue = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        $expected = str_replace('[separator-here]', $glue, $expected);

        self::assertSame($expected, ElementHelper::generateSlug($input, $ascii, $language));
    }

    /**
     * @dataProvider normalizeSlugDataProvider
     * @param string $expected
     * @param string $slug
     */
    public function testNormalizeSlug(string $expected, string $slug): void
    {
        $glue = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        $expected = str_replace('[separator-here]', $glue, $expected);

        self::assertSame($expected, ElementHelper::normalizeSlug($slug));
    }

    /**
     *
     */
    public function testLowerRemoveFromCreateSlug(): void
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $general->allowUppercaseInSlug = false;

        self::assertSame('word' . $general->slugWordSeparator . 'word', ElementHelper::normalizeSlug('word WORD'));
    }

    /**
     * @dataProvider doesUriHaveSlugTagDataProvider
     * @param bool $expected
     * @param string $uriFormat
     */
    public function testDoesUriFormatHaveSlugTag(bool $expected, string $uriFormat): void
    {
        self::assertSame($expected, ElementHelper::doesUriFormatHaveSlugTag($uriFormat));
    }

    /**
     * @dataProvider setUniqueUriDataProvider
     * @param array $expected
     * @param array $config
     * @param int $duplicates
     * @throws OperationAbortedException
     */
    public function testSetUniqueUri(array $expected, array $config, int $duplicates = 0): void
    {
        if ($duplicates) {
            $db = Craft::$app->getDb();
            $this->tester->mockDbMethods([
                'createCommand' => function($sql, $params) use (&$duplicates, &$db) {
                    /* @var Command $command */
                    $command = Stub::construct(Command::class, [
                        ['db' => $db, 'sql' => $sql],
                    ], [
                        'queryScalar' => function() use (&$duplicates) {
                            return $duplicates-- ? 1 : 0;
                        },
                    ]);
                    $command->bindValues($params);
                    return $command;
                },
            ]);
        }

        $example = new ExampleElement($config);
        ElementHelper::setUniqueUri($example);

        foreach ($expected as $key => $res) {
            self::assertSame($res, $example->$key);
        }
    }

    /**
     *
     */
    public function testMaxSlugIncrementDoesntThrow(): void
    {
        $oldValue = Craft::$app->getConfig()->getGeneral()->maxSlugIncrement;
        Craft::$app->getConfig()->getGeneral()->maxSlugIncrement = 0;

        $this->tester->expectThrowable(OperationAbortedException::class, function() {
            $el = new ExampleElement(['uriFormat' => 'test/{slug}']);
            ElementHelper::setUniqueUri($el);
        });

        // reset
        Craft::$app->getConfig()->getGeneral()->maxSlugIncrement = $oldValue;
    }

    /**
     *
     */
    public function testMaxLength(): void
    {
        try {
            $el = new ExampleElement([
                'uriFormat' => 'test/{slug}',
                'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssss22ssss',
            ]);
            ElementHelper::setUniqueUri($el);
            $result = true;
        } catch (Exception) {
            $result = false;
        }

        self::assertTrue($result);
    }

    /**
     *
     */
    public function testSetNextOnPrevElement(): void
    {
        $editable = [
            $one = new ExampleElement(['id' => '1']),
            $two = new ExampleElement(['id' => '2']),
            $three = new ExampleElement(['id' => '3']),
        ];

        ElementHelper::setNextPrevOnElements($editable);
        self::assertNull($one->getPrev());

        self::assertSame($two, $one->getNext());
        self::assertSame($two, $one->getNext());
        self::assertSame($two, $three->getPrev());

        self::assertNull($three->getNext());
    }

    /**
     * @dataProvider rootSourceDataProvider
     */
    public function testRootSource(string $expected, string $sourceKey): void
    {
        $this->assertEquals($expected, ElementHelper::rootSourceKey($sourceKey));
    }

    /**
     * @return array
     */
    public function generateSlugDataProvider(): array
    {
        return [
            ['wordWord', 'wordWord'],
            ['word[separator-here]word', 'word word'],
            ['foo[separator-here]0', 'foo 0'],
            ['word', 'word'],
            ['123456789', '123456789'],
            ['abc[separator-here]dfg', 'abc...dfg'],
            ['abc[separator-here]dfg', 'abc...(dfg)'],
            ['A[separator-here]B[separator-here]C', 'A-B-C'], // https://github.com/craftcms/cms/issues/4266
            ['test[separator-here]slug', 'test_slug'],
            ['Audi[separator-here]S8[separator-here]4E[separator-here]2006[separator-here]2010', 'Audi S8 4E (2006-2010)'], // https://github.com/craftcms/cms/issues/4607
            ['こんにちは', 'こんにちは', false], // https://github.com/craftcms/cms/issues/4628
            ['Сертификация', 'Сертификация', false], // https://github.com/craftcms/cms/issues/1535
        ];
    }

    /**
     * @return array
     */
    public function normalizeSlugDataProvider(): array
    {
        return [
            ['wordWord', 'wordWord'],
            ['word[separator-here]word', 'word word'],
            ['foo[separator-here]0', 'foo 0'],
            ['word', 'word'],
            ['123456789', '123456789'],
            ['abc...dfg', 'abc...dfg'],
            ['abc...dfg', 'abc...(dfg)'],
            ['__home__', '__home__'], // https://github.com/craftcms/cms/issues/4096
            ['A-B-C', 'A-B-C'], // https://github.com/craftcms/cms/issues/4266
            ['test_slug', 'test_slug'],
            ['Audi[separator-here]S8[separator-here]4E[separator-here]2006-2010', 'Audi S8 4E (2006-2010)'], // https://github.com/craftcms/cms/issues/4607
            ['こんにちは', 'こんにちは'], // https://github.com/craftcms/cms/issues/4628
            ['Сертификация', 'Сертификация'], // https://github.com/craftcms/cms/issues/1535
        ];
    }

    /**
     * @return array
     */
    public function doesUriHaveSlugTagDataProvider(): array
    {
        return [
            [false, ''],
            [true, '{slug}'],
            [true, 'entry/slug'],
            [true, 'entry/{slug}'],
            [false, 'entry/{notASlug}'],
            [false, 'entry/{SLUG}'],
            [false, 'entry/data'],
        ];
    }

    /**
     * @return array
     */
    public function setUniqueUriDataProvider(): array
    {
        return [
            [['uri' => null], ['uriFormat' => null]],
            [['uri' => null], ['uriFormat' => '']],
            [['uri' => 'craft'], ['uriFormat' => '{slug}', 'slug' => 'craft']],
            [['uri' => 'craft--3'], ['uriFormat' => '{slug}', 'slug' => 'craft'], 2],
            [['uri' => 'testing-uri-longer-than-255-chars/arrêté-du-24-décembre-2020-portant-modification-de-larrêté-du-4-décembre-2020-fixant-la-liste-des-personnes-autorisées-à-exercer-en-france-la-profession-de-médecin-dans-la-spécialité-gériatrie-en-application-des-dispos--2'], ['uriFormat' => 'testing-uri-longer-than-255-chars/{slug}', 'slug' => 'arrêté-du-24-décembre-2020-portant-modification-de-larrêté-du-4-décembre-2020-fixant-la-liste-des-personnes-autorisées-à-exercer-en-france-la-profession-de-médecin-dans-la-spécialité-gériatrie-en-application-des-dispositions-de-larti'], 1],
            [['uri' => 'test'], ['uriFormat' => 'test/{slug}']],
            [['uri' => 'test/test'], ['uriFormat' => 'test/{slug}', 'slug' => 'test']],
            [['uri' => 'test/tes.!@#$%^&*()_t'], ['uriFormat' => 'test/{slug}', 'slug' => 'tes.!@#$%^&*()_t']],

            // 254 chars.
            [['uri' => 'test/asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss'], ['uriFormat' => 'test/{slug}', 'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss']],

            [['uri' => 'some-uri/With--URL--2--2'], ['uriFormat' => 'some-uri/{slug}', 'slug' => 'With--URL--2']],
            [['uri' => 'some-uri/With--URL--1--2'], ['uriFormat' => 'some-uri/{slug}', 'slug' => 'With--URL--1']],
            [['uri' => 'different-uri/With--URL--1'], ['uriFormat' => 'different-uri/{slug}', 'slug' => 'With--URL--1']],
        ];
    }

    /**
     * @return array
     */
    public function rootSourceDataProvider(): array
    {
        return [
            ['foo', 'foo'],
            ['foo', 'foo/bar'],
            ['foo', 'foo/bar/baz'],
        ];
    }
}
