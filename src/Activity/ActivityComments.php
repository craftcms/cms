<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\EventTypes\CommentDeleted;
use CraftCms\Cms\Activity\EventTypes\CommentEdited;
use CraftCms\Cms\Activity\EventTypes\CommentEvent;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Markdown\Markdown;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ActivityMentionNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\Mention\Mention;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeIterator;
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
        ?Site $site,
        string $markdown,
    ): ActivityEvent {
        $this->validate($markdown);

        $event = $this->events->record(new CommentCreated(
            subject: $subject,
            actor: $author,
            site: $site,
            markdown: $markdown,
            authorId: $author->id,
            authorLabel: $author->name,
            mentions: $this->resolveMentions($markdown, $subject),
        ));

        $this->scheduleMentionNotifications($event, $event->data['mentions']);

        return $event;
    }

    public function edit(
        ActivityEvent $comment,
        User $author,
        string $markdown,
        ElementInterface $subject,
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

    /**
     * @param  Collection<int|string, User>|null  $mentionedUsers
     */
    public function render(
        ActivityEvent $version,
        User $viewer,
        ?Collection $mentionedUsers = null,
    ): HtmlString {
        $mentions = $this->mentions($version);
        $mentionedUsers ??= $this->mentionedUsers(collect([$version]));
        $html = $this->markdown->transform(
            $version->data['markdown'],
            function (Document $document) use ($mentions, $mentionedUsers, $viewer): void {
                foreach ($this->mentionNodes($document) as $node) {
                    $reference = $node->getIdentifier();
                    $mention = ctype_digit($reference) ? $mentions->get((int) $reference) : null;

                    if ($mention === null) {
                        $node->replaceWith(new Text($node->getLabel() ?? "@$reference"));

                        continue;
                    }

                    $id = (int) $reference;
                    $user = $mentionedUsers->get($id);
                    $canView = $user !== null && Gate::forUser($viewer)->allows('view', $user);
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

    /**
     * @param  Collection<array-key, ActivityEvent>  $versions
     * @return Collection<int|string, User>
     */
    public function mentionedUsers(Collection $versions): Collection
    {
        $ids = $versions
            ->filter(fn (ActivityEvent $version): bool => $version->eventType !== CommentDeleted::class
                && is_a($version->eventType, CommentEvent::class, true))
            ->flatMap(fn (ActivityEvent $version): Collection => $this->mentions($version)->keys())
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return User::find()
            ->id($ids)
            ->status(null)
            ->collect()
            ->keyBy('id');
    }

    public function notificationText(ActivityEvent $version, User $viewer): string
    {
        $html = $this->render($version, $viewer)->toHtml();

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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
                ->eventTypes(CommentCreated::class)
                ->whereNull('rootEventId')
                ->lockForUpdate()
                ->findOrFail($comment->id);
            $current = ActivityEvent::query()
                ->rootEvent($root)
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

            $mentions = $markdown === null
                ? ($current->data['mentions'] ?? [])
                : $this->resolveMentions($markdown, $liveSubject);
            $event = $this->events->record(new $eventType(
                subject: $subject,
                actor: $actor,
                site: $site,
                markdown: $markdown ?? $current->data['markdown'],
                authorId: $root->data['author']['id'],
                authorLabel: $root->data['author']['label'],
                mentions: $mentions,
            ), rootEventId: $root->id);

            if ($markdown !== null) {
                $previousMentionIds = array_column($current->data['mentions'] ?? [], 'id');
                $addedMentions = array_values(array_filter(
                    $mentions,
                    fn (array $mention): bool => ! in_array($mention['id'], $previousMentionIds, true),
                ));

                $this->scheduleMentionNotifications($event, $addedMentions);
            }

            return $event;
        });
    }

    /** @param list<array{id: int, username: string}> $mentions */
    private function scheduleMentionNotifications(ActivityEvent $version, array $mentions): void
    {
        Notification::send(
            UserModel::query()->findMany(array_column($mentions, 'id')),
            new ActivityMentionNotification($version),
        );
    }

    /** @return list<array{id: int, username: string}> */
    private function resolveMentions(string $markdown, ?ElementInterface $subject): array
    {
        $references = [];
        $this->markdown->transform(
            $markdown,
            function (Document $document) use (&$references): void {
                foreach ($this->mentionNodes($document) as $node) {
                    $references[] = $node->getIdentifier();
                }
            },
            Markdown::FLAVOR_GFM_COMMENT,
        );

        if ($references === []) {
            return [];
        }

        $ids = collect($references)
            ->filter(fn (string $id): bool => ctype_digit($id))
            ->map(fn (string $id): int => (int) $id)
            ->unique()
            ->values();
        $users = User::find()
            ->id($ids->all())
            ->status(User::STATUS_ACTIVE)
            ->collect()
            ->keyBy('id');

        return $ids
            ->map(function (int $id) use ($subject, $users): ?array {
                $user = $users->get($id);

                if ($subject === null || $user === null || ! $this->canMention($user, $subject)) {
                    return null;
                }

                return ['id' => $user->id, 'username' => $user->username];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** @return iterable<int, Mention> */
    private function mentionNodes(Document $document): iterable
    {
        foreach (new NodeIterator($document) as $node) {
            if ($node instanceof Mention) {
                yield $node;
            }
        }
    }

    /** @return Collection<int|string, array{id: int, username: string}> */
    private function mentions(ActivityEvent $version): Collection
    {
        $mentions = $version->data['mentions'] ?? [];

        if (! is_array($mentions)) {
            throw new UnexpectedValueException('Activity comment mentions must be an array.');
        }

        return collect($mentions)->keyBy('id');
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
