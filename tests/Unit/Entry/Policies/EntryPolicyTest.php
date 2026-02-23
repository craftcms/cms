<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Policies\EntryPolicy;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->policy = app(EntryPolicy::class);

    $this->channelSection = new Section([
        'id' => 1,
        'name' => 'Blog',
        'handle' => 'blog',
        'type' => SectionType::Channel,
        'uid' => 'channel-section-uid',
        'propagationMethod' => PropagationMethod::All,
    ]);

    $this->singleSection = new Section([
        'id' => 2,
        'name' => 'Homepage',
        'handle' => 'homepage',
        'type' => SectionType::Single,
        'uid' => 'single-section-uid',
        'propagationMethod' => PropagationMethod::All,
    ]);

    $this->customPropagationSection = new Section([
        'id' => 3,
        'name' => 'Products',
        'handle' => 'products',
        'type' => SectionType::Channel,
        'uid' => 'custom-propagation-uid',
        'propagationMethod' => PropagationMethod::Custom,
    ]);
});

it('is registered with the gate', function () {
    $entry = new Entry;
    $user = createEntryTestUser([]);

    $result = Gate::forUser($user)->allows('view', $entry);

    expect($result)->toBeBool();
});

it('returns false without section for view', function () {
    $user = createEntryTestUser([]);
    $entry = new Entry;

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeFalse();
});

it('returns false without view entries permission', function () {
    $user = createEntryTestUser([]);
    $entry = createEntryTestEntry($this->channelSection);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeFalse();
});

it('returns true for single with view entries permission', function () {
    $user = createEntryTestUser(['viewEntries:single-section-uid']);
    $entry = createEntryTestEntry($this->singleSection);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeTrue();
});

it('returns true when user is author', function () {
    $user = createEntryTestUser(['viewEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [$user->id]);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeTrue();
});

it('returns true with view peer entries permission', function () {
    $user = createEntryTestUser([
        'viewEntries:channel-section-uid',
        'viewPeerEntries:channel-section-uid',
    ]);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [999]);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeTrue();
});

it('returns false for peer entry without peer permission', function () {
    $user = createEntryTestUser(['viewEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [999]);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeFalse();
});

it('returns true for draft creator', function () {
    $user = createEntryTestUser(['viewEntries:channel-section-uid']);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: $user->id);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeTrue();
});

it('returns true for peer draft with view peer drafts permission', function () {
    $user = createEntryTestUser([
        'viewEntries:channel-section-uid',
        'viewPeerEntryDrafts:channel-section-uid',
    ]);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: 999);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeTrue();
});

it('returns false for peer draft without peer drafts permission', function () {
    $user = createEntryTestUser(['viewEntries:channel-section-uid']);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: 999);

    $result = $this->policy->view($user, $entry);

    expect($result)->toBeFalse();
});

it('returns false without section for save', function () {
    $user = createEntryTestUser([]);
    $entry = new Entry;

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeFalse();
});

it('requires create entries permission for new entry', function () {
    $user = createEntryTestUser(['createEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection, id: null);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeTrue();
});

it('does not allow saving new single entry', function () {
    $user = createEntryTestUser(['createEntries:single-section-uid']);
    $entry = createEntryTestEntry($this->singleSection, id: null);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeFalse();
});

it('allows draft creator to save draft', function () {
    $user = createEntryTestUser([]);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: $user->id);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeTrue();
});

it('allows peer draft with save peer drafts permission', function () {
    $user = createEntryTestUser(['savePeerEntryDrafts:channel-section-uid']);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: 999);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeTrue();
});

it('denies peer draft without save peer drafts permission', function () {
    $user = createEntryTestUser([]);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: 999);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeFalse();
});

it('allows author to save existing entry', function () {
    $user = createEntryTestUser(['saveEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [$user->id]);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeTrue();
});

it('allows peer entry with save peer entries permission', function () {
    $user = createEntryTestUser([
        'saveEntries:channel-section-uid',
        'savePeerEntries:channel-section-uid',
    ]);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [999]);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeTrue();
});

it('allows saving single with save entries permission', function () {
    $user = createEntryTestUser(['saveEntries:single-section-uid']);
    $entry = createEntryTestEntry($this->singleSection);

    $result = $this->policy->save($user, $entry);

    expect($result)->toBeTrue();
});

it('returns false without section for delete', function () {
    $user = createEntryTestUser([]);
    $entry = new Entry;

    $result = $this->policy->delete($user, $entry);

    expect($result)->toBeFalse();
});

it('does not allow deleting single entry', function () {
    $user = createEntryTestUser([
        'deleteEntries:single-section-uid',
        'deletePeerEntries:single-section-uid',
    ]);
    $entry = createEntryTestEntry($this->singleSection);

    $result = $this->policy->delete($user, $entry);

    expect($result)->toBeFalse();
});

it('allows deleting single draft by creator', function () {
    $user = createEntryTestUser([]);
    $entry = createEntryTestDraft($this->singleSection, draftCreatorId: $user->id);

    $result = $this->policy->delete($user, $entry);

    expect($result)->toBeTrue();
});

it('allows draft creator to delete draft', function () {
    $user = createEntryTestUser([]);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: $user->id);

    $result = $this->policy->delete($user, $entry);

    expect($result)->toBeTrue();
});

it('allows peer draft with delete peer drafts permission', function () {
    $user = createEntryTestUser(['deletePeerEntryDrafts:channel-section-uid']);
    $entry = createEntryTestDraft($this->channelSection, draftCreatorId: 999);

    $result = $this->policy->delete($user, $entry);

    expect($result)->toBeTrue();
});

it('allows author to delete entry', function () {
    $user = createEntryTestUser(['deleteEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [$user->id]);

    $result = $this->policy->delete($user, $entry);

    expect($result)->toBeTrue();
});

it('allows peer entry with delete peer entries permission', function () {
    $user = createEntryTestUser([
        'deleteEntries:channel-section-uid',
        'deletePeerEntries:channel-section-uid',
    ]);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [999]);

    $result = $this->policy->delete($user, $entry);

    expect($result)->toBeTrue();
});

it('returns false for duplicate of singles', function () {
    $user = createEntryTestUser([
        'createEntries:single-section-uid',
        'saveEntries:single-section-uid',
    ]);
    $entry = createEntryTestEntry($this->singleSection);

    $result = $this->policy->duplicate($user, $entry);

    expect($result)->toBeFalse();
});

it('requires create and save permissions for duplicate', function () {
    $user = createEntryTestUser([
        'createEntries:channel-section-uid',
        'saveEntries:channel-section-uid',
    ]);
    $entry = createEntryTestEntry($this->channelSection);

    $result = $this->policy->duplicate($user, $entry);

    expect($result)->toBeTrue();
});

it('fails duplicate without save permission', function () {
    $user = createEntryTestUser(['createEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection);

    $result = $this->policy->duplicate($user, $entry);

    expect($result)->toBeFalse();
});

it('requires only create permission for duplicate as draft', function () {
    $user = createEntryTestUser(['createEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection);

    $result = $this->policy->duplicateAsDraft($user, $entry);

    expect($result)->toBeTrue();
});

it('returns false for duplicate as draft of singles', function () {
    $user = createEntryTestUser(['createEntries:single-section-uid']);
    $entry = createEntryTestEntry($this->singleSection);

    $result = $this->policy->duplicateAsDraft($user, $entry);

    expect($result)->toBeFalse();
});

it('returns same result for copy as view', function () {
    $user = createEntryTestUser(['viewEntries:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [$user->id]);

    $viewResult = $this->policy->view($user, $entry);
    $copyResult = $this->policy->copy($user, $entry);

    expect($copyResult)->toBe($viewResult);
});

it('create drafts always returns true', function () {
    $user = createEntryTestUser([]);
    $entry = createEntryTestEntry($this->channelSection);

    $result = $this->policy->createDrafts($user, $entry);

    expect($result)->toBeTrue();
});

it('returns false for delete for site with non-custom propagation', function () {
    $user = createEntryTestUser(['deleteEntriesForSite:channel-section-uid']);
    $entry = createEntryTestEntry($this->channelSection, authorIds: [$user->id]);

    $result = $this->policy->deleteForSite($user, $entry);

    expect($result)->toBeFalse();
});

it('allows draft creator to delete for site', function () {
    $user = createEntryTestUser([]);
    $entry = createEntryTestDraft($this->customPropagationSection, draftCreatorId: $user->id);

    $result = $this->policy->deleteForSite($user, $entry);

    expect($result)->toBeTrue();
});

it('allows peer draft with delete peer drafts permission for site', function () {
    $user = createEntryTestUser(['deletePeerEntryDrafts:custom-propagation-uid']);
    $entry = createEntryTestDraft($this->customPropagationSection, draftCreatorId: 999);

    $result = $this->policy->deleteForSite($user, $entry);

    expect($result)->toBeTrue();
});

it('allows author to delete for site', function () {
    $user = createEntryTestUser(['deleteEntriesForSite:custom-propagation-uid']);
    $entry = createEntryTestEntry($this->customPropagationSection, authorIds: [$user->id]);

    $result = $this->policy->deleteForSite($user, $entry);

    expect($result)->toBeTrue();
});

it('allows peer entry with delete peer entries for site permission', function () {
    $user = createEntryTestUser([
        'deleteEntriesForSite:custom-propagation-uid',
        'deletePeerEntriesForSite:custom-propagation-uid',
    ]);
    $entry = createEntryTestEntry($this->customPropagationSection, authorIds: [999]);

    $result = $this->policy->deleteForSite($user, $entry);

    expect($result)->toBeTrue();
});

// Helper functions
function createEntryTestUser(array $permissions): User
{
    $user = new class extends User
    {
        public array $grantedPermissions = [];

        public function can($abilities, $arguments = []): bool
        {
            if (is_array($abilities)) {
                return array_all($abilities, fn ($ability) => $this->can($ability, $arguments));
            }

            return in_array($abilities, $this->grantedPermissions, true);
        }
    };

    $user->id = random_int(1, 10000);
    $user->grantedPermissions = $permissions;

    return $user;
}

function createEntryTestEntry(Section $section, ?int $id = 100, array $authorIds = []): Entry
{
    $entry = new class extends Entry
    {
        public ?Section $mockSection = null;

        public array $mockAuthorIds = [];

        public function getSection(): ?Section
        {
            return $this->mockSection;
        }

        public function getAuthorIds(): array
        {
            return $this->mockAuthorIds;
        }
    };

    $entry->id = $id;
    $entry->siteId = null;
    $entry->sectionId = $section->id;
    $entry->mockSection = $section;
    $entry->mockAuthorIds = $authorIds;

    return $entry;
}

function createEntryTestDraft(Section $section, int $draftCreatorId): Entry
{
    $entry = new class extends Entry
    {
        public ?Section $mockSection = null;

        public bool $mockIsDraft = false;

        public function getSection(): ?Section
        {
            return $this->mockSection;
        }

        public function getIsDraft(): bool
        {
            return $this->mockIsDraft;
        }
    };

    $entry->id = 100;
    $entry->siteId = null;
    $entry->sectionId = $section->id;
    $entry->mockSection = $section;
    $entry->mockIsDraft = true;
    $entry->draftCreatorId = $draftCreatorId;

    return $entry;
}
