<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\errors\OperationAbortedException;
use craft\helpers\ElementHelper;
use craft\test\mockclasses\elements\ExampleElement;
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
class ElementHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Fixtures
    // =========================================================================

    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryFixture::class
            ]
        ];
    }

    // Tests
    // =========================================================================

    /**
     * @dataProvider createSlugDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testCreateSlug($result, $input)
    {
        $glue = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        $result = str_replace('[separator-here]', $glue, $result);

        $this->assertSame($result, ElementHelper::createSlug($input));
    }

    /**
     *
     */
    public function testLowerRemoveFromCreateSlug()
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $general->allowUppercaseInSlug = false;

        $this->assertSame('word' . $general->slugWordSeparator . 'word', ElementHelper::createSlug('word WORD'));
    }

    /**
     * @dataProvider doesUriHaveSlugTagDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testDoesUriFormatHaveSlugTag($result, $input)
    {
        $doesIt = ElementHelper::doesUriFormatHaveSlugTag($input);
        $this->assertSame($result, $doesIt);
        $this->assertIsBool($doesIt);
    }

    /**
     * @dataProvider setUniqueUriDataProvider
     *
     * @param $result
     * @param $config
     * @throws OperationAbortedException
     */
    public function testSetUniqueUri($result, $config)
    {
        $example = new ExampleElement($config);
        $this->assertNull(ElementHelper::setUniqueUri($example));

        foreach ($result as $key => $res) {
            $this->assertSame($res, $example->$key);
        }
    }

    /**
     *
     */
    public function testMaxSlugIncrementDoesntThrow()
    {
        Craft::$app->getConfig()->getGeneral()->maxSlugIncrement = 0;
        $this->tester->expectThrowable(OperationAbortedException::class, function() {
            $el = new ExampleElement(['uriFormat' => 'test/{slug}']);
            ElementHelper::setUniqueUri($el);
        });
    }

    /**
     *
     */
    public function testMaxLength()
    {
        try {
            $el = new ExampleElement([
                'uriFormat' => 'test/{slug}',
                'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssss22ssss'
            ]);
            ElementHelper::setUniqueUri($el);
            $result = true;
        } catch (Exception $exception) {
            $result = false;
        }

        $this->assertTrue($result);
    }

    /**
     *
     */
    public function testSetNextOnPrevElement()
    {
        $editable = [
            $one = new ExampleElement(['id' => '1']),
            $two = new ExampleElement(['id' => '2']),
            $three = new ExampleElement(['id' => '3'])
        ];

        ElementHelper::setNextPrevOnElements($editable);
        $this->assertNull($one->getPrev());

        $this->assertSame($two, $one->getNext());
        $this->assertSame($two, $one->getNext());
        $this->assertSame($two, $three->getPrev());

        $this->assertNull($three->getNext());
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function createSlugDataProvider(): array
    {
        return [
            ['word[separator-here]Word', 'wordWord'],
            ['word[separator-here]word', 'word word'],
            ['word', 'word'],
            ['123456789', '123456789'],
            ['abc--dfg', 'abc...dfg'],
            ['abc--dfg', 'abc...(dfg)'],
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
            [['uri' => 'test'], ['uriFormat' => 'test/{slug}']],
            [['uri' => 'test/test'], ['uriFormat' => 'test/{slug}', 'slug' => 'test']],
            [['uri' => 'test/tes.!@#$%^&*()_t'], ['uriFormat' => 'test/{slug}', 'slug' => 'tes.!@#$%^&*()_t']],

            // 254 chars.
            [['uri' => 'test/asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss'], ['uriFormat' => 'test/{slug}', 'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss']],

            [['uri' => 'some-uri/With--URL--2--1'], ['uriFormat' => 'some-uri/{slug}', 'slug' => 'With--URL--2']],
            [['uri' => 'some-uri/With--URL--1--1'], ['uriFormat' => 'some-uri/{slug}', 'slug' => 'With--URL--1']],
            [['uri' => 'different-uri/With--URL--1'], ['uriFormat' => 'different-uri/{slug}', 'slug' => 'With--URL--1']],
        ];
    }
}
