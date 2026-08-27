<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\EventTypes\CommentDeleted;
use CraftCms\Cms\Activity\EventTypes\CommentEdited;
use CraftCms\Cms\Activity\EventTypes\CommentEvent;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Markdown\Markdown;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ActivityMentionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeIterator;
use LogicException;
use Throwable;
use UnexpectedValueException;

use function CraftCms\Cms\t;

class ActivityComments
{
    public function __construct(
        private readonly ActivityEventRecorder $events,
        private readonly HtmlSanitizerManager $htmlSanitizers,
        private readonly Markdown $markdown,
    ) {}

    public function create(
        ElementInterface $subject,
        User $author,
        Site $site,
        string $markdown,
    ): ActivityEvent {
        $this->validate($markdown);

        return DB::transaction(function () use ($subject, $author, $site, $markdown): ActivityEvent {
            $event = $this->events->record(new CommentCreated(
                subject: $subject,
                actor: $author,
                site: $site,
                markdown: $markdown,
                authorId: $author->id,
                authorLabel: $author->name,
                mentions: $this->resolveMentions($markdown, $subject),
            ));

            $this->scheduleMentionNotifications($event, $event);

            return $event;
        });
    }

    public function edit(
        ActivityEvent $comment,
        User $author,
        string $markdown,
        ?ElementInterface $subject = null,
    ): ActivityEvent {
        $this->validate($markdown);

        return $this->mutate($comment, $author, CommentEdited::class, $markdown, $subject);
    }

    public function delete(ActivityEvent $comment, User $actor): ActivityEvent
    {
        return $this->mutate($comment, $actor, CommentDeleted::class);
    }

    public function canMention(User $user, ElementInterface $subject): bool
    {
        return $user->getStatus() === User::STATUS_ACTIVE
            && $user->can('accessCp')
            && Gate::forUser($user)->allows('view', $subject);
    }

    public function render(ActivityEvent $version): HtmlString
    {
        $mentionData = $version->data['mentions'] ?? [];

        if (! is_array($mentionData)) {
            throw new UnexpectedValueException('Activity comment mentions must be an array.');
        }

        $mentions = collect($mentionData)->keyBy('id');
        $users = User::find()
            ->id($mentions->keys()->all())
            ->status(null)
            ->collect()
            ->keyBy('id');
        $html = $this->markdown->transform(
            $version->data['markdown'],
            function (Document $document) use ($mentions, $users): void {
                foreach ($this->mentionLinks($document) as [$node, $reference]) {
                    if (! ctype_digit($reference)) {
                        continue;
                    }

                    $id = (int) $reference;
                    $mention = $mentions->get($id);

                    if ($mention === null) {
                        continue;
                    }

                    $user = $users->get($id);
                    $canView = $user !== null && Gate::check('view', $user);
                    $username = $canView ? ($user->username ?? $mention['username']) : $mention['username'];

                    $node->replaceWith($canView && $user->getCpEditUrl() !== null
                        ? new Link($user->getCpEditUrl(), "@$username")
                        : new Text("@$username"));
                }
            },
            Markdown::FLAVOR_GFM_COMMENT,
        );

        return new HtmlString($this->htmlSanitizers->sanitize($html));
    }

    /** @param class-string<CommentEvent> $eventType */
    private function mutate(
        ActivityEvent $comment,
        User $actor,
        string $eventType,
        ?string $markdown = null,
        ?ElementInterface $liveSubject = null,
    ): ActivityEvent {
        return DB::transaction(function () use ($comment, $actor, $eventType, $markdown, $liveSubject): ActivityEvent {
            $root = ActivityEvent::query()
                ->whereKey($comment->id)
                ->where('eventType', CommentCreated::class)
                ->whereNull('rootEventId')
                ->lockForUpdate()
                ->firstOrFail();
            $current = ActivityEvent::query()
                ->where('rootEventId', $root->id)
                ->newestFirst()
                ->first() ?? $root;

            if ($current->eventType === CommentDeleted::class) {
                throw ValidationException::withMessages([
                    'commentId' => t('This comment has been removed.'),
                ]);
            }

            $subject = new ActivitySubject(
                $root->subjectType,
                $root->subjectId,
                $root->snapshots['subject']['label'],
            );
            $site = $root->siteId === null ? null : Site::get($root->siteId);

            if ($site === null) {
                throw new LogicException('Activity comments require a current site.');
            }

            $event = $this->events->record(new $eventType(
                subject: $subject,
                actor: $actor,
                site: $site,
                markdown: $markdown ?? $current->data['markdown'],
                authorId: $root->data['author']['id'],
                authorLabel: $root->data['author']['label'],
                mentions: $markdown === null
                    ? ($current->data['mentions'] ?? [])
                    : $this->resolveMentions($markdown, $liveSubject),
            ), rootEventId: $root->id);

            if ($markdown !== null) {
                $this->scheduleMentionNotifications($root, $event);
            }

            return $event;
        });
    }

    private function scheduleMentionNotifications(ActivityEvent $comment, ActivityEvent $version): void
    {
        foreach ($version->data['mentions'] as $mention) {
            $pair = [
                'activityEventId' => $comment->id,
                'userId' => $mention['id'],
            ];

            if (DB::table(Table::ACTIVITYNOTIFICATIONS)->where($pair)->exists()) {
                continue;
            }

            DB::table(Table::ACTIVITYNOTIFICATIONS)->insert([
                ...$pair,
                'versionEventId' => $version->id,
            ]);

            DB::afterCommit(function () use ($mention, $pair, $version): void {
                try {
                    UserModel::query()
                        ->findOrFail($mention['id'])
                        ->notify(new ActivityMentionNotification($version->id));
                } catch (Throwable $exception) {
                    DB::table(Table::ACTIVITYNOTIFICATIONS)
                        ->where($pair)
                        ->where('versionEventId', $version->id)
                        ->delete();
                    report($exception);
                }
            });
        }
    }

    /** @return list<array{id: int, username: string}> */
    private function resolveMentions(string $markdown, ?ElementInterface $subject): array
    {
        $references = [];
        $this->markdown->transform(
            $markdown,
            function (Document $document) use (&$references): void {
                foreach ($this->mentionLinks($document) as [, $reference]) {
                    $references[] = $reference;
                }
            },
            Markdown::FLAVOR_GFM_COMMENT,
        );

        if ($references === []) {
            return [];
        }

        $ids = collect($references)
            ->map(function (string $id): int {
                if (! ctype_digit($id)) {
                    throw ValidationException::withMessages([
                        'markdown' => t('Comment contains an invalid user mention.'),
                    ]);
                }

                return (int) $id;
            })
            ->unique()
            ->values();
        $users = User::find()
            ->id($ids->all())
            ->status(User::STATUS_ACTIVE)
            ->collect()
            ->keyBy('id');

        return $ids->map(function (int $id) use ($subject, $users): array {
            $user = $users->get($id);

            if ($subject === null || $user === null || ! $this->canMention($user, $subject)) {
                throw ValidationException::withMessages([
                    'markdown' => t('Comment contains an ineligible user mention.'),
                ]);
            }

            return ['id' => $user->id, 'username' => $user->username];
        })->all();
    }

    /** @return iterable<int, array{Link, string}> */
    private function mentionLinks(Document $document): iterable
    {
        foreach (new NodeIterator($document) as $node) {
            if ($node instanceof Link && str_starts_with($node->getUrl(), 'craft-user:')) {
                yield [$node, substr($node->getUrl(), strlen('craft-user:'))];
            }
        }
    }

    private function validate(string $markdown): void
    {
        if (blank($markdown)) {
            throw ValidationException::withMessages([
                'markdown' => t('Comment cannot be blank.'),
            ]);
        }
    }
}
