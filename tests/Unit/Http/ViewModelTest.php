<?php

declare(strict_types=1);

use CraftCms\Cms\Http\ViewModels\ViewModel;

it('returns public properties and public method values', function () {
    $viewModel = new class extends ViewModel
    {
        public string $name = 'Craft';

        public array $items;

        private string $secret = 'hidden';

        public function __construct() {}

        public function status(): string
        {
            return 'ready';
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
