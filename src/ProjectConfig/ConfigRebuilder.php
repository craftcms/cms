<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementSources as ElementSourceTypes;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use RuntimeException;

/** @internal */
class ConfigRebuilder
{
    public function __construct(
        private readonly AssetTransformers $assetTransformers,
        private readonly ImageTransforms $imageTransforms,
        private readonly Conditions $conditions,
        private readonly EntryTypes $entryTypes,
        private readonly Fields $fields,
        private readonly Filesystems $filesystems,
        private readonly Gql $gql,
        private readonly Sections $sections,
        private readonly SiteGroups $siteGroups,
        private readonly Sites $sites,
        private readonly UserGroups $userGroups,
        private readonly Volumes $volumes,
        private readonly DatabaseManager $database,
    ) {}

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
        $config[ProjectConfig::PATH_ASSET_TRANSFORMERS] = $this->components($this->assetTransformers->getAllAssetTransformers());
        $config[ProjectConfig::PATH_ENTRY_TYPES] = $this->components($this->entryTypes->getAllEntryTypes());
        $config[ProjectConfig::PATH_FIELDS] = collect($this->fields->getAllFields('global'))
            ->mapWithKeys(fn ($field): array => [$field->uid => $this->fields->createFieldConfig($field)])
            ->all();
        $config[ProjectConfig::PATH_FS] = collect($this->filesystems->getAllFilesystems())
            ->mapWithKeys(fn ($filesystem): array => [$filesystem->handle => $this->filesystems->createFilesystemConfig($filesystem)])
            ->all();
        $config[ProjectConfig::PATH_IMAGE_TRANSFORMS] = $this->components($this->imageTransforms->getAllTransforms());
        $config[ProjectConfig::PATH_SECTIONS] = $this->components($this->sections->getAllSections());
        $config[ProjectConfig::PATH_SITES] = $this->components($this->sites->getAllSites(true));
        $config[ProjectConfig::PATH_SITE_GROUPS] = $this->components($this->siteGroups->getAllGroups());
        $config[ProjectConfig::PATH_VOLUMES] = $this->components($this->volumes->getAllVolumes());
        $config[ProjectConfig::PATH_USERS]['groups'] = $this->components($this->userGroups->getAllGroups());
        unset($config[ProjectConfig::PATH_USERS]['fieldLayouts']);
        $config[ProjectConfig::PATH_USERS] = array_replace($config[ProjectConfig::PATH_USERS], $this->fieldLayout(User::class));

        $token = $this->gql->getPublicToken();
        $config[ProjectConfig::PATH_GRAPHQL] = [
            'schemas' => $this->components($this->gql->getSchemas()),
            'publicToken' => [
                'enabled' => $token->enabled ?? false,
                'expiryDate' => $token?->expiryDate?->getTimestamp(),
            ],
        ];

        $plugins = $config[ProjectConfig::PATH_PLUGINS] ?? [];
        $config[ProjectConfig::PATH_PLUGINS] = [];

        foreach ($this->database->table(Table::PLUGINS)->get(['handle', 'schemaVersion']) as $plugin) {
            $config[ProjectConfig::PATH_PLUGINS][$plugin->handle] = array_replace($plugins[$plugin->handle] ?? [], ['schemaVersion' => $plugin->schemaVersion]);
        }

        $config[ProjectConfig::PATH_ELEMENT_SOURCES] ??= [];

        foreach ($config[ProjectConfig::PATH_ELEMENT_SOURCES] as &$sources) {
            foreach ($sources as &$source) {
                if (($source['type'] ?? null) !== ElementSourceTypes::TYPE_CUSTOM || empty($source['condition'])) {
                    continue;
                }

                try {
                    $source['condition'] = $this->conditions->createCondition($source['condition'])->getConfig();
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
        $layout = $this->fields->getLayoutByType($elementType, false);
        $config = $layout?->getConfig();

        return $config ? ['fieldLayouts' => [$layout->uid => $config]] : [];
    }
}
