<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Filesystems\Filesystem;

it('does not resolve the root URL while validating attributes', function () {
    $filesystem = new class extends Filesystem
    {
        public ?string $url = '@missingFilesystemAlias';

        public function getRootUrl(): ?string
        {
            throw new RuntimeException('The root URL was resolved during validation.');
        }

        public function getDiskConfig(): array
        {
            return [];
        }

        public function getRules(): array
        {
            return ['url' => ['required', 'string']];
        }
    };

    expect($filesystem->validate(['url']))->toBeTrue();
});
