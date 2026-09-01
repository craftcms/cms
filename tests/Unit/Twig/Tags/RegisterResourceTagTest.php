<?php

declare(strict_types=1);

use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateManager;

beforeEach(function () {
    app()->forgetScopedInstances();
    $this->manager = app(TemplateManager::class);
    $this->assets = app(HtmlStack::class);
});

describe('css tag', function () {
    it('registers inline CSS', function () {
        $this->assets->startCssBuffer();

        $this->manager->renderString('{% css %}body { color: red; }{% endcss %}');

        $registered = $this->assets->clearCssBuffer();
        $html = implode("\n", array_map(strval(...), $registered));

        expect($html)->toContain('body { color: red; }');
    });

    it('registers CSS from a string expression', function () {
        $this->assets->startCssBuffer();

        $this->manager->renderString('{% css "body { margin: 0; }" %}');

        $registered = $this->assets->clearCssBuffer();
        $html = implode("\n", array_map(strval(...), $registered));

        expect($html)->toContain('body { margin: 0; }');
    });
});

describe('js tag', function () {
    it('registers inline JS', function () {
        $this->assets->startJsBuffer();

        $this->manager->renderString('{% js %}console.log("hello");{% endjs %}');

        $registered = $this->assets->clearJsBuffer(scriptTag: false, combine: false);

        expect($registered)->toHaveKey(Position::BodyEnd->value)
            ->and($registered[Position::BodyEnd->value])->toContain('console.log("hello");');
    });

    it('registers JS from a string expression', function () {
        $this->assets->startJsBuffer();

        $this->manager->renderString('{% js "alert(1);" %}');

        $registered = $this->assets->clearJsBuffer(scriptTag: false, combine: true);

        expect($registered)->toContain('alert(1);');
    });

    it('registers ready JS separately from body end JS', function () {
        $this->assets->startJsBuffer();

        $this->manager->renderString('{% js on ready %}console.log("ready");{% endjs %}');

        $registered = $this->assets->clearJsBuffer(scriptTag: false, combine: false);

        expect($registered)->toHaveKey(Position::Ready->value)
            ->and($registered[Position::Ready->value])->toContain('console.log("ready");');
    });
});
