<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Validator;

function authorsSection(int $min, ?int $max): Section
{
    $section = Section::factory()
        ->withEntryTypes(ImportFixtures::entryTypeWithTitle([]))
        ->create(['minAuthors' => $min, 'maxAuthors' => $max]);

    EntryTypes::refreshEntryTypes();
    Sections::refreshSections();

    return $section;
}

it('marks Author IDs as required when the section needs authors', function (int $min, bool $required) {
    $section = authorsSection($min, null);

    $col = collect(new EntryImporter()->section($section->uid)->getDestinationCols())->firstWhere('handle', 'authorIds');

    expect((bool) $col->required)->toBe($required);
})->with([
    'needs one' => [1, true],
    'needs none' => [0, false],
]);

it('requires authors to be mapped or defaulted when a step can create entries in a section that needs them', function (array $settings, int $min, bool $fails) {
    $section = authorsSection($min, null);

    $errors = Validator::make(
        ['settings' => ['section' => $section->uid, 'map' => [], ...$settings]],
        ['settings.map' => EntryImporter::getSettingsRules()['settings.map']],
    )->errors();

    expect($errors->has('settings.map.authorIds'))->toBe($fails);
})->with([
    'neither mapped nor defaulted' => [[], 1, true],
    'mapped' => [['map' => ['authorIds' => 'author']], 1, false],
    'defaulted' => [['defaultValues' => ['authorIds' => [1]]], 1, false],
    'only updating' => [['matchMode' => 'updateOnly'], 1, false],
    'a section that needs none' => [[], 0, false],
]);

it('matches authors by ID, username or email, and offers the users who can author the section as a default', function () {
    $section = authorsSection(1, 2);

    $col = collect(new EntryImporter()->section($section->uid)->getDestinationCols())->firstWhere('handle', 'authorIds');
    $control = $col->defaultValueInput['control'];

    expect(array_column($col->importSettings[0]->options, 'value'))->toBe(['id', 'username', 'email'])
        ->and($col->defaultValueInput['type'])->toBe('elements')
        ->and($control['props']['elementType'])->toBe(UserElement::class)
        ->and($control['props']['limit'])->toBe(2)
        ->and($control['props']['criteria']['can'])->toBe("viewEntries:$section->uid");
});

describe('importing authors', function () {
    beforeEach(function () {
        $seed = ImportFixtures::seedEntry([], sectionAttrs: ['minAuthors' => 0, 'maxAuthors' => null]);

        $this->ada = User::factory()->createElement(['username' => 'ada', 'email' => 'ada@example.com']);
        $this->grace = User::factory()->createElement(['username' => 'grace', 'email' => 'grace@example.com']);

        $this->importAuthors = function (mixed $authors, string $matchBy) use ($seed): array {
            $importer = new EntryImporter()
                ->site(Sites::getPrimarySite()->handle)
                ->transformer(null)
                ->fieldSettings(['authorIds' => ['matchBy' => $matchBy]]);

            app(Import::class)->importItem($importer, [
                'title' => 'imported entry',
                'sectionId' => $seed->section->handle,
                'typeId' => $seed->entryType->handle,
                'matchCriteria' => ['title' => 'title'],
                'authorIds' => $authors,
            ]);

            return EntryElement::find()->title('imported entry')->status(null)->one()->getAuthorIds();
        };
    });

    it('looks authors up by username or email, keeping their order', function () {
        expect(($this->importAuthors)(['GRACE', 'ada'], 'username'))->toBe([$this->grace->id, $this->ada->id])
            ->and(($this->importAuthors)(['ada@example.com'], 'email'))->toBe([$this->ada->id]);
    });

    it('skips and logs an author no user matches', function () {
        ImportLog::spy();

        expect(($this->importAuthors)(['nobody', 'ada'], 'username'))->toBe([$this->ada->id]);

        ImportLog::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, '“nobody”'))->once();
    });

    it('keeps an integer as an ID, as a default’s are', function () {
        expect(($this->importAuthors)([$this->grace->id, 'ada'], 'username'))->toBe([$this->grace->id, $this->ada->id]);
    });
});
