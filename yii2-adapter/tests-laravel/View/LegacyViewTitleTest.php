<?php

declare(strict_types=1);

use craft\web\View;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;

beforeEach(function() {
    $this->tempDir = sys_get_temp_dir() . '/craft-legacy-view-title-test-' . uniqid();
    File::ensureDirectoryExists($this->tempDir);

    Aliases::set('@templates', $this->tempDir);
    TemplateMode::set(TemplateMode::Site);
});

afterEach(function() {
    Craft::$app->getView()->off(View::EVENT_END_PAGE);
    Craft::$app->getView()->title = null;
    File::deleteDirectory($this->tempDir);
});

it('renders the legacy view title in the page head', function() {
    file_put_contents($this->tempDir . '/page.twig', '<html><head><meta charset="utf-8"></head><body></body></html>');

    $view = Craft::$app->getView();
    $view->on(View::EVENT_END_PAGE, function() use ($view) {
        $view->title = 'Tom & Jerry';
    });

    $html = app(TemplateManager::class)->renderPageTemplate('page');

    expect($html)->toContain('<head><meta charset="utf-8"><title>Tom &amp; Jerry</title></head>');
});
