<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\Entry;
use craft\services\Elements;
use craft\test\TestCase;
use craft\test\TestSetup;
use crafttests\fixtures\AssetFixture;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\settings\GeneralConfigSettingFixture;
use crafttests\fixtures\SitesFixture;
use crafttests\fixtures\UserFixture;

/**
 * Unit tests for the config service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Oliver Stark <os@fortrabbit.com>
 * @since 5.8
 */
class ElementsTest extends TestCase
{
    /**
     * @var Elements
     */
    public $elements;

    /**
     * @return void
     */
    public function testParseRefs(): void
    {
        $this->markTestSkipped('Port to Laravel');

        // Generate a random slug that is unlikely to exist:
        $randomSlug = Craft::$app->getSecurity()->generateRandomString(10);

        $entryWithUrl = Entry::find()
            ->slug('With--URL--1')
            ->one();

        $strings = [
            // Things that should stay the same:
            'no-tags' => ['No tags here!', 'No tags here!'],
            'incomplete-closing' => ['Incomplete {tag.', 'Incomplete {tag.'],
            'incomplete-opening' => ['Incomplete tag}.', 'Incomplete tag}.'],
            'invalid-type-ref' => ['Invalid {beeble:1234:property}', 'Invalid {beeble:1234:property}'],
            'invalid-type-class' => ['Invalid {craft\elements\Beeble:1234:property}', 'Invalid {craft\elements\Beeble:1234:property}'],

            // Entries + behaviors
            'entry-default-property-id' => [TestSetup::SITE_URL . 'some-uri/With--URL--1', "{entry:$entryWithUrl->id}"],
            'entry-url' => [TestSetup::SITE_URL . 'some-uri/With--URL--1', "{entry:$entryWithUrl->id:url}"],
            'entry-title' => ['With URL 1', "{entry:$entryWithUrl->id:title}"],
            'entry-custom-identifer-slug' => ['With URL 1', '{entry:With--URL--1:title}'],
            'entry-custom-identifer-section-and-slug' => ['With URL 1', '{entry:withUri1/With--URL--1:title}'],
            'entry-custom-field' => ['foo', '{entry:test1/Theories--of--life:plainTextField}'],
            'entry-other-site-id' => ['Theories of life', '{entry:test1/Theories--of--life@1001:title}'],
            'entry-other-site-handle' => ['Theories of life', '{entry:test1/Theories--of--life@testSite2:title}'],
            'entry-other-site-uuid' => ['Theories of life', '{entry:test1/Theories--of--life@e9c6ae73-c175-4a3c-afa4-1ee095aa4b55:title}'],

            // Things that should use fallback text:
            'fallback-invalid-type' => ['Fallback text', '{beeble:bobbing:bubbles||Fallback text}'],
            'fallback-nonexistent-element-id' => ['Fallback text', '{entry:999999999||Fallback text}'],
            'fallback-nonexistent-element-custom' => ['Fallback text', "{entry:test1/$randomSlug||Fallback text}"],
            'fallback-nonexistent-property-id' => ['Fallback text', "{entry:999999999:propertyThatIsNotDefined||Fallback text}"],
            'fallback-nonexistent-property-custom' => ['Fallback text', "{entry:test1/$randomSlug:propertyThatIsNotDefined||Fallback text}"],

            // Recursive evaluation:
            'recursive-eval' => ['Substitution in A: [Substitution in B: [Value from C]]', '{entry:test1/recursive-reference-a:plainTextField}'],
        ];

        foreach ($strings as $label => [$expected, $text]) {
            self::assertEquals($expected, $this->elements->parseRefs($text), $label);
        }
    }

    /**
     * @inheritdoc
     */
    public function _fixtures(): array
    {
        return [
            'generalConfig:allowUppercaseInSlug' => [
                'class' => GeneralConfigSettingFixture::class,
                'setting' => 'allowUppercaseInSlug',
                'value' => true,
            ],
            // Address?
            'assets' => [
                'class' => AssetFixture::class,
            ],
            // Category?
            // ContentBlock?
            'entries' => [
                'class' => EntryFixture::class,
            ],
            'globalSet' => [
                'class' => GlobalSetFixture::class,
            ],
            // Tag?
            'users' => [
                'class' => UserFixture::class,
            ],
            'sites' => [
                'class' => SitesFixture::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->elements = Craft::$app->getElements();
    }
}
