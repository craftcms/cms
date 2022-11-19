<?php

namespace ui;

use Craft;
use craft\helpers\Html;
use craft\test\TestCase;

class ButtonComponentTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function assetHasClass(string $class, string $output)
    {
        $attributes = Html::parseTagAttributes($output);
        $classes = $attributes['class'];

        self::assertContains($class, $classes);
    }

    /**
     * @dataProvider dataProviderTestAttributes
     * @param string $needle
     * @param array $props
     * @return void
     */
    public function testAttributes(string $needle, array $props = []): void
    {
        $output = Craft::$app->getUi()->createAndRender('button', $props);
        self::assertStringContainsString($needle, $output);
    }


    public function dataProviderTestAttributes(): array
    {
        return [
            'data attribute' => ['data-test="some-value"', ['data-test' => 'some-value']],
            'data attribute complex' => ['data-test="some-value"', ['data' => ['test' => 'some-value']]],
            'aria-label' => ['aria-label="neato"', ['aria-label' => 'neato']],
            'random' => ['arbitrary="attribute"', ['arbitrary' => 'attribute']],
        ];
    }


    /**
     * @dataProvider appliesClassDataProvider
     * @param string $class
     * @param array $props
     * @return void
     */
    public function testAppliesClass(string $class, array $props = []): void
    {
        $output = Craft::$app->getUi()->createAndRender('button', $props);
        $this->assetHasClass($class, $output);
    }

    public function appliesClassDataProvider(): array
    {
        return [
            'empty' => ['btn-empty', []],
            'default' => ['btn', ['label' => "button"]],
            'disabled' => ['disabled', ['disabled' => true]],
            'submit prop' => ['submit', ['submit' => true]],
            'submit type' => ['submit', ['type' => "submit"]],
            'submit variant' => ['submit', ['variant' => "submit"]],
            'loading prop' => ['loading', ['loading' => true]],
            'loading state' => ['loading', ['state' => 'loading']],
            'dashed prop' => ['dashed', ['dashed' => true]],
            'dashed variant' => ['dashed', ['variant' => 'dashed']],
        ];
    }

    // tests
    public function testButtonRender()
    {
        $output = Craft::$app->getUi()->createAndRender('button');
        $parsed = Html::parseTag($output);

        self::assertSame('button', $parsed['type']);
        $this->assetHasClass('btn-empty', $output);
    }
}
