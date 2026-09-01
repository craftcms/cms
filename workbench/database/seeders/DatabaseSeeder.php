<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Env;
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
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
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

        $fieldLayout = null;
        $this->components->task('Creating field layout', function () use (&$fieldLayout) {
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

        $this->createSampleEntries($site->id);
    }

    private function createSampleEntries(int $siteId): void
    {
        $this->components->info('Creating sample entries...');

        $pageType = EntryTypes::getEntryTypeByHandle('page');
        $posts = Sections::getSectionByHandle('posts');
        $pages = Sections::getSectionByHandle('pages');

        $this->components->task('Post entries', function () use ($siteId, $pageType, $posts) {
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
                $this->createEntry($siteId, $posts->id, $pageType->id, $title, now()->subDays($i));
            }

            // One pending and one disabled entry, for testing the status filter
            $this->createEntry($siteId, $posts->id, $pageType->id, 'Scheduled for next week', now()->addWeek());
            $this->createEntry($siteId, $posts->id, $pageType->id, 'Draft ideas (disabled)', now()->subMonth(), enabled: false);
        });

        $this->components->task('Page entries', function () use ($siteId, $pageType, $pages) {
            foreach (['About', 'Contact', 'Pricing'] as $i => $title) {
                $this->createEntry($siteId, $pages->id, $pageType->id, $title, now()->subDays($i + 1));
            }
        });
    }

    private function createEntry(
        int $siteId,
        int $sectionId,
        int $typeId,
        string $title,
        \DateTimeInterface $postDate,
        bool $enabled = true,
    ): void {
        $entry = new Entry;
        $entry->siteId = $siteId;
        $entry->sectionId = $sectionId;
        $entry->typeId = $typeId;
        $entry->title = $title;
        $entry->slug = Str::slug($title);
        $entry->postDate = $postDate;
        $entry->enabled = $enabled;

        Elements::saveElement($entry);
    }

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
