<?php

declare(strict_types=1);

use CraftCms\Cms\Http\ViewModels\ViewModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

it('returns public properties and public method values', function () {
    $viewModel = new class extends ViewModel
    {
        public string $name = 'Craft';

        public array $items;

        public function __construct(private readonly string $secret = 'hidden') {}

        public function status(): string
        {
            if ($this->secret === 'hidden') {
                return 'ready';
            }

            return 'not-ready';
        }

        public static function ignored(): string
        {
            return 'ignored';
        }
    };

    expect($viewModel->toArray())->toBe([
        'name' => 'Craft',
        'items' => [],
        'status' => 'ready',
    ]);
});

it('evaluates only the view model methods requested by Inertia', function (array $headers, array $props, int $rowCalls, int $paginationCalls) {
    $calls = (object) ['rows' => 0, 'pagination' => 0];
    $viewModel = new class($calls) extends ViewModel
    {
        public string $name = 'Craft';

        public function __construct(private readonly object $calls) {}

        public function rows(): array
        {
            $this->calls->rows++;

            return [['id' => 1]];
        }

        public function pagination(): array
        {
            $this->calls->pagination++;

            return ['total' => 1];
        }
    };
    $request = Request::create('/view-model');
    $request->headers->add(['X-Inertia' => 'true', ...$headers]);
    config(['inertia.pages.ensure_pages_exist' => false]);

    $response = Inertia::render('performance/Test', [$viewModel])->toResponse($request);

    expect($response->getData(true)['props'])->toBe($props)
        ->and($calls->rows)->toBe($rowCalls)
        ->and($calls->pagination)->toBe($paginationCalls);
})->with([
    'full response' => [[], ['name' => 'Craft', 'rows' => [['id' => 1]], 'pagination' => ['total' => 1]], 1, 1],
    'pagination only' => [
        ['X-Inertia-Partial-Component' => 'performance/Test', 'X-Inertia-Partial-Data' => 'pagination'],
        ['pagination' => ['total' => 1]], 0, 1,
    ],
    'public property only' => [
        ['X-Inertia-Partial-Component' => 'performance/Test', 'X-Inertia-Partial-Data' => 'name'],
        ['name' => 'Craft'], 0, 0,
    ],
    'exclude rows' => [
        ['X-Inertia-Partial-Component' => 'performance/Test', 'X-Inertia-Partial-Except' => 'rows'],
        ['name' => 'Craft', 'pagination' => ['total' => 1]], 0, 1,
    ],
    'component changed' => [
        ['X-Inertia-Partial-Component' => 'performance/Other', 'X-Inertia-Partial-Data' => 'pagination'],
        ['name' => 'Craft', 'rows' => [['id' => 1]], 'pagination' => ['total' => 1]], 1, 1,
    ],
]);
