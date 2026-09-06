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
        unset($config[ProjectConfig::PATH_META]);
        $config[ProjectConfig::PATH_DATE_MODIFIED] = now()->getTimestamp();
        $config[ProjectConfig::PATH_SYSTEM]['schemaVersion'] = Info::fetch()->schemaVersion;
        $config[ProjectConfig::PATH_ADDRESSES] = $this->fieldLayout(Address::class);
        $config[ProjectConfig::PATH_ASSET_TRANSFORMERS] = $this->components(app(AssetTransformers::class)->getAllAssetTransformers());
        $config[ProjectConfig::PATH_ENTRY_TYPES] = $this->components(EntryTypes::getAllEntryTypes());
        $config[ProjectConfig::PATH_FIELDS] = collect(Fields::getAllFields('global'))
            ->mapWithKeys(fn ($field): array => [$field->uid => Fields::createFieldConfig($field)])
            ->all();
        $config[ProjectConfig::PATH_FS] = collect(Filesystems::getAllFilesystems())
            ->mapWithKeys(fn ($filesystem): array => [$filesystem->handle => Filesystems::createFilesystemConfig($filesystem)])
            ->all();
        $config[ProjectConfig::PATH_IMAGE_TRANSFORMS] = $this->components(app(ImageTransforms::class)->getAllTransforms());
        $config[ProjectConfig::PATH_SECTIONS] = $this->components(Sections::getAllSections());
        $config[ProjectConfig::PATH_SITES] = $this->components(Sites::getAllSites(true));
        $config[ProjectConfig::PATH_SITE_GROUPS] = $this->components(SiteGroups::getAllGroups());
        $config[ProjectConfig::PATH_VOLUMES] = $this->components(Volumes::getAllVolumes());
        $config[ProjectConfig::PATH_USERS]['groups'] = $this->components(UserGroups::getAllGroups());
        unset($config[ProjectConfig::PATH_USERS]['fieldLayouts']);
        $config[ProjectConfig::PATH_USERS] = array_replace($config[ProjectConfig::PATH_USERS], $this->fieldLayout(User::class));

        $token = Gql::getPublicToken();
        $config[ProjectConfig::PATH_GRAPHQL] = [
            'schemas' => $this->components(Gql::getSchemas()),
            'publicToken' => [
                'enabled' => $token->enabled ?? false,
                'expiryDate' => $token?->expiryDate?->getTimestamp(),
            ],
        ];

        $plugins = $config[ProjectConfig::PATH_PLUGINS] ?? [];
        $config[ProjectConfig::PATH_PLUGINS] = [];

        foreach (DB::table(Table::PLUGINS)->get(['handle', 'schemaVersion']) as $plugin) {
            $config[ProjectConfig::PATH_PLUGINS][$plugin->handle] = array_replace($plugins[$plugin->handle] ?? [], ['schemaVersion' => $plugin->schemaVersion]);
        }

        $config[ProjectConfig::PATH_ELEMENT_SOURCES] ??= [];

        foreach ($config[ProjectConfig::PATH_ELEMENT_SOURCES] as &$sources) {
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
