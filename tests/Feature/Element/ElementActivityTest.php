<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use craft\models\ElementActivity as LegacyElementActivity;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Data\ElementActivity as ElementActivityData;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementActivity as ElementActivityService;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\ElementActivity as ElementActivityFacade;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->elementActivity = app(ElementActivityService::class);
    $this->drafts = app(Drafts::class);
});

it('is a singleton and is available via the facade', function () {
    expect(app(ElementActivityService::class))->toBe(app(ElementActivityService::class));

    expect($this->elementActivity)->toBe(ElementActivityFacade::getFacadeRoot());
});

it('tracks activity for the authenticated user on canonical elements', function () {
    $user = UserModel::factory()->createElement();
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);

    actingAs($user);

    $this->elementActivity->trackActivity($entry, ElementActivityType::View);

    $activity = DB::table(Table::ELEMENTACTIVITY)->first();

    expect($activity->elementId)->toBe($entry->getCanonicalId())
        ->and($activity->userId)->toBe($user->id)
        ->and($activity->siteId)->toBe($entry->siteId)
        ->and($activity->draftId)->toBeNull()
        ->and($activity->type)->toBe(ElementActivityType::View->value);
});

it('tracks draft activity with the draft id for non-provisional drafts', function () {
    $user = UserModel::factory()->createElement();
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);
    $draft = $this->drafts->createDraft($entry, $user->id, name: 'Draft 1');

    $this->elementActivity->trackActivity($draft, ElementActivityType::Edit, $user);

    $activity = DB::table(Table::ELEMENTACTIVITY)->first();

    expect($activity->elementId)->toBe($entry->id)
        ->and($activity->draftId)->toBe($draft->draftId)
        ->and($activity->type)->toBe(ElementActivityType::Edit->value);
});

it('normalizes provisional draft saves to edit activity', function () {
    $user = UserModel::factory()->createElement();
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);
    $draft = $this->drafts->createDraft($entry, $user->id, provisional: true);

    $this->elementActivity->trackActivity($draft, ElementActivityType::Save, $user);

    $activity = DB::table(Table::ELEMENTACTIVITY)->first();

    expect($activity->elementId)->toBe($entry->id)
        ->and($activity->draftId)->toBeNull()
        ->and($activity->type)->toBe(ElementActivityType::Edit->value);
});

it('throws when no user is provided and nobody is authenticated', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);

    Auth::logout();

    $this->elementActivity->trackActivity($entry, ElementActivityType::View);
})->throws(InvalidArgumentException::class, '$user must be set if no user is signed in.');

it('returns an empty array when there is no recent activity', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);

    expect($this->elementActivity->getRecentActivity($entry))->toBeEmpty();
});

it('excludes a provided user id from recent activity results', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);
    $excludedUser = UserModel::factory()->createElement();
    $includedUser = UserModel::factory()->createElement();

    insertElementActivity($entry, $excludedUser, ElementActivityType::View, now()->subSeconds(10));
    insertElementActivity($entry, $includedUser, ElementActivityType::Edit, now()->subSeconds(5));

    $activity = $this->elementActivity->getRecentActivity($entry, $excludedUser->id);

    expect($activity)->toHaveCount(1)
        ->and($activity[0]->user->id)->toBe($includedUser->id);
});

it('prefers edit activity over a newer view for the same user', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);
    $user = UserModel::factory()->createElement();

    insertElementActivity($entry, $user, ElementActivityType::View, now()->subSeconds(5));
    insertElementActivity($entry, $user, ElementActivityType::Edit, now()->subSeconds(10));

    $activity = $this->elementActivity->getRecentActivity($entry);

    expect($activity)->toHaveCount(1)
        ->and($activity[0])->toBeInstanceOf(ElementActivityData::class)
        ->and($activity[0]->type)->toBe(ElementActivityType::Edit);
});

it('reuses the passed element and loads other related variants', function () {
    $draftUser = UserModel::factory()->createElement();
    $canonicalUser = UserModel::factory()->createElement();
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);
    $draft = $this->drafts->createDraft($entry, $draftUser->id, name: 'Draft 1');

    insertElementActivity($draft, $draftUser, ElementActivityType::Edit, now()->subSeconds(5));
    insertElementActivity($entry, $canonicalUser, ElementActivityType::View, now()->subSeconds(10));

    $activity = $this->elementActivity->getRecentActivity($draft);

    $draftActivity = collect($activity)->first(fn (ElementActivityData $record) => $record->user->id === $draftUser->id);
    $canonicalActivity = collect($activity)->first(fn (ElementActivityData $record) => $record->user->id === $canonicalUser->id);

    expect($draftActivity?->element)->toBe($draft)
        ->and($canonicalActivity)->not->toBeNull()
        ->and($canonicalActivity->element->id)->toBe($entry->id)
        ->and($canonicalActivity->element->draftId)->toBeNull();
});

it('skips missing related elements when building recent activity', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);
    $user = UserModel::factory()->createElement();
    $draft = $this->drafts->createDraft($entry, $user->id, name: 'Draft 1');

    insertElementActivity($draft, $user, ElementActivityType::Edit, now()->subSeconds(5));

    DB::table(Table::ELEMENTS)->where('id', $draft->id)->delete();

    expect($this->elementActivity->getRecentActivity($entry))->toBeEmpty();
});

it('maps recent activity back to the legacy model through the elements service', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Canonical entry']);
    $user = UserModel::factory()->createElement();

    insertElementActivity($entry, $user, ElementActivityType::View, now()->subSeconds(5));

    $activity = Craft::$app->getElements()->getRecentActivity($entry);

    expect($activity)->toHaveCount(1)
        ->and($activity[0])->toBeInstanceOf(LegacyElementActivity::class)
        ->and($activity[0]->user->id)->toBe($user->id)
        ->and($activity[0]->element->id)->toBe($entry->id)
        ->and($activity[0]->type)->toBe(ElementActivityType::View->value);
});

function insertElementActivity(
    ElementInterface $element,
    User $user,
    ElementActivityType $type,
    mixed $timestamp,
): void {
    $isCanonical = $element->getIsCanonical() || $element->isProvisionalDraft;

    DB::table(Table::ELEMENTACTIVITY)->insert([
        'elementId' => $element->getCanonicalId(),
        'userId' => $user->id,
        'siteId' => $element->siteId,
        'draftId' => $isCanonical ? null : $element->draftId,
        'type' => $type->value,
        'timestamp' => $timestamp,
    ]);
}
