<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('renders a simple HTML tag', function () {
    $result = $this->renderer->renderString(
        '{% tag "div" %}Hello{% endtag %}',
    );

    expect(trim((string) $result))->toBe('<div>Hello</div>');
});

it('renders a tag with attributes', function () {
    $result = $this->renderer->renderString(
        '{% tag "div" with { class: "wrapper", id: "main" } %}Content{% endtag %}',
    );

    expect(trim((string) $result))->toContain('<div')
        ->toContain('class="wrapper"')
        ->toContain('id="main"')
        ->toContain('>Content</div>');
});

it('renders a self-closing void tag', function () {
    $result = $this->renderer->renderString(
        '{% tag "br" %}{% endtag %}',
    );

    expect(trim((string) $result))->toBe('<br>');
});

it('renders a tag with dynamic tag name', function () {
    $result = $this->renderer->renderString(
        '{% tag tagName %}Inner{% endtag %}',
        ['tagName' => 'span'],
    );

    expect(trim((string) $result))->toBe('<span>Inner</span>');
});

it('renders a tag with dynamic attributes', function () {
    $result = $this->renderer->renderString(
        '{% tag "a" with attrs %}Click{% endtag %}',
        ['attrs' => ['href' => '/home', 'class' => 'link']],
    );

    expect(trim((string) $result))->toContain('href="/home"')
        ->toContain('class="link"')
        ->toContain('>Click</a>');
});

it('renders nested template content inside a tag', function () {
    $result = $this->renderer->renderString(
        '{% tag "ul" %}{% for item in items %}<li>{{ item }}</li>{% endfor %}{% endtag %}',
        ['items' => ['A', 'B', 'C']],
    );

    expect(trim((string) $result))->toBe('<ul><li>A</li><li>B</li><li>C</li></ul>');
});
