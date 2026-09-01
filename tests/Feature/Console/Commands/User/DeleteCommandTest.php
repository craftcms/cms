<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\ElementDeleting;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('deletes authored content', function (bool $hardDelete) {
    $user = User::factory()->create(['username' => 'deleted-user']);
    $entry = Entry::factory()
        ->hasAttached($user, ['sortOrder' => 1], 'authors')
        ->createElement();

    $this->artisan('craft:users:delete', [
        'user' => $user->id,
        '--delete-content' => true,
        '--hard' => $hardDelete,
    ])
        ->expectsConfirmation('Delete user “deleted-user” and their content?', 'yes')
        ->assertSuccessful();

    $userRecord = ElementModel::withTrashed()->find($user->id);
    $entryRecord = ElementModel::withTrashed()->find($entry->id);

    expect($userRecord === null)->toBe($hardDelete)
        ->and($entryRecord === null)->toBe($hardDelete)
        ->and($userRecord?->dateDeleted !== null)->toBe(! $hardDelete)
        ->and($entryRecord?->dateDeleted !== null)->toBe(! $hardDelete);
})->with([
    'soft deletion' => false,
    'hard deletion' => true,
]);

it('deletes multi-author entries', function () {
    $user = User::factory()->create(['username' => 'deleted-user']);
    $otherAuthor = User::factory()->create();
    $entry = Entry::factory()
        ->hasAttached($user, ['sortOrder' => 1], 'authors')
        ->hasAttached($otherAuthor, ['sortOrder' => 2], 'authors')
        ->createElement();

    $this->artisan('craft:users:delete', [
        'user' => $user->id,
        '--delete-content' => true,
    ])
        ->expectsConfirmation('Delete user “deleted-user” and their content?', 'yes')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id)?->dateDeleted)->not()->toBeNull();
});

it('records an interactive choice to delete content', function () {
    $user = User::factory()->create(['username' => 'deleted-user']);
    $entry = Entry::factory()
        ->hasAttached($user, ['sortOrder' => 1], 'authors')
        ->createElement();

    $this->artisan('craft:users:delete', ['user' => $user->id])
        ->expectsConfirmation('Transfer this user’s content to an existing user?', 'no')
        ->expectsConfirmation('Delete user “deleted-user” and their content?', 'yes')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id)?->dateDeleted)->not()->toBeNull();
});

it('rejects the deleted user as their own content inheritor', function () {
    $user = User::factory()->create(['username' => 'deleted-user']);

    $this->artisan('craft:users:delete', [
        'user' => $user->id,
        '--inheritor' => $user->id,
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('A user cannot inherit their own content.')
        ->assertFailed();

    expect(ElementModel::find($user->id))->not()->toBeNull();
});

it('rolls back content reassignment when user deletion fails', function () {
    $user = User::factory()->create(['username' => 'deleted-user']);
    $inheritor = User::factory()->create(['username' => 'inheritor']);
    $entry = Entry::factory()
        ->hasAttached($user, ['sortOrder' => 1], 'authors')
        ->create();

    Event::listen(ElementDeleting::class, function (ElementDeleting $event) use ($user) {
        if ($event->element instanceof UserElement && $event->element->id === $user->id) {
            $event->isValid = false;
        }
    });

    $this->artisan('craft:users:delete', [
        'user' => $user->id,
        '--inheritor' => $inheritor->id,
    ])
        ->expectsConfirmation('Delete user “deleted-user” and transfer their content to user “inheritor”?', 'yes')
        ->expectsOutputToContain('Couldn’t delete the user.')
        ->assertFailed();

    expect(DB::table(Table::ENTRIES_AUTHORS)
        ->where('entryId', $entry->id)
        ->pluck('authorId')
        ->all())->toBe([$user->id]);
});
