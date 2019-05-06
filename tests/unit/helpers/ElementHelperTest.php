<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use Craft;
use craft\errors\OperationAbortedException;
use craft\helpers\ElementHelper;
use craft\test\mockclasses\elements\ExampleElement;
use UnitTester;

/**
 * Class ElementHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class ElementHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider createSlugData
     *
     * @param $result
     * @param $input
     */
    public function testCreateSlug($result, $input)
    {
        $glue = Craft::$app->getConfig()->getGeneral()->slugWordSeparator;
        $result = str_replace('[seperator-here]', $glue, $result);

        $this->assertSame($result, ElementHelper::createSlug($input));
    }

    public function createSlugData(): array
    {
        return [
            ['word[seperator-here]Word', 'wordWord'],
            ['word[seperator-here]word', 'word word'],
            ['word', 'word'],
            ['123456789', '123456789'],
            ['abc...dfg', 'abc...dfg'],
            ['abc...dfg', 'abc...(dfg)'],
        ];
    }

    public function testLowerRemoveFromCreateSlug()
    {
        $general =  Craft::$app->getConfig()->getGeneral();
        $general->allowUppercaseInSlug = false;

        $this->assertSame('word'.$general->slugWordSeparator.'word', ElementHelper::createSlug('word WORD'));
    }

    /**
     * @dataProvider doesuriHaveSlugTagData
     * @param $result
     * @param $input
     */
    public function testDoesUriFormatHaveSlugTag($result, $input)
    {
        $doesIt = ElementHelper::doesUriFormatHaveSlugTag($input);
        $this->assertSame($result, $doesIt);
        $this->assertInternalType('boolean', $doesIt);
    }
    public function doesuriHaveSlugTagData(): array
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
     * @dataProvider setUniqueUriData
     * @param $result
     * @param $config
     * @throws OperationAbortedException
     */
    public function testSetUniqueUri($result, $config)
    {
        $example = new ExampleElement($config);
        $uri = ElementHelper::setUniqueUri($example);

        $this->assertNull($uri);
        foreach ($result as $key => $res) {
            $this->assertSame($res, $example->$key);
        }
    }
    public function setUniqueUriData(): array
    {
        return [
            [['uri' => null], ['uriFormat' => null]],
            [['uri' => ''], ['uriFormat' => '']],
            [['uri' => 'craft'], ['uriFormat' => '{slug}', 'slug' => 'craft']],
            [['uri' => 'test'], ['uriFormat' => 'test/{slug}']],
            [['uri' => 'test/test'], ['uriFormat' => 'test/{slug}', 'slug' => 'test']],
            [['uri' => 'test/tes.!@#$%^&*()_t'], ['uriFormat' => 'test/{slug}', 'slug' => 'tes.!@#$%^&*()_t']],

            // 254 chars.
            [['uri' => 'test/asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss'], ['uriFormat' => 'test/{slug}', 'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssssssss']],

            // TODO: Test the line 100.
            // TODO: Test _isUniqueUri and setup fixtures that add data to elements_sites
        ];
    }
    public function testMaxSlugIncrementExceptions()
    {
        Craft::$app->getConfig()->getGeneral()->maxSlugIncrement = 0;
        $this->tester->expectThrowable(OperationAbortedException::class, function () {
            $el = new ExampleElement(['uriFormat' => 'test/{slug}']);
            ElementHelper::setUniqueUri($el);
        });
    }
    public function maxLength()
    {
        // 256 length slug. Oh no we dont.
        $this->tester->expectThrowable(OperationAbortedException::class, function () {
            $el = new ExampleElement([
                'uriFormat' => 'test/{slug}',
                'slug' => 'asdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadssssssssssssssssssssssssssssssssssssssssssssssadsasdsdaadsadsasddasadsdasasasdsadsadaasdasdadsssssssssssssssssssssssssssssssssssssssss22ssss'
            ]);
            ElementHelper::setUniqueUri($el);
        });
    }

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
}
