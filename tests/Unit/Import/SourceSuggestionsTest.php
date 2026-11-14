<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Import\SourceSuggestions;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->originalRoot = Aliases::get('@root');
    $this->root = sys_get_temp_dir().'/source-suggestions-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists("$this->root/data");
    touch("$this->root/data/people.csv");
    Aliases::set('@root', $this->root);

    $this->labels = fn (string $query): array => array_column(app(SourceSuggestions::class)->suggest($query), 'label');
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
    File::deleteDirectory($this->root);
});

it('offers the folders and importable files under a path', function () {
    expect(($this->labels)('./'))->toBe(['data/'])
        ->and(($this->labels)('./data/'))->toBe(['people.csv']);
});

it('doesn’t browse a folder that links to a restricted one', function () {
    symlink('/etc', "$this->root/data/linked");

    expect(($this->labels)('./data/'))->toBe(['people.csv'])
        ->and(($this->labels)('./data/linked/'))->toBe([]);
})->skipOnWindows();
