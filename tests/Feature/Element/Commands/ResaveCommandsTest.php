<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Models\Address;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Asset\Volumes as VolumesService;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Jobs\ResaveElements;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('runs the built-in resave commands from resave all', function () {
    $entry = Entry::factory()->title('Article')->createElement();
    $asset = Asset::factory()->createElement();
    User::factory()->createElement(['fullName' => 'CLI User']);
    Address::factory()->createElement(['countryCode' => 'CA']);

    DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->update(['title' => null]);
    DB::table(Table::ASSETS)->where('id', $asset->id)->update(['filename' => '']);
    $this->artisan('craft:resave:all --set=title --to="=Retitled" --if-empty')
        ->expectsOutputToContain('Running craft:resave:entries')
        ->expectsOutputToContain('Running craft:resave:assets')
        ->expectsOutputToContain('Running craft:resave:addresses')
        ->expectsOutputToContain('Running craft:resave:users')
        ->assertExitCode(1);

    expect(EntryElement::find()->id($entry->id)->one()?->title)->toBe('Retitled');
});

it('forwards options to all resave commands even when some do not declare them', function () {
    Asset::factory()->createElement();

    $this->artisan('craft:resave:all --offset=1 --no-interaction')
        ->expectsOutputToContain('Running craft:resave:entries')
        ->expectsOutputToContain('Running craft:resave:assets')
        ->expectsOutputToContain('Running craft:resave:addresses')
        ->expectsOutputToContain('Running craft:resave:users')
        ->assertSuccessful();
});

it('passes comma-separated with-fields through resave all', function () {
    $first = Entry::factory()
        ->withField('alphaField', PlainText::class, value: 'alpha')
        ->createElementWithFields();
    $second = Entry::factory()
        ->withField('betaField', PlainText::class, value: 'beta')
        ->createElementWithFields();

    DB::table(Table::ELEMENTS_SITES)->where('elementId', $first->element->id)->update(['title' => null]);
    DB::table(Table::ELEMENTS_SITES)->where('elementId', $second->element->id)->update(['title' => null]);

    $this->artisan('craft:resave:all --with-fields=alphaField,betaField --set=title --to="=Field Matched" --if-empty')
        ->assertExitCode(1);

    expect(EntryElement::find()->id($first->element->id)->one()?->title)->toBe('Field Matched')
        ->and(EntryElement::find()->id($second->element->id)->one()?->title)->toBe('Field Matched');
});

it('accepts comma-separated with-fields on entries command directly', function () {
    $first = Entry::factory()
        ->withField('fieldOne', PlainText::class, value: 'one')
        ->createElementWithFields();
    $second = Entry::factory()
        ->withField('fieldTwo', PlainText::class, value: 'two')
        ->createElementWithFields();

    DB::table(Table::ELEMENTS_SITES)->where('elementId', $first->element->id)->update(['title' => null]);
    DB::table(Table::ELEMENTS_SITES)->where('elementId', $second->element->id)->update(['title' => null]);

    $this->artisan('craft:resave:entries --with-fields=fieldOne,fieldTwo --set=title --to="=Updated by Fields" --if-empty')
        ->assertSuccessful();

    expect(EntryElement::find()->id($first->element->id)->one()?->title)->toBe('Updated by Fields')
        ->and(EntryElement::find()->id($second->element->id)->one()?->title)->toBe('Updated by Fields');
});

it('sets matching fields to their default values', function () {
    $result = Entry::factory()
        ->withField('featured', Lightswitch::class, ['default' => true], value: false)
        ->withField('promoted', Lightswitch::class, ['default' => true], value: false)
        ->createElementWithFields();

    $this->artisan('craft:resave:entries --with-fields=featured,promoted --to-default')
        ->assertSuccessful();

    $entry = EntryElement::find()->id($result->element->id)->one();

    expect($entry->getFieldValue('featured'))->toBeTrue()
        ->and($entry->getFieldValue('promoted'))->toBeTrue();
});

it('sets a single field to its default value when passed as set', function () {
    $result = Entry::factory()
        ->withField('featured', Lightswitch::class, ['default' => true], value: false)
        ->withField('promoted', Lightswitch::class, ['default' => true], value: false)
        ->createElementWithFields();

    $this->artisan('craft:resave:entries --set=featured --to-default')
        ->assertSuccessful();

    $entry = EntryElement::find()->id($result->element->id)->one();

    expect($entry->getFieldValue('featured'))->toBeTrue()
        ->and($entry->getFieldValue('promoted'))->toBeFalse();
});

it('requires a target field when setting fields to default values', function () {
    $this->artisan('craft:resave:entries --to-default')
        ->expectsOutputToContain('--with-fields or --set is required when using --to-default.')
        ->assertExitCode(1);
});

it('rejects invalid fields before resaving', function () {
    $this->artisan('craft:resave:entries --with-fields=missingField --to-default')
        ->expectsOutputToContain('Invalid field: `missingField`')
        ->assertExitCode(1);
});

it('rejects fields without default values when setting all matching fields to defaults', function () {
    Entry::factory()
        ->withField('bodyText', PlainText::class, value: 'Body')
        ->createElementWithFields();

    $this->artisan('craft:resave:entries --with-fields=bodyText --to-default')
        ->expectsOutputToContain('bodyText doesn’t support --to-default.')
        ->assertExitCode(1);
});

it('passes fields and default value options to queued resave jobs', function () {
    Entry::factory()
        ->withField('featured', Lightswitch::class, ['default' => true], value: false)
        ->createElementWithFields();

    Queue::fake();

    $this->artisan('craft:resave:entries --with-fields=featured --set=featured --to-default --queue')
        ->assertSuccessful();

    Queue::assertPushed(ResaveElements::class, fn (ResaveElements $job) => $job->withFields === ['featured']
        && $job->set === 'featured'
        && $job->toDefault);
});

it('rejects non-positive queue batch sizes', function (int $batchSize) {
    Queue::fake();

    $this->artisan("craft:resave:entries --queue --batch-size=$batchSize")
        ->expectsOutputToContain('--batch-size must be at least 1.')
        ->assertExitCode(1);

    Queue::assertNothingPushed();
})->with([
    'zero' => 0,
    'negative' => -1,
]);

it('filters users by group', function () {
    $group = UserGroup::factory()->create(['handle' => 'staff']);
    $groupedUser = User::factory()->createElement(['fullName' => 'Grouped User']);
    $otherUser = User::factory()->createElement(['fullName' => 'Other User']);

    app(Users::class)->assignUserToGroups($groupedUser->id, [$group->id]);

    User::query()->whereKey($groupedUser->id)->update(['fullName' => null]);
    User::query()->whereKey($otherUser->id)->update(['fullName' => null]);

    $this->artisan('craft:resave:users --group=staff --set=fullName --to="=Grouped" --if-empty')
        ->assertSuccessful();

    expect(UserElement::find()->id($groupedUser->id)->one()?->fullName)->toBe('Grouped')
        ->and(UserElement::find()->id($otherUser->id)->one()?->fullName)->toBeNull();
});

it('filters assets by volume', function () {
    $targetVolume = Volume::factory()->create(['handle' => 'images']);
    $otherVolume = Volume::factory()->create(['handle' => 'docs']);
    $targetFolder = VolumeFolder::factory()->create(['volumeId' => $targetVolume->id]);
    $otherFolder = VolumeFolder::factory()->create(['volumeId' => $otherVolume->id]);
    $targetAsset = Asset::factory()->createElement([
        'folderId' => $targetFolder->id,
        'volumeId' => $targetVolume->id,
    ]);
    $otherAsset = Asset::factory()->createElement([
        'folderId' => $otherFolder->id,
        'volumeId' => $otherVolume->id,
    ]);

    DB::table(Table::ASSETS)->where('id', $targetAsset->id)->update(['filename' => '']);
    DB::table(Table::ASSETS)->where('id', $otherAsset->id)->update(['filename' => '']);
    app()->forgetInstance(VolumesService::class);

    $this->artisan('craft:resave:assets --volume=images --set=filename --to="=renamed.jpg" --if-empty')
        ->assertSuccessful();

    expect(AssetElement::find()->id($targetAsset->id)->one()?->filename)->toBe('renamed.jpg')
        ->and(AssetElement::find()->id($otherAsset->id)->one()?->filename)->toBe('');
});

it('rejects invalid propagate-to and set combinations for entries', function () {
    $this->artisan('craft:resave:entries --propagate-to=defaultSite --set=title --to="=Nope"')
        ->expectsOutputToContain('--propagate-to can’t be coupled with --set.')
        ->assertExitCode(1);
});

it('requires to when set is passed to entries', function () {
    $this->artisan('craft:resave:entries --set=title')
        ->expectsOutputToContain('--to or --to-default is required when using --set.')
        ->assertExitCode(1);
});

it('filters entries by section and can propagate to another site', function () {
    $site = Site::factory()->create([
        'handle' => 'secondary',
        'primary' => false,
        'baseUrl' => 'https://secondary.test/',
        'language' => 'en-US',
        'groupId' => Site::first()->groupId,
    ]);
    app(CraftCms\Cms\Site\Sites::class)->refreshSites();
    Sites::getSiteByHandle('secondary');

    $targetSection = Section::factory()->create(['handle' => 'articles']);
    $otherSection = Section::factory()->create(['handle' => 'news']);
    $entryType = EntryType::factory()->create();
    $targetSection->entryTypes()->attach($entryType, ['sortOrder' => 1]);
    $otherSection->entryTypes()->attach($entryType, ['sortOrder' => 1]);
    SectionSiteSettings::factory()->create([
        'sectionId' => $targetSection->id,
        'siteId' => $site->id,
        'hasUrls' => true,
    ]);
    SectionSiteSettings::factory()->create([
        'sectionId' => $otherSection->id,
        'siteId' => $site->id,
        'hasUrls' => true,
    ]);

    $entry = Entry::factory()->forSection($targetSection)->forEntryType($entryType)->createElement();
    $otherEntry = Entry::factory()->forSection($otherSection)->forEntryType($entryType)->createElement();

    DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->update(['title' => null]);
    DB::table(Table::ELEMENTS_SITES)->where('elementId', $otherEntry->id)->update(['title' => null]);

    $this->artisan('craft:resave:entries --section=articles --set=title --to="=Section Match" --if-empty')
        ->assertSuccessful();

    $this->artisan('craft:resave:entries --element-id='.$entry->id.' --propagate-to=secondary --set-enabled-for-site=true')
        ->assertSuccessful();

    expect(EntryElement::find()->id($entry->id)->site('defaultSite')->one()?->title)->toBe('Section Match')
        ->and(EntryElement::find()->id($otherEntry->id)->site('defaultSite')->one()?->title)->toBeNull()
        ->and(DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->where('siteId', $site->id)->exists())->toBeTrue();
});

it('runs registered resave commands', function () {
    $handled = false;
    Artisan::setArtisan(null);
    Artisan::command('craft:resave:custom', function () use (&$handled) {
        $handled = true;
    })->purpose('Custom resave command');

    $this->artisan('craft:resave:all --no-interaction')->assertSuccessful();

    expect($handled)->toBeTrue();
});
