<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\HtmlPurifier;

use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use CraftCms\Cms\Support\Json;
use CraftCms\Yii2Adapter\HtmlPurifier\HtmlPurifierSanitizer;
use CraftCms\Yii2Adapter\HtmlPurifier\LegacyHtmlPurifierConfigRegistrar;
use CraftCms\Yii2Adapter\Tests\TestCase;

class LegacyHtmlPurifierConfigRegistrarTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(config_path('craft/htmlpurifier/test.json'));
        File::deleteDirectory(config_path('craft/htmlpurifier'));

        parent::tearDown();
    }

    public function test_register_imports_legacy_json_config(): void
    {
        File::ensureDirectoryExists(config_path('craft/htmlpurifier'));
        File::put(config_path('craft/htmlpurifier/test.json'), Json::encode([
            'Attr.EnableID' => true,
            'Attr.AllowedFrameTargets' => ['_blank'],
        ], JSON_THROW_ON_ERROR));

        app(LegacyHtmlPurifierConfigRegistrar::class)->boot();

        self::assertTrue(app(HtmlSanitizers::class)->has('test'));

        $sanitized = app(HtmlSanitizers::class)->sanitize('<a id="a" target="_blank" onclick="bad()">Hello</a>', 'test');

        self::assertSame('<a id="a" target="_blank" rel="noreferrer noopener">Hello</a>', $sanitized);
    }

    public function test_html_purifier_sanitizer_can_be_used_directly(): void
    {
        $sanitized = app(HtmlSanitizers::class)->sanitize('<p id="test">Hello</p>', new HtmlPurifierSanitizer([
            'Attr.EnableID' => true,
        ]));

        self::assertSame('<p id="test">Hello</p>', $sanitized);
    }
}
