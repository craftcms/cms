<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\Address;
use craft\fieldlayoutelements\addresses\AddressField;
use craft\fieldlayoutelements\addresses\CountryCodeField;
use craft\fieldlayoutelements\addresses\LabelField;
use craft\mcp\schema\FieldLayoutConfig;
use craft\test\TestCase;

class FieldLayoutConfigTest extends TestCase
{
    public function testMissingMandatoryFieldsAreAddedToTheConfig(): void
    {
        $layout = Craft::$app->get('mcp')->getCapabilityContainer()->get(FieldLayoutConfig::class)->make([
            'tabs' => [
                ['name' => 'Content', 'elements' => [['type' => CountryCodeField::class]]],
            ],
        ], Address::class);

        $elements = $layout->getConfig()['tabs'][0]['elements'];

        self::assertSame(
            [LabelField::class, AddressField::class, CountryCodeField::class],
            array_column($elements, 'type'),
        );
    }
}
