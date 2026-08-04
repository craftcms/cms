<?php

declare(strict_types=1);

use CraftCms\Cms\Http\ViewModels\ViewModel;

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
