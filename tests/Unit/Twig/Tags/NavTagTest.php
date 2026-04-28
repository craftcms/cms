<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\TemplateRenderer;

beforeEach(function () {
    $this->renderer = app(TemplateRenderer::class);
});

it('renders a flat list of items at the same level', function () {
    $result = $this->renderer->renderString(
        '{% nav item in items %}{{ item.title }},{% endnav %}',
        [
            'items' => [
                (object) ['title' => 'A', 'level' => 1],
                (object) ['title' => 'B', 'level' => 1],
                (object) ['title' => 'C', 'level' => 1],
            ],
        ],
    );

    expect(trim((string) $result))->toBe('A,B,C,');
});

it('renders nested list with ifchildren and children sub-tags', function () {
    $template = '{% nav item in items %}<li>{{ item.title }}{% ifchildren %}<ul>{% children %}</ul>{% endifchildren %}</li>{% endnav %}';

    $result = $this->renderer->renderString($template, [
        'items' => [
            (object) ['title' => 'About', 'level' => 1],
            (object) ['title' => 'Team', 'level' => 2],
            (object) ['title' => 'History', 'level' => 2],
            (object) ['title' => 'Contact', 'level' => 1],
        ],
    ]);

    $normalized = preg_replace('/\s+/', '', (string) $result);

    expect($normalized)->toBe('<li>About<ul><li>Team</li><li>History</li></ul></li><li>Contact</li>');
});

it('renders three levels deep with proper outdenting', function () {
    $template = '{% nav item in items %}<li>{{ item.title }}{% ifchildren %}<ul>{% children %}</ul>{% endifchildren %}</li>{% endnav %}';

    $result = $this->renderer->renderString($template, [
        'items' => [
            (object) ['title' => 'A', 'level' => 1],
            (object) ['title' => 'B', 'level' => 2],
            (object) ['title' => 'C', 'level' => 3],
            (object) ['title' => 'D', 'level' => 1],
        ],
    ]);

    $normalized = preg_replace('/\s+/', '', (string) $result);

    expect($normalized)->toBe('<li>A<ul><li>B<ul><li>C</li></ul></li></ul></li><li>D</li>');
});

it('exposes nav.level context variable', function () {
    $result = $this->renderer->renderString(
        '{% nav item in items %}[{{ nav.level }}:{{ item.title }}]{% endnav %}',
        [
            'items' => [
                (object) ['title' => 'Root', 'level' => 1],
                (object) ['title' => 'Child', 'level' => 2],
            ],
        ],
    );

    expect(trim((string) $result))->toBe('[1:Root][2:Child]');
});

it('skips items that jump more than one level deeper', function () {
    $result = $this->renderer->renderString(
        '{% nav item in items %}{{ item.title }},{% endnav %}',
        [
            'items' => [
                (object) ['title' => 'Root', 'level' => 1],
                (object) ['title' => 'Skipped', 'level' => 3],
                (object) ['title' => 'Valid', 'level' => 1],
            ],
        ],
    );

    expect(trim((string) $result))->toBe('Root,Valid,');
});

it('renders nothing for an empty collection', function () {
    $result = $this->renderer->renderString(
        '{% nav item in items %}{{ item.title }}{% endnav %}',
        ['items' => []],
    );

    expect(trim((string) $result))->toBe('');
});

it('skips orphan items outside parent nested-set range using lft/rgt', function () {
    $result = $this->renderer->renderString(
        '{% nav item in items %}{{ item.title }},{% endnav %}',
        [
            'items' => [
                (object) ['title' => 'Parent', 'level' => 1, 'lft' => 1, 'rgt' => 4],
                (object) ['title' => 'Child', 'level' => 2, 'lft' => 2, 'rgt' => 3],
                (object) ['title' => 'Orphan', 'level' => 2, 'lft' => 10, 'rgt' => 11],
                (object) ['title' => 'Other', 'level' => 1, 'lft' => 5, 'rgt' => 6],
            ],
        ],
    );

    expect(trim((string) $result))->toBe('Parent,Child,Other,');
});
