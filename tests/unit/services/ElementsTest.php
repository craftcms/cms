<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\services\Elements;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\GlobalSetFixture;
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
     * @dataProvider parseRefsDataProvider
     * @param string $expected
     * @param string $text
     * @return void
     */
    public function testParseRefs(string $expected, string $text): void
    {
        self::assertEquals($expected, $this->elements->parseRefs($text));
    }

    public function parseRefsDataProvider(): array
    {
        $randomSlug = Craft::$app->getSecurity()->generateRandomString(10);

        return [
            // Things that should stay the same:
            'no-tags' => ['No tags here!', 'No tags here!'],
            'incomplete-closing' => ['Incomplete {tag.', 'Incomplete {tag.'],
            'incomplete-opening' => ['Incomplete tag}.', 'Incomplete tag}.'],
            'invalid-type-ref' => ['Invalid {beeble:1234:property}', 'Invalid {beeble:1234:property}'],
            'invalid-type-class' => ['Invalid {craft\elements\Beeble:1234:property}', 'Invalid {craft\elements\Beeble:1234:property}'],

            // Entries + behaviors
            'entry-default-property' => ['https://craftcms.com', '{entry:With--URL--1}'],
            'entry-url' => ['https://craftcms.com', '{entry:With--URL--1:url}'],
            'entry-title' => ['With URL 1', '{entry:With--URL--1:title}'],
            'entry-section-scope' => ['With URL 1', '{entry:withUri1/With--URL--1:title}'],
            'entry-custom-field' => ['foo', '{entry:Theories--of--life:plainTextField}'],
            'entry-other-site-id' => ['Theories of life', '{entry:Theories-of-life@1001:title}'],
            'entry-other-site-handle' => ['Theories of life', '{entry:Theories-of-life@testSite2:title}'],
            'entry-other-site-uuid' => ['Theories of life', '{entry:Theories-of-life@09a48e85-2f12-2124-b82c-45b14b13d8ce:title}'],

            // Using fallbacks:
            'fallback-invalid-type' => ['Fallback text', "{beeble:bobbing:bubbles||Fallback text}"],
            'fallback-nonexistent-element' => ['Fallback text', "{entry:$randomSlug||Fallback text}"],
            'fallback-nonexistent-property' => ['Fallback text', "{entry:$randomSlug:propertyThatIsNotDefined||Fallback text}"],

            // Recursive evaluation:
            'recursive-eval' => ['Substitution in A: [Substitution in B: [Value from C]]', '{entry:recursive-reference-a:plainTextField}'],
        ];
    }

    public function _fixtures(): array
    {
        return [
            // Address?
            'assets' => AssetFixture::class,
            // Category?
            // ContentBlock?
            'entries' => EntryFixture::class,
            'globalSet' => GlobalSetFixture::class,
            // Tag?
            'users' => UserFixture::class,
            'sites' => SitesFixture::class,
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
