<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\View\AssetRegistry;

beforeEach(function () {
    app()->forgetScopedInstances();
    $this->renderer = app(TemplateRenderer::class);
    $this->assets = app(AssetRegistry::class);
});

describe('css tag', function () {
    it('registers inline CSS', function () {
        $this->assets->startCssBuffer();

        $this->renderer->renderString('{% css %}body { color: red; }{% endcss %}');

        $registered = $this->assets->clearCssBuffer();
        $html = implode("\n", array_map(strval(...), $registered));

        expect($html)->toContain('body { color: red; }');
    });

    it('registers CSS from a string expression', function () {
        $this->assets->startCssBuffer();

        $this->renderer->renderString('{% css "body { margin: 0; }" %}');

        $registered = $this->assets->clearCssBuffer();
        $html = implode("\n", array_map(strval(...), $registered));

        expect($html)->toContain('body { margin: 0; }');
    });
});

describe('js tag', function () {
    it('registers inline JS', function () {
        $this->assets->startJsBuffer();

        $this->renderer->renderString('{% js %}console.log("hello");{% endjs %}');

        $registered = $this->assets->clearJsBuffer(scriptTag: false, combine: true);

        expect($registered)->toContain('console.log("hello");');
    });

    it('registers JS from a string expression', function () {
        $this->assets->startJsBuffer();

        $this->renderer->renderString('{% js "alert(1);" %}');

        $registered = $this->assets->clearJsBuffer(scriptTag: false, combine: true);

        expect($registered)->toContain('alert(1);');
    });
});
