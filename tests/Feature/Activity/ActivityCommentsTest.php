<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\EventTypes\CommentDeleted;
use CraftCms\Cms\Activity\EventTypes\CommentEdited;
use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ActivityMentionNotification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Edition::set(Edition::Pro);
    Site::factory()->create();
    Sites::refreshSites();
    Notification::fake();

    $this->comments = app(ActivityComments::class);
    $this->author = User::findOne();
    $this->entry = Entry::factory()->createElement(['title' => 'Release notes']);
    $this->site = Sites::getSiteById($this->entry->siteId);
    $this->mentionPermissions = [
        'accessCp',
        "editSite:{$this->site->uid}",
        "viewEntries:{$this->entry->getSection()->uid}",
        "viewPeerEntries:{$this->entry->getSection()->uid}",
    ];
    $this->mentioned = UserModel::factory()
        ->withPermissions($this->mentionPermissions)
        ->createElement(['admin' => false, 'username' => 'grace']);

    $this->actingAs($this->author);
    DB::table(Table::ACTIVITYEVENTS)->delete();
});

it('records immutable comment lifecycle versions', function () {
    $created = $this->comments->create($this->entry, $this->author, null, 'First version');
    $edited = $this->comments->edit($created, $this->author, 'Second version', $this->entry);
    $deleted = $this->comments->delete($created, $this->author);

    expect($created->eventType)->toBe(CommentCreated::class)
        ->and($created->rootEventId)->toBeNull()
        ->and($created->siteId)->toBeNull()
        ->and($edited->eventType)->toBe(CommentEdited::class)
        ->and($edited->rootEventId)->toBe($created->id)
        ->and($deleted->eventType)->toBe(CommentDeleted::class)
        ->and($deleted->rootEventId)->toBe($created->id)
        ->and($deleted->data['markdown'])->toBe('Second version');

    expect(fn () => $this->comments->edit($created, $this->author, 'Resurrected', $this->entry))
        ->toThrow(ValidationException::class);
});

it('stores and renders eligible mentions and ignores invalid mentions', function () {
    $comment = $this->comments->create(
        $this->entry,
        $this->author,
        $this->site,
        "Hello [@grace](craft-user:{$this->mentioned->id}) and @plain.",
    );
    $document = new DOMDocument;
    $document->loadHTML(
        $this->comments->render($comment, $this->author)->toHtml(),
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
    );

    expect($comment->data['mentions'])->toBe([[
        'id' => $this->mentioned->id,
        'username' => 'grace',
    ]])
        ->and($document->textContent)->toBe('Hello @grace and @plain.');

    UserModel::query()->whereKey($this->mentioned->id)->update(['username' => 'hopper']);
    $document->loadHTML(
        $this->comments->render($comment, $this->author)->toHtml(),
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
    );

    expect($document->textContent)->toBe('Hello @hopper and @plain.');

    $ineligible = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement(['admin' => false, 'username' => 'ineligible']);

    $ignored = $this->comments->create(
        $this->entry,
        $this->author,
        $this->site,
        "Hello [@ineligible](craft-user:{$ineligible->id}) and [@invalid](craft-user:not-a-number).",
    );
    $document->loadHTML(
        $this->comments->render($ignored, $this->author)->toHtml(),
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
    );

    expect($ignored->data['mentions'])->toBe([])
        ->and($document->textContent)->toBe('Hello @ineligible and @invalid.')
        ->and(fn () => $this->comments->create(
            $this->entry,
            $this->author,
            $this->site,
            " \n\t ",
        ))->toThrow(ValidationException::class);
});

it('normalizes CommonMark mention links in notification comments', function (Closure $mention) {
    $comment = $this->comments->create(
        $this->entry,
        $this->author,
        $this->site,
        sprintf('Hello **A & B**, %s.', $mention($this->mentioned->id)),
    );
    $notifiable = UserModel::query()->findOrFail($this->mentioned->id);

    expect(new ActivityMentionNotification($comment)->toMail($notifiable)->variables['comment'])
        ->toBe('Hello A & B, @grace.');
})->with([
    'plain destination' => fn (int $id): string => "[@grace](craft-user:$id)",
    'angle destination' => fn (int $id): string => "[@grace](<craft-user:$id>)",
    'destination with title' => fn (int $id): string => "[@grace](craft-user:$id \"Grace Hopper\")",
]);

it('rechecks complete mention eligibility before sending', function () {
    $comment = $this->comments->create(
        $this->entry,
        $this->author,
        $this->site,
        "Hello [@grace](craft-user:{$this->mentioned->id}).",
    );
    $notification = unserialize(serialize(new ActivityMentionNotification($comment)));
    $notifiable = UserModel::query()->findOrFail($this->mentioned->id);

    expect($notification)->toBeInstanceOf(CpNotification::class)
        ->and($notification->via($notifiable))->toBe([DatabaseChannel::class, MailChannel::class])
        ->and($notification->toDatabase($notifiable))->toMatchArray([
            'title' => 'comment_mention_subject',
            'message' => 'Hello @grace.',
            'byline' => $this->author->name,
            'icon' => 'comment',
            'url' => $this->entry->getCpEditUrl(),
        ])
        ->and($notification->shouldSend($notifiable, 'mail'))->toBeTrue();

    UserPermissions::saveUserPermissions(
        $this->mentioned->id,
        array_values(array_diff($this->mentionPermissions, ['accessCp'])),
    );
    UserPermissions::reset();

    expect($notification->shouldSend($notifiable, 'mail'))->toBeFalse();
});

it('notifies users added by comment edits', function () {
    $added = UserModel::factory()
        ->withPermissions($this->mentionPermissions)
        ->createElement(['admin' => false, 'username' => 'ada']);
    $markdown = "Hello [@grace](craft-user:{$this->mentioned->id}).";
    $comment = $this->comments->create($this->entry, $this->author, $this->site, $markdown);
    $editedMarkdown = "$markdown And [@ada](craft-user:{$added->id}).";

    $this->comments->edit($comment, $this->author, $editedMarkdown, $this->entry);
    $this->comments->edit($comment, $this->author, $editedMarkdown, $this->entry);

    Notification::assertSentTimes(ActivityMentionNotification::class, 2);
    Notification::assertSentTo(UserModel::query()->findOrFail($this->mentioned->id), ActivityMentionNotification::class);
    Notification::assertSentTo(UserModel::query()->findOrFail($added->id), ActivityMentionNotification::class);
});
