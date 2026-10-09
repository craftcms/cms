<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Twig\Exceptions\TemplateExitException;
use CraftCms\Cms\Twig\TokenParsers\ExitTokenParser;
use CraftCms\Cms\Twig\TokenParsers\NamespaceTokenParser;
use CraftCms\Cms\View\TemplateManager;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

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

it('preserves nested scopes and template context', function (bool $useYield) {
    $twig = new Environment(new ArrayLoader, ['use_yield' => $useYield]);
    $twig->addTokenParser(new NamespaceTokenParser);
    InputNamespace::set('original');

    $result = $twig->createTemplate('{% macro input() %}<input name="title">{% endmacro %}{% namespace "outer" %}{% namespace "inner" %}{{ _self.input() }}{% endnamespace %}{% namespace null %}{% namespace "" %}{% set value = "saved" %}{% block input %}<input name="after">{% endblock %}{% endnamespace %}{% endnamespace %}{% endnamespace %}{{ value }}')->render([]);

    expect($result)->toBe('<input name="outer[inner][title]"><input name="outer[after]">saved');
    expect(InputNamespace::get())->toBe('original');
})->with([false, true]);

it('restores namespace and output buffers when the body throws', function (bool $useYield, string $exception) {
    $twig = new Environment(new ArrayLoader, ['use_yield' => $useYield]);
    $twig->addTokenParser(new NamespaceTokenParser);
    $twig->addTokenParser(new ExitTokenParser);
    $twig->addFunction(new TwigFunction('fail', fn () => throw new $exception));
    InputNamespace::set('original');
    $level = ob_get_level();

    expect(fn () => $twig->createTemplate('{% namespace "outer" %}{% namespace "inner" %}output'.($exception === TemplateExitException::class ? '{% exit %}' : '{{ fail() }}').'{% endnamespace %}{% endnamespace %}')->render([]))->toThrow(fn (RuntimeError $error) => expect($error->getPrevious())->toBeInstanceOf($exception));

    expect(InputNamespace::get())->toBe('original');
    expect(ob_get_level())->toBe($level);
})->with([false, true])->with([RuntimeException::class, Error::class, TemplateExitException::class]);
