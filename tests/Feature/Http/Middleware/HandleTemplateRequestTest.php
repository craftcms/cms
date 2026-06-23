<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    Cms::config()->isSystemLive = true;

    $this->tempDir = sys_get_temp_dir().'/craft-template-request-test-'.uniqid();

    File::ensureDirectoryExists($this->tempDir);
    File::put($this->tempDir.'/index.twig', 'homepage-template');

    Aliases::set('@templates', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);
});

afterEach(function () {
    Cms::config()->isSystemLive = null;

    File::deleteDirectory($this->tempDir);
});

it('renders the homepage template through the frontend fallback route', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('homepage-template');
});

it('does not render CP views through the frontend fallback route', function () {
    $this->get('/'.Cms::config()->cpTrigger.'/mail/system-message-text?textBody=<script>alert(1)</script>')
        ->assertNotFound()
        ->assertDontSee('<script>alert(1)</script>', false);
});
