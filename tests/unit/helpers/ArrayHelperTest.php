<?php
namespace app\helpers;

use \craft\helpers\ArrayHelper;

class ArrayHelperTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testToArray()
    {
        $this->assertEquals([], ArrayHelper::toArray(null));
        $this->assertEquals([1,1,1,2,2,2,3,3,3], ArrayHelper::toArray("1,1,1,2,2,2,3,3,3"));
    }

    public function testPrependOrAppend()
    {
        $array = [1,2,3];
        ArrayHelper::prependOrAppend($array,4,false);
        $this->assertSame([1,2,3,4],$array);

        $array = [1,2,3];
        ArrayHelper::prependOrAppend($array,4,true);
        $this->assertSame([4,1,2,3], $array);

    }
}