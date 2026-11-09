<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Adds production-sized content on top of {@see DatabaseSeeder}, so control panel pages are measured against
 * realistic volumes: several sites, many sections, entry types and fields, a large channel, a deep structure, and
 * plenty of users, groups, volumes, assets, routes, transforms and GraphQL schemas.
 *
 * Run it after the workbench has been seeded:
 *
 *     vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\ScaleSeeder' --no-interaction
 *
 * Set `SCALE` to multiply the element counts (default 1). `SCALE=0` seeds no entries, assets or users, only the
 * sites, fields, entry types, sections, groups, volumes and settings, which most control panel costs grow with.
 *
 * It also adds `editor` (password `craftcms2018!!`), who isn't an admin but has every section, volume and site
 * permission, so pages can be measured for non-admins too.
 */
class ScaleSeeder extends Seeder
{
    private float $scale;

    /** @var list<FieldInterface> */
    private array $fields = [];

    public function run(): void
    {
        $scale = getenv('SCALE');
        $this->scale = $scale === false || $scale === '' ? 1.0 : (float) $scale;
        mt_srand(6);
        Edition::set(Edition::Pro);

        $this->step('sites', fn () => $this->sites());
        $this->step('fields', fn () => $this->fields());
        $this->step('sections', fn () => $this->sections());
        $this->step('user groups and users', fn () => $this->users());
        $this->step('editor', fn () => $this->editor());
        $this->step('volumes and assets', fn () => $this->assets());
        $this->step('routes, transforms and GraphQL', fn () => $this->settings());
        $this->step('large channel', fn () => $this->entries('news', $this->count(3000)));
        $this->step('deep structure', fn () => $this->structure('docs', $this->count(600)));
        $this->step('small sections', fn () => $this->smallSections());
    }

    private function step(string $name, callable $callback): void
    {
        $start = microtime(true);
        $callback();
        fwrite(STDOUT, sprintf("%-32s %6.1fs\n", $name, microtime(true) - $start));
    }

    private function count(int $base): int
    {
        return $this->scale > 0 ? max(1, (int) round($base * $this->scale)) : 0;
    }

    private function sites(): void
    {
        $groupId = SiteGroups::getAllGroups()->first()->id;

        foreach (['de' => 'de-DE', 'fr' => 'fr-FR'] as $handle => $language) {
            $site = new Site([
                'groupId' => $groupId,
                'name' => strtoupper($handle).' site',
                'handle' => $handle,
                'language' => $language,
                'baseUrl' => '@web/'.$handle,
                'hasUrls' => true,
            ]);

            Sites::saveSite($site) || throw new RuntimeException("Couldn't save the $handle site.");
        }
    }

    private function fields(): void
    {
        $types = [
            PlainText::class => [],
            Number::class => ['decimals' => 0],
            Lightswitch::class => [],
            Dropdown::class => ['options' => [
                ['label' => 'Red', 'value' => 'red', 'default' => true],
                ['label' => 'Green', 'value' => 'green'],
            ]],
            Entries::class => ['sources' => '*', 'maxRelations' => 5],
        ];
        $typeList = array_keys($types);

        for ($i = 1; $i <= 200; $i++) {
            $type = $typeList[$i % count($typeList)];
            $field = Fields::createField([
                'type' => $type,
                'name' => "Scale field $i",
                'handle' => "scaleField$i",
                'searchable' => $type === PlainText::class,
                'settings' => $types[$type],
            ]);

            Fields::saveField($field) || throw new RuntimeException("Couldn't save field $i.");
            $this->fields[] = $field;
        }

        Fields::refreshFields();
    }

    private function sections(): void
    {
        $sites = Sites::getAllSites();
        $entryTypes = [];

        for ($i = 1; $i <= 80; $i++) {
            $fields = array_slice($this->fields, ($i * 7) % 190, 10);
            $layout = FieldLayout::create([
                'uid' => Str::uuid()->toString(),
                'type' => Entry::class,
                'config' => ['tabs' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'name' => 'Content',
                        'elements' => [
                            ['uid' => Str::uuid()->toString(), 'type' => EntryTitleField::class, 'required' => true],
                            ...array_map(fn (FieldInterface $field) => [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $field->uid,
                            ], array_slice($fields, 0, 6)),
                        ],
                    ],
                    [
                        'uid' => Str::uuid()->toString(),
                        'name' => 'More',
                        'elements' => array_map(fn (FieldInterface $field) => [
                            'uid' => Str::uuid()->toString(),
                            'type' => CustomField::class,
                            'fieldUid' => $field->uid,
                        ], array_slice($fields, 6)),
                    ],
                ]],
            ]);

            $entryType = new EntryType([
                'fieldLayoutId' => $layout->id,
                'name' => "Scale type $i",
                'handle' => "scaleType$i",
            ]);
            EntryTypes::saveEntryType($entryType) || throw new RuntimeException("Couldn't save entry type $i.");
            $entryTypes[] = $entryType;
        }

        Fields::refreshFields();

        $sections = [
            ['news', 'News', SectionType::Channel],
            ['docs', 'Docs', SectionType::Structure],
        ];

        for ($i = 1; $i <= 48; $i++) {
            $sections[] = ["scaleSection$i", "Scale section $i", match (true) {
                $i % 8 === 0 => SectionType::Single,
                $i % 5 === 0 => SectionType::Structure,
                default => SectionType::Channel,
            }];
        }

        foreach ($sections as $i => [$handle, $name, $type]) {
            $section = new Section([
                'name' => $name,
                'handle' => $handle,
                'type' => $type,
                'siteSettings' => $sites->mapWithKeys(fn (Site $site) => [$site->id => new SectionSiteSettings([
                    'siteId' => $site->id,
                    'hasUrls' => true,
                    'uriFormat' => $type === SectionType::Single ? $handle : "$handle/{slug}",
                    'template' => null,
                ])])->all(),
                'entryTypes' => array_slice($entryTypes, ($i * 3) % 78, $type === SectionType::Single ? 1 : 3),
            ]);

            Sections::saveSection($section) || throw new RuntimeException("Couldn't save the $handle section.");
        }
    }

    private function users(): void
    {
        $groups = [];

        for ($i = 1; $i <= 30; $i++) {
            $group = new UserGroup(['name' => "Scale group $i", 'handle' => "scaleGroup$i"]);
            UserGroups::saveGroup($group) || throw new RuntimeException("Couldn't save user group $i.");
            $groups[] = $group->id;
        }

        for ($i = 1; $i <= $this->count(1000); $i++) {
            $user = UserFactory::new()->create([
                'username' => "scaleuser$i",
                'email' => "scaleuser$i@example.com",
                'fullName' => "Scale User $i",
            ]);

            User::query()->find($user->id)->userGroups()->sync([$groups[$i % 30], $groups[($i * 7) % 30]]);
        }
    }

    private function editor(): void
    {
        $editor = UserFactory::new()->createOne([
            'username' => 'editor',
            'email' => 'editor@example.com',
            'fullName' => 'Editor',
            'admin' => false,
            'password' => Hash::make('craftcms2018!!'),
        ]);

        $permissions = ['accessCp', 'viewUsers', 'editUsers', 'utility:system-report'];

        foreach (Sections::getAllSections() as $section) {
            foreach (['viewEntries', 'createEntries', 'saveEntries', 'deleteEntries', 'viewPeerEntries', 'savePeerEntries', 'viewPeerEntryDrafts'] as $permission) {
                $permissions[] = "$permission:$section->uid";
            }
        }

        foreach (Volumes::getAllVolumes() as $volume) {
            foreach (['viewAssets', 'saveAssets', 'replaceFiles'] as $permission) {
                $permissions[] = "$permission:$volume->uid";
            }
        }

        foreach (Sites::getAllSites() as $site) {
            $permissions[] = "editSite:$site->uid";
        }

        app(UserPermissions::class)->saveUserPermissions($editor->getKey(), $permissions)
            || throw new RuntimeException("Couldn't save the editor's permissions.");
    }

    private function assets(): void
    {
        $folders = app(Folders::class);

        for ($v = 1; $v <= 8; $v++) {
            $volume = new Volume([
                'name' => "Scale volume $v",
                'handle' => "scaleVolume$v",
                'fsHandle' => 'assets',
                'hasUrls' => true,
                'subpath' => "scale-$v",
                'assetTransformer' => 'craft',
            ]);
            Volumes::saveVolume($volume) || throw new RuntimeException("Couldn't save volume $v.");
        }

        $images = Volumes::getVolumeByHandle('images');
        $folderId = $folders->getRootFolderByVolumeId($images->id)->id;
        $tmp = sys_get_temp_dir().'/craft-scale-assets';
        @mkdir($tmp);

        for ($i = 1; $i <= $this->count(800); $i++) {
            $width = [320, 480, 640, 800][$i % 4];
            $height = [240, 360, 480, 600][($i * 3) % 4];
            $image = imagecreatetruecolor($width, $height);
            imagefill($image, 0, 0, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
            imagefilledellipse($image, mt_rand(0, $width), mt_rand(0, $height), 200, 200, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
            $path = "$tmp/scale-$i.jpg";
            imagejpeg($image, $path, 70);

            $asset = new Asset([
                'volumeId' => $images->id,
                'newFolderId' => $folderId,
                'tempFilePath' => $path,
                'newFilename' => "scale-$i.jpg",
                'title' => "Scale image $i",
            ]);
            $asset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);
            Elements::saveElement($asset) || throw new RuntimeException("Couldn't save asset $i.");
        }
    }

    private function settings(): void
    {
        for ($i = 1; $i <= 40; $i++) {
            app(Routes::class)->saveRoute(new Route(["scale-route-$i/", ['slug', '[^\/]+']], "_scale/route-$i"));
        }

        for ($i = 1; $i <= 30; $i++) {
            app(ImageTransforms::class)->saveTransform(new ImageTransform([
                'name' => "Scale transform $i",
                'handle' => "scaleTransform$i",
                'width' => 100 + $i * 20,
                'height' => 80 + $i * 10,
            ])) || throw new RuntimeException("Couldn't save transform $i.");
        }

        $gql = app(Gql::class);

        for ($i = 1; $i <= 20; $i++) {
            $schema = new GqlSchema(['name' => "Scale schema $i", 'scope' => ['sections.*:read', 'volumes.*:read']]);
            $gql->saveSchema($schema) || throw new RuntimeException("Couldn't save schema $i.");
            $gql->saveToken(new GqlToken([
                'name' => "Scale token $i",
                'schemaId' => $schema->id,
                'accessToken' => Str::random(32),
            ])) || throw new RuntimeException("Couldn't save token $i.");
        }
    }

    /** @return list<int> */
    private function entries(string $sectionHandle, int $count): array
    {
        $section = Sections::getSectionByHandle($sectionHandle);
        $types = $section->getEntryTypes();
        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $ids[] = $this->saveEntry($section, $types[$i % count($types)], "$section->name item $i", $i, $ids)->id;
        }

        return $ids;
    }

    private function structure(string $sectionHandle, int $count): void
    {
        $section = Sections::getSectionByHandle($sectionHandle);
        $types = $section->getEntryTypes();
        $parents = [];

        for ($i = 1; $i <= $count; $i++) {
            $entry = new Entry;
            $parent = $i <= 20 ? null : $parents[mt_rand(0, count($parents) - 1)];

            if ($parent) {
                $entry->setParentId($parent);
            }

            $saved = $this->saveEntry($section, $types[$i % count($types)], "Docs page $i", $i, [], $entry);

            if (count($parents) < 200) {
                $parents[] = $saved->id;
            }
        }
    }

    private function smallSections(): void
    {
        foreach (Sections::getAllSections() as $section) {
            if (str_starts_with($section->handle, 'scaleSection') && $section->type !== SectionType::Single) {
                $this->entries($section->handle, $this->count(25));
            }
        }
    }

    /** @param list<int> $related */
    private function saveEntry(Section $section, EntryType $type, string $title, int $i, array $related, ?Entry $entry = null): Entry
    {
        $entry ??= new Entry;
        $entry->sectionId = $section->id;
        $entry->typeId = $type->id;
        $entry->title = $title;
        $entry->slug = Str::slug($title);
        $entry->postDate = now()->subMinutes($i * 37);
        $entry->enabled = $i % 13 !== 0;

        foreach ($type->getFieldLayout()->getCustomFields() as $field) {
            $value = match ($field::class) {
                PlainText::class => "Value $i for $field->name",
                Number::class => $i % 1000,
                Lightswitch::class => $i % 2 === 0,
                Dropdown::class => $i % 3 === 0 ? 'green' : 'red',
                Entries::class => $related ? array_slice($related, -min(3, count($related))) : [],
                default => null,
            };

            if ($value !== null) {
                $entry->setFieldValue($field->handle, $value);
            }
        }

        Elements::saveElement($entry) || throw new RuntimeException("Couldn't save $title.");

        return $entry;
    }
}
