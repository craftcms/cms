<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateManager;

beforeEach(function () {
    $this->manager = app(TemplateManager::class);
});

it('namespaces input name attributes', function () {
    $result = $this->manager->renderString(
        '{% namespace "myNamespace" %}<input name="title">{% endnamespace %}',
    );

    expect($result)->toContain('name="myNamespace[title]"');
});

it('namespaces input id attributes', function () {
    $result = $this->manager->renderString(
        '{% namespace "myNamespace" %}<input id="title">{% endnamespace %}',
    );

    expect($result)->toContain('id="myNamespace-title"');
});

it('namespaces for attributes when matching id exists', function () {
    $result = $this->manager->renderString(
        '{% namespace "ns" %}<label for="title">Title</label><input id="title">{% endnamespace %}',
    );

    expect($result)->toContain('for="ns-title"');
});

it('leaves for attributes unchanged when no matching id exists', function () {
    $result = $this->manager->renderString(
        '{% namespace "ns" %}<label for="title">Title</label>{% endnamespace %}',
    );

    expect($result)->toContain('for="title"');
});

it('renders body directly when namespace is empty', function () {
    $result = $this->manager->renderString(
        '{% namespace "" %}<input name="title">{% endnamespace %}',
    );

    expect($result)->toContain('name="title"');
});

it('supports dynamic namespace values', function () {
    $result = $this->manager->renderString(
        '{% namespace ns %}<input name="title">{% endnamespace %}',
        ['ns' => 'dynamicNs'],
    );

    expect($result)->toContain('name="dynamicNs[title]"');
});

it('supports withClasses to namespace CSS classes', function () {
    $result = $this->manager->renderString(
        '{% namespace "ns" withClasses %}<div class="container"></div>{% endnamespace %}',
    );

    expect($result)->toContain('class="ns-container"');
});
