<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Responses\CpModalResponse;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Twig\Markup;

it('renders UI form state and submission props in pages and slideouts', function (bool $resolved, string $accept, string $mode) {
    $ui = Ui::make([
        Field::make('Title', Text::make('title')->value('Default title')),
    ]);
    $context = new UiContext(
        namespace: ['settings'],
        values: ['settings' => ['title' => 'Submitted title']],
        errors: ['title' => ['Title is invalid.']],
        globalErrors: ['Settings could not be saved.'],
        mode: ControlMode::ReadOnly,
        refreshable: true,
    );
    $props = ['submit' => ['method' => 'post', 'url' => '/settings']];
    $screen = new CpScreenResponse()->title('Settings');

    if ($resolved) {
        $screen->ui(app(UiResolver::class)->resolve($ui, $context), props: $props);
    } else {
        $screen->ui($ui, $context, $props);
    }

    $request = Request::create('/', server: [
        'HTTP_ACCEPT' => $accept,
        'HTTP_X_INERTIA' => 'true',
        'HTTP_X_CRAFT_CONTAINER_ID' => 'settings-slideout',
    ]);

    TestResponse::fromBaseResponse($screen->toResponse($request))
        ->assertOk()
        ->assertJsonPath('component', 'Ui')
        ->assertJsonPath('props.screen.mode', $mode)
        ->assertJsonPath('props.title', 'Settings')
        ->assertJsonPath('props.submit', ['method' => 'post', 'url' => '/settings'])
        ->assertJsonPath('props.ui.scope', ['settings'])
        ->assertJsonPath('props.ui.refreshable', true)
        ->assertJsonPath('props.ui.nodes.0.control.path', ['settings', 'title'])
        ->assertJsonPath('props.ui.nodes.0.control.mode', 'readOnly')
        ->assertJsonPath('props.ui.values.settings.title', 'Submitted title')
        ->assertJsonPath('props.ui.errors', [
            ['path' => ['settings', 'title'], 'messages' => ['Title is invalid.']],
        ])
        ->assertJsonPath('props.ui.globalErrors', ['Settings could not be saved.']);
})->with(['definition' => false, 'payload' => true])
    ->with(['page' => ['text/html', 'page'], 'slideout' => ['application/json', 'slideout']]);

it('accepts markup for html sections', function (string $method, string $property) {
    $markup = new Markup('<div>HTML</div>', 'UTF-8');
    $response = new CpScreenResponse;

    expect($response->$method($markup))->toBe($response)
        ->and($response->$property)->toBe($markup);
})->with([
    ['toolbarHtml', 'toolbarHtml'],
    ['additionalButtonsHtml', 'additionalButtonsHtml'],
    ['contentHtml', 'contentHtml'],
    ['metaSidebarHtml', 'metaSidebarHtml'],
    ['pageSidebarHtml', 'pageSidebarHtml'],
    ['noticeHtml', 'noticeHtml'],
    ['errorSummary', 'errorSummary'],
]);

it('restores the namespace after response preparation', function (string $class, string $prepare, ?string $outer, bool $throws) {
    InputNamespace::set($outer);
    $request = Request::create('/', server: ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_CRAFT_CONTAINER_ID' => 'container']);
    $response = new $class;
    $namespace = null;
    $response->$prepare(function ($prepared, $containerId) use ($response, $throws, &$namespace) {
        expect($prepared)->toBe($response)->and($containerId)->toBe('container');
        $namespace = InputNamespace::get();
        expect($namespace)->toBeString()->toHaveLength(10);
        if ($throws) {
            throw new RuntimeException('Preparation failed');
        }
        $prepared->contentHtml('<input name="title">');
    });

    if ($throws) {
        expect(fn () => $response->toResponse($request))->toThrow(RuntimeException::class, 'Preparation failed');
    } else {
        $data = $response->toResponse($request)->getData(true);
        expect($data['namespace'])->toBe($namespace)
            ->and($data['content'])->toBe('<input name="'.$namespace.'[title]">');
    }
    expect(InputNamespace::get())->toBe($outer);
})->with([[CpScreenResponse::class, 'prepareScreen'], [CpModalResponse::class, 'prepareModal']])
    ->with([null, 'outer'])->with([false, true]);
