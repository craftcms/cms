<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementSources as ElementSourceTypes;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ConfigRebuilder
{
    /**
     * @param  array<string|int, mixed>  $config
     * @return array<string|int, mixed>
     */
    public function build(array $config): array
    {
        unset($config['meta']);
        $config['dateModified'] = now()->getTimestamp();
        $config['system']['schemaVersion'] = Info::fetch()->schemaVersion;
        $config['addresses'] = $this->fieldLayout(Address::class);
        $config['assetTransformers'] = $this->components(app(AssetTransformers::class)->getAllAssetTransformers());
        $config['entryTypes'] = $this->components(EntryTypes::getAllEntryTypes());
        $config['fields'] = collect(Fields::getAllFields('global'))
            ->mapWithKeys(fn ($field): array => [$field->uid => Fields::createFieldConfig($field)])
            ->all();
        $config['fs'] = collect(Filesystems::getAllFilesystems())
            ->mapWithKeys(fn ($filesystem): array => [$filesystem->handle => Filesystems::createFilesystemConfig($filesystem)])
            ->all();
        $config['imageTransforms'] = $this->components(app(ImageTransforms::class)->getAllTransforms());
        $config['sections'] = $this->components(Sections::getAllSections());
        $config['sites'] = $this->components(Sites::getAllSites(true));
        $config['siteGroups'] = $this->components(SiteGroups::getAllGroups());
        $config['volumes'] = $this->components(Volumes::getAllVolumes());
        $config['users']['groups'] = $this->components(UserGroups::getAllGroups());
        unset($config['users']['fieldLayouts']);
        $config['users'] = array_replace($config['users'], $this->fieldLayout(User::class));

        $token = Gql::getPublicToken();
        $config['graphql'] = [
            'schemas' => $this->components(Gql::getSchemas()),
            'publicToken' => [
                'enabled' => $token->enabled ?? false,
                'expiryDate' => $token?->expiryDate?->getTimestamp(),
            ],
        ];

        $plugins = $config['plugins'] ?? [];
        $config['plugins'] = [];

        foreach (DB::table(Table::PLUGINS)->get(['handle', 'schemaVersion']) as $plugin) {
            $config['plugins'][$plugin->handle] = array_replace($plugins[$plugin->handle] ?? [], ['schemaVersion' => $plugin->schemaVersion]);
        }

        $config['elementSources'] ??= [];

        foreach ($config['elementSources'] as &$sources) {
            foreach ($sources as &$source) {
                if (($source['type'] ?? null) !== ElementSourceTypes::TYPE_CUSTOM || empty($source['condition'])) {
                    continue;
                }

                try {
                    $source['condition'] = Conditions::createCondition($source['condition'])->getConfig();
                } catch (InvalidArgumentException|RuntimeException) {
                    // Preserve conditions whose plugin is currently unavailable.
                }
            }
            unset($source);
        }
        unset($sources);

        return $config;
    }

    /**
     * @param  iterable<object>  $components
     * @return array<string, mixed>
     */
    private function components(iterable $components): array
    {
        return collect($components)->mapWithKeys(fn ($component): array => [$component->uid => $component->getConfig()])->all();
    }

    /** @return array{fieldLayouts?: array<string, mixed>} */
    private function fieldLayout(string $elementType): array
    {
        $layout = Fields::getLayoutByType($elementType, false);
        $config = $layout?->getConfig();

        return $config ? ['fieldLayouts' => [$layout->uid => $config]] : [];
    }
}
