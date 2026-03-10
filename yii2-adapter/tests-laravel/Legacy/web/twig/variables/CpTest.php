<?php

namespace CraftCms\Yii2Adapter\Tests\Legacy\web\twig\variables;

use craft\web\twig\variables\Cp;
use CraftCms\Yii2Adapter\Tests\TestCase;

final class CpTest extends TestCase
{
    public function test_get_template_suggestions(): void
    {
        $cp = new Cp();
        $suggestions = $cp->getTemplateSuggestions();

        self::assertIsArray($suggestions);
        self::assertCount(1, $suggestions);
        self::assertArrayHasKey('label', $suggestions[0]);
        self::assertArrayHasKey('data', $suggestions[0]);
        self::assertIsString($suggestions[0]['label']);
        self::assertIsArray($suggestions[0]['data']);

        // Legacy format should not include 'type' or 'options' keys
        self::assertArrayNotHasKey('type', $suggestions[0]);
        self::assertArrayNotHasKey('options', $suggestions[0]);
    }
}
