<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('renders the matching case body', function () {
    $result = $this->renderer->renderString(
        '{% switch var %}{% case "foo" %}matched{% endswitch %}',
        ['var' => 'foo'],
    );

    expect(trim((string) $result))->toBe('matched');
});

it('renders nothing when no case matches and no default exists', function () {
    $result = $this->renderer->renderString(
        '{% switch var %}{% case "foo" %}Foo{% case "bar" %}Bar{% endswitch %}',
        ['var' => 'baz'],
    );

    expect(trim((string) $result))->toBe('');
});

it('renders the default case when no case matches', function () {
    $result = $this->renderer->renderString(
        '{% switch var %}{% case "foo" %}Foo{% default %}Default{% endswitch %}',
        ['var' => 'baz'],
    );

    expect(trim((string) $result))->toBe('Default');
});

it('renders the first matching case only', function () {
    $result = $this->renderer->renderString(
        '{% switch var %}{% case "foo" %}First{% case "foo" %}Second{% endswitch %}',
        ['var' => 'foo'],
    );

    expect(trim((string) $result))->toBe('First');
});

it('supports or values in a case', function () {
    $result = $this->renderer->renderString(
        '{% switch var %}{% case "foo" or "bar" %}Matched{% default %}Default{% endswitch %}',
        ['var' => 'bar'],
    );

    expect(trim((string) $result))->toBe('Matched');
});

it('works with integer values', function () {
    $result = $this->renderer->renderString(
        '{% switch var %}{% case 1 %}One{% case 2 %}Two{% default %}Other{% endswitch %}',
        ['var' => 2],
    );

    expect(trim((string) $result))->toBe('Two');
});

it('renders case body with template expressions', function () {
    $result = $this->renderer->renderString(
        '{% switch type %}{% case "greeting" %}Hello, {{ name }}!{% endswitch %}',
        ['type' => 'greeting', 'name' => 'World'],
    );

    expect(trim((string) $result))->toBe('Hello, World!');
});
