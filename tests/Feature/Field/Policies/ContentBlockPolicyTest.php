<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\Field\Policies\ContentBlockPolicy;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->policy = app(ContentBlockPolicy::class);
});

it('is registered with the gate', function () {
    expect(Gate::getPolicyFor(ContentBlock::class))->toBeInstanceOf(ContentBlockPolicy::class);
});

it('returns false without owner for view', function () {
    $user = User::findOrFail(CraftCms\Cms\User\Elements\User::findOne()->id);
    $contentBlock = createTestContentBlock(owner: null);

    $result = $user->can('view', $contentBlock);

    expect($result)->toBeFalse();
});

it('returns false without owner for save', function () {
    $user = User::findOrFail(CraftCms\Cms\User\Elements\User::findOne()->id);
    $contentBlock = createTestContentBlock(owner: null);

    $result = $user->can('save', $contentBlock);

    expect($result)->toBeFalse();
});

it('returns false without owner for delete', function () {
    $user = User::findOrFail(CraftCms\Cms\User\Elements\User::findOne()->id);
    $contentBlock = createTestContentBlock(owner: null);

    $result = $user->can('delete', $contentBlock);

    expect($result)->toBeFalse();
});

it('returns false without owner for duplicate', function () {
    $user = User::findOrFail(CraftCms\Cms\User\Elements\User::findOne()->id);
    $contentBlock = createTestContentBlock(owner: null);

    $result = $user->can('duplicate', $contentBlock);

    expect($result)->toBeFalse();
});

it('create drafts always returns true', function () {
    $user = User::findOrFail(CraftCms\Cms\User\Elements\User::findOne()->id);
    $contentBlock = createTestContentBlock(owner: null);

    $result = $user->can('createDrafts', $contentBlock);

    expect($result)->toBeTrue();
});

function createTestContentBlock(?Entry $owner): ContentBlock
{
    $contentBlock = new ContentBlock;
    $contentBlock->siteId = null;
    $contentBlock->owner = $owner;
    $contentBlock->ownerId = $owner?->id;

    return $contentBlock;
}
