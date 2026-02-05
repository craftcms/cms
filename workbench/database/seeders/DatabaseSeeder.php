<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use craft\fieldlayoutelements\entries\EntryTitleField;
use CraftCms\Cms\Database\Migrations\Install;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class DatabaseSeeder extends Seeder
{
    use InteractsWithIO;
    use WithoutModelEvents;

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

        $site = new Site(
            name: 'Craft test site',
            handle: 'defaultSite',
            language: 'en-US',
            baseUrl: config('app.url'),
            primary: true,
            hasUrls: true,
        );

        new Install(
            username: 'craftcms',
            password: 'craftcms2018!!',
            email: 'support@craftcms.com',
            site: $site,
        )->up();

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
            EntryTypes::saveEntryType($pageType = new EntryType(
                fieldLayoutId: $fieldLayout->id,
                name: 'Page',
                handle: 'page',
            ));
        });

        $this->createSection($site, 'Home', SectionType::Single, '__HOME__', [$pageType]);
        $this->createSection($site, 'Pages', SectionType::Structure, '{parent.uri}/{slug}', [$pageType]);
        $this->createSection($site, 'Posts', SectionType::Channel, 'blog/{slug}', [$pageType]);
    }

    public function createSection(Site $site, string $title, SectionType $sectionType = SectionType::Channel, ?string $uriFormat = null, array $entryTypes = []): ?Section
    {
        $section = null;

        $this->components->task("{$title} section ({$sectionType->label()})", function () use ($uriFormat, $entryTypes, $sectionType, $title, $site, &$section) {
            Sections::saveSection($section = new Section(
                name: $title,
                handle: Str::slug($title),
                type: $sectionType,
                siteSettings: [
                    $site->id => new SectionSiteSettings(
                        siteId: $site->id,
                        hasUrls: ! is_null($uriFormat),
                        uriFormat: $uriFormat,
                    ),
                ],
                entryTypes: $entryTypes,
            ));
        });

        return $section;
    }
}
