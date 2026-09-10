<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Activity\ActivityComments;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\DraftApplied;
use CraftCms\Cms\Activity\EventTypes\DraftCreated;
use CraftCms\Cms\Activity\EventTypes\DraftDiscarded;
use CraftCms\Cms\Activity\EventTypes\DraftSaved;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementRestored;
use CraftCms\Cms\Activity\EventTypes\ElementStatusChanged;
use CraftCms\Cms\Activity\EventTypes\ElementTrashed;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\EventTypes\RevisionRestored;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Cp\Data\NotificationButtonData;
use CraftCms\Cms\Cp\Enums\ButtonVariant;
use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\Plugins;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class DatabaseSeeder extends Seeder
{
    use InteractsWithIO;

    public function __construct()
    {
        $this->input = new ArrayInput([]);
        $this->output = new OutputStyle($this->input, new ConsoleOutput);
        $this->components = new Factory($this->output);
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Context::forgetHidden('craft.info');
        Context::forgetHidden('craft.isInstalled');

        File::cleanDirectory(config_path('craft/project'));
        File::cleanDirectory(storage_path('runtime/compiled_classes'));

        Cache::lock(ProjectConfig::MUTEX_NAME)->forceRelease();

        $site = new Site([
            'name' => 'Craft test site',
            'handle' => 'defaultSite',
            'language' => 'en-US',
            'baseUrl' => config('app.url'),
            'primary' => true,
            'hasUrls' => true,
        ]);

        new Install(
            username: 'craftcms',
            password: 'craftcms2018!!',
            email: 'support@craftcms.com',
            site: $site,
        )->up();

        Edition::set(Edition::Pro);

        app(LaravelMigrations::class)->ensureSessionsTable();

        $this->components->task('Installing test plugin', fn () => Plugins::installPlugin('test-plugin'));

        $this->components->task('Creating notifications', function (): void {
            $user = User::query()->firstOrFail();

            $user->notify(new CpNotification('The workbench is ready for testing.')
                ->kind('announcement')
                ->title('Welcome to Craft 6')
                ->byline('Craft CMS')
                ->icon('custom-icons/craft-cms'));
            $user->notifications()->firstOrFail()->markAsRead();

            $user->notify(new CpNotification('This announcement demonstrates **Markdown** formatting.')
                ->kind('announcement')
                ->title('Unread Craft announcement')
                ->byline('Craft CMS')
                ->icon('custom-icons/craft-cms'));
            $user->notify(new CpNotification('This announcement belongs to the Test Plugin.')
                ->kind('announcement')
                ->title('Unread plugin announcement')
                ->byline('Test Plugin'));
            $user->notify(new CpNotification('Review the **release notes** before updating.')
                ->title('Craft 6.1 is available')
                ->byline('Updates')
                ->icon('arrows-rotate')
                ->url('/admin/utilities/updates')
                ->buttons([
                    new NotificationButtonData('View updates', '/admin/utilities/updates', variant: ButtonVariant::Primary),
                ]));
            $user->notify(new CpNotification('Rias mentioned you in an entry. This sample has multiple actions.')
                ->title('New mention')
                ->byline('Editorial team')
                ->image(url: 'https://i.pravatar.cc/80?img=12', alt: 'Rias')
                ->url('/admin/entries')
                ->buttons([
                    new NotificationButtonData('Open entry', '/admin/entries', variant: ButtonVariant::Primary),
                    new NotificationButtonData('View profile', '/admin/myaccount'),
                ]));
            $user->notifications()->latest()->firstOrFail()->markAsRead();
        });

        $this->components->task('Creating assets filesystem', function (): void {
            $_SERVER['DOCUMENT_ROOT'] = Env::get('DOCUMENT_ROOT') ?: public_path();

            $filesystem = Filesystems::createFilesystem([
                'type' => Local::class,
                'name' => 'Assets',
                'handle' => 'assets',
                'settings' => [
                    'path' => '$DOCUMENT_ROOT/assets',
                    'hasUrls' => true,
                    'url' => '/assets',
                ],
            ]);

            if (! Filesystems::saveFilesystem($filesystem)) {
                throw new RuntimeException('Failed to create the assets filesystem.');
            }
        });

        $this->components->task('Creating asset volumes', function (): void {
            foreach (['images' => 'Images', 'documents' => 'Documents'] as $handle => $name) {
                $volume = new Volume([
                    'name' => $name,
                    'handle' => $handle,
                    'fsHandle' => 'assets',
                    'subpath' => $handle,
                    'assetTransformer' => 'craft',
                ]);

                if (! Volumes::saveVolume($volume)) {
                    throw new RuntimeException("Failed to create the {$name} volume.");
                }
            }
        });

        $site = Sites::getCurrentSite();

        $this->components->info('Creating default entry types & sections...');

        $markdownField = null;
        $this->components->task('Creating Markdown field', function () use (&$markdownField): void {
            $markdownField = Fields::createField([
                'type' => Markdown::class,
                'name' => 'Body',
                'handle' => 'body',
                'searchable' => true,
                'settings' => [
                    'initialRows' => 12,
                    'showStats' => true,
                ],
            ]);

            if (! Fields::saveField($markdownField)) {
                throw new RuntimeException('Failed to create the Markdown field.');
            }
        });

        Fields::refreshFields();

        $fieldLayout = null;
        $this->components->task('Creating field layout', function () use (&$fieldLayout, $markdownField) {
            $fieldLayout = FieldLayout::create([
                'uid' => Str::uuid()->toString(),
                'type' => Entry::class,
                'config' => [
                    'tabs' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'name' => 'Content',
                            'elements' => [
                                [
                                    'uid' => Str::uuid()->toString(),
                                    'type' => EntryTitleField::class,
                                    'required' => true,
                                ],
                                [
                                    'uid' => Str::uuid()->toString(),
                                    'type' => CustomField::class,
                                    'fieldUid' => $markdownField->uid,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        });

        Fields::refreshFields();

        $pageType = null;
        $this->components->task('Page entry type', function () use ($fieldLayout, &$pageType) {
            EntryTypes::saveEntryType($pageType = new EntryType([
                'fieldLayoutId' => $fieldLayout->id,
                'name' => 'Page',
                'handle' => 'page',
            ]));
        });

        $this->createSection($site, 'Home', SectionType::Single, '__HOME__', [$pageType]);
        $this->createSection($site, 'Pages', SectionType::Structure, '{parent.uri}/{slug}', [$pageType]);
        $this->createSection($site, 'Posts', SectionType::Channel, 'blog/{slug}', [$pageType]);

        $this->createSampleEntries($site);
    }

    private function createSampleEntries(Site $site): void
    {
        $this->components->info('Creating sample entries...');

        $pageType = EntryTypes::getEntryTypeByHandle('page');
        $posts = Sections::getSectionByHandle('posts');
        $pages = Sections::getSectionByHandle('pages');

        $this->components->task('Post entries', function () use ($site, $pageType, $posts) {
            $titles = [
                'Welcome to Craft 6',
                'Porting the element index to Vue',
                'Inertia in the control panel',
                'Structures, sections & sources',
                'Pagination without page reloads',
                'Searching every entry',
                'Sorting by any column',
            ];

            foreach ($titles as $i => $title) {
                $this->createEntry($site->id, $posts->id, $pageType->id, $title, now()->subDays($i));
            }

            // One pending and one disabled entry, for testing the status filter
            $this->createEntry($site->id, $posts->id, $pageType->id, 'Scheduled for next week', now()->addWeek());
            $this->createEntry($site->id, $posts->id, $pageType->id, 'Draft ideas (disabled)', now()->subMonth(), enabled: false);
        });

        $this->components->task('Page entries', function () use ($site, $pageType, $pages) {
            foreach (['About', 'Contact', 'Pricing'] as $i => $title) {
                $this->createEntry($site->id, $pages->id, $pageType->id, $title, now()->subDays($i + 1));
            }

            $activityEntry = $this->createEntry(
                $site->id,
                $pages->id,
                $pageType->id,
                'Activity timeline',
                now()->subDays(4),
                body: <<<'MARKDOWN'
## Activity timeline playground

This entry contains representative seeded activity for Craft’s core event types.

- Field and status changes
- Draft and revision activity
- New, edited, and removed comments
- An accidental deletion and recovery
MARKDOWN,
            );

            $this->seedActivity($activityEntry, $site);
        });
    }

    private function createEntry(
        int $siteId,
        int $sectionId,
        int $typeId,
        string $title,
        \DateTimeInterface $postDate,
        bool $enabled = true,
        ?string $body = null,
    ): Entry {
        $entry = new Entry;
        $entry->siteId = $siteId;
        $entry->sectionId = $sectionId;
        $entry->typeId = $typeId;
        $entry->title = $title;
        $entry->slug = Str::slug($title);
        $entry->postDate = $postDate;
        $entry->enabled = $enabled;
        $entry->setFieldValue('body', $body ?? "This is seeded Markdown content for **{$title}**.");

        if (! Elements::saveElement($entry)) {
            throw new RuntimeException("Failed to create the {$title} entry.");
        }

        return $entry;
    }

    private function seedActivity(Entry $entry, Site $site): void
    {
        $user = User::query()->firstOrFail();
        $editor = UserFactory::new()->createElement([
            'fullName' => 'Ada Lovelace',
            'username' => 'ada',
            'email' => 'ada@example.com',
        ]);
        $userElement = $user->asElement();
        $comments = app(ActivityComments::class);
        $impersonation = app(Impersonation::class);
        $bodyField = $entry->getFieldLayout()?->getFieldByHandle('body')
            ?? throw new RuntimeException('Failed to find the Body field.');
        $subject = ActivitySubject::fromElement($entry);
        ActivityEvent::query()->subject($subject)->delete();
        $startedAt = now()->subDays(7);
        $events = [
            [0, new ElementCreated(
                subject: new ActivitySubject(
                    type: $subject->type,
                    id: $subject->id,
                    label: 'Activity log',
                ),
                actor: $user,
                site: $site,
            )],
            [2, new ElementUpdated(
                subject: $entry,
                actor: $user,
                site: $site,
                changes: [
                    new ActivityChange('Title', 'Activity log', 'Activity timeline'),
                    new ActivityChange($bodyField->name, null, 'Added an overview of the activity timeline.'),
                ],
            )],
            [4, new ElementStatusChanged(
                subject: $entry,
                site: $site,
                oldStatus: 'disabled',
                newStatus: 'live',
            )],
            [24, new DraftCreated(subject: $entry, actor: $user, site: $site)],
            [25, new DraftSaved(
                subject: $entry,
                actor: $user,
                site: $site,
                changes: [new ActivityChange(
                    $bodyField->name,
                    'Added an overview of the activity timeline.',
                    'Added draft and revision examples.',
                )],
            )],
            [31, new DraftApplied(subject: $entry, actor: $user, site: $site)],
            [72, new ElementUpdated(
                subject: $entry,
                actor: $user,
                site: $site,
                changes: [
                    new ActivityChange('Title', 'Activity timeline', 'Activity timeline overview'),
                    new ActivityChange('Slug', 'activity-timeline', 'activity-timeline-overview'),
                    new ActivityChange(
                        'Post Date',
                        $startedAt->copy()->addDays(3)->toDateString(),
                        $startedAt->copy()->addDays(4)->toDateString(),
                    ),
                    new ActivityChange(
                        $bodyField->name,
                        'Added draft and revision examples.',
                        'Added comment and recovery examples.',
                    ),
                ],
            )],
            [96, new RevisionRestored(subject: $entry, site: $site, revisionNum: 2)],
            [120, new DraftCreated(subject: $entry, actor: $user, site: $site)],
            [121, new DraftSaved(
                subject: $entry,
                actor: $user,
                site: $site,
                changes: [new ActivityChange($bodyField->name, 'Draft and revision examples', 'Alternative introduction')],
            )],
            [123, new DraftDiscarded(subject: $entry, actor: $user, site: $site)],
            [144, new ElementTrashed(subject: $entry, actor: $user, site: $site)],
            [145, new ElementRestored(subject: $entry, actor: $user, site: $site)],
        ];

        try {
            foreach ($events as [$hours, $event]) {
                Date::setTestNow($startedAt->copy()->addHours($hours));
                Activities::record($event);
            }

            Date::setTestNow($startedAt->copy()->addHours(12));
            $comments->create($entry, $userElement, $site, 'Could we include examples of draft and revision activity?');

            Date::setTestNow($startedAt->copy()->addHours(54));
            $editedComment = $comments->create($entry, $userElement, $site, 'Could we make the change summary easier to scan?');

            Date::setTestNow($startedAt->copy()->addHours(55));
            $comments->edit($editedComment, $userElement, 'Could we keep short change summaries expanded?', $entry);

            Date::setTestNow($startedAt->copy()->addHours(132));
            $removedComment = $comments->create($entry, $userElement, $site, 'This note is no longer relevant.');

            Date::setTestNow($startedAt->copy()->addHours(133));
            $comments->delete($removedComment, $userElement);

            Date::setTestNow($startedAt->copy()->addHours(146));
            $impersonation->setImpersonatorId($user->id);
            Activities::record(new ElementUpdated(
                subject: $entry,
                actor: $editor,
                site: $site,
                changes: [new ActivityChange('Title', 'Activity timeline overview', 'Activity timeline')],
            ));
        } finally {
            $impersonation->setImpersonatorId(null);
            Date::setTestNow();
        }
    }

    /** @param list<EntryType> $entryTypes */
    public function createSection(Site $site, string $title, SectionType $sectionType = SectionType::Channel, ?string $uriFormat = null, array $entryTypes = []): ?Section
    {
        $section = null;

        $this->components->task("{$title} section ({$sectionType->label()})", function () use ($uriFormat, $entryTypes, $sectionType, $title, $site, &$section) {
            Sections::saveSection($section = new Section([
                'name' => $title,
                'handle' => Str::slug($title),
                'type' => $sectionType,
                'siteSettings' => [
                    $site->id => new SectionSiteSettings([
                        'siteId' => $site->id,
                        'hasUrls' => ! is_null($uriFormat),
                        'uriFormat' => $uriFormat,
                    ]),
                ],
                'entryTypes' => $entryTypes,
            ]));
        });

        return $section;
    }
}
