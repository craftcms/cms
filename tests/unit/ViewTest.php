<?php

class ViewTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */

    // tests
    public function testNormalizeObjectTemplate()
    {
        $view = Craft::$app->view;

        $this->assertEquals('{{ object.titleWithHyphens|replace({\'-\': \'!\'}) }}', $view->normalizeObjectTemplate('{{ object.titleWithHyphens|replace({\'-\': \'!\'}) }}'));
        $this->assertEquals('{{ (_variables.foo ?? object.foo)|raw }}', $view->normalizeObjectTemplate('{foo}'));
        $this->assertEquals('{{ (_variables.foo ?? object.foo).bar|raw }}', $view->normalizeObjectTemplate('{foo.bar}'));
        $this->assertEquals('{foo : \'bar\'}', $view->normalizeObjectTemplate('{foo : \'bar\'}'));
        $this->assertEquals('{{foo}}', $view->normalizeObjectTemplate('{{foo}}'));
        $this->assertEquals('{% foo %}', $view->normalizeObjectTemplate('{% foo %}'));
        $this->assertEquals('{{ (_variables.foo ?? object.foo).fn({bar: baz})|raw }}', $view->normalizeObjectTemplate('{foo.fn({bar: baz})}'));
        $this->assertEquals('{{ (_variables.foo ?? object.foo).fn({bar: {baz: 1}})|raw }}', $view->normalizeObjectTemplate('{foo.fn({bar: {baz: 1}})}'));
        $this->assertEquals('{{ (_variables.foo ?? object.foo).fn(\'bar:baz\')|raw }}', $view->normalizeObjectTemplate('{foo.fn(\'bar:baz\')}'));
        $this->assertEquals('{{ (_variables.foo ?? object.foo).fn({\'bar\': baz})|raw }}', $view->normalizeObjectTemplate('{foo.fn({\'bar\': baz})}'));
    }
}
