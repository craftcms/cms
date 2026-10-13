<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\PermissionTree;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\CopyAttribute;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Support\Collection;
use LogicException;
use Mcp\Capability\Registry\ElementReference;
use Mcp\Capability\Registry\ResourceReference;
use Mcp\Capability\Registry\ResourceTemplateReference;
use Mcp\Capability\Registry\ToolReference;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class SettingsUi
{
    public function __construct(
        private CapabilityDiscovery $capabilities,
        private Fields $fields,
        private PublicElementTypes $publicElementTypes,
        private Sections $sections,
        private Sites $sites,
        private UserGroups $userGroups,
        private Volumes $volumes,
    ) {}

    public function make(): Ui
    {
        $endpoint = route('craft.cp.mcp.server');

        return Ui::make([
            Heading::make('mcp-connection-heading', t('Connection'))
                ->description(t('Clients authenticate through Laravel Passport. The authorizing user must have control panel access and the “Use Craft MCP” permission.')),
            Field::make(t('MCP Endpoint'), Text::make('endpoint')->monospace()->mode(ControlMode::ReadOnly))
                ->instructions(t('Use this URL when connecting an authenticated MCP client to Craft.'))
                ->actions(CopyAttribute::make('mcp-endpoint-copy', $endpoint)),
            Separator::make('public-mcp-separator'),
            Heading::make('public-mcp-heading', t('Public MCP'))
                ->description(t('Public MCP is disabled until it is enabled here. Every public capability and content source also requires explicit approval below.')),
            $this->booleanField(
                t('Enable Public MCP'),
                'publicEnabled',
                t('Allow unauthenticated MCP clients to access explicitly approved capabilities and content.'),
            ),
            Field::make(t('Public Endpoint'), Text::make('publicRoute')->monospace())
                ->instructions(t('Enter an app-relative path, such as `/mcp`.'))
                ->required(),
            Separator::make('public-capabilities-separator'),
            Heading::make('public-capabilities-heading', t('Public Capabilities'))
                ->description(t('Approve each capability that unauthenticated MCP clients may discover and execute.')),
            ...$this->capabilityFields(),
            Separator::make('public-content-separator'),
            Heading::make('public-content-heading', t('Public Content'))
                ->description(t('Choose the content that approved public capabilities may expose.')),
            $this->permissionTreeField(t('Sites'), 'publicSiteHandles', $this->sitePermissions()),
            $this->permissionTreeField(t('Sections'), 'publicSectionHandles', $this->sectionPermissions()),
            $this->permissionTreeField(t('Nested Entry Fields'), 'publicNestedEntryFieldHandles', $this->nestedEntryFieldPermissions()),
            $this->permissionTreeField(t('Volumes'), 'publicVolumeHandles', $this->volumePermissions()),
            $this->permissionTreeField(t('Element Types'), 'publicElementTypes', $this->elementTypePermissions()),
            $this->permissionTreeField(t('User Groups'), 'publicUserGroupHandles', $this->userGroupPermissions()),
            Separator::make('element-states-separator'),
            Heading::make('element-states-heading', t('Element States')),
            $this->booleanField(t('Allow Drafts'), 'publicAllowDrafts', t('Allow public queries to include drafts.')),
            $this->booleanField(t('Allow Revisions'), 'publicAllowRevisions', t('Allow public queries to include revisions.')),
            $this->booleanField(t('Allow Inactive Elements'), 'publicAllowInactive', t('Allow public queries to include elements that are not enabled.')),
        ]);
    }

    /** @return list<Field> */
    private function capabilityFields(): array
    {
        $discovered = $this->capabilities->discoverPublic(__DIR__.'/Capabilities', ['.']);
        $categories = [
            [t('Tools'), 'publicTools', $discovered->getTools()],
            [t('Resources'), 'publicResources', $discovered->getResources()],
            [t('Resource Templates'), 'publicResourceTemplates', $discovered->getResourceTemplates()],
            [t('Prompts'), 'publicPrompts', $discovered->getPrompts()],
        ];
        $fields = [];

        foreach ($categories as [$heading, $attribute, $references]) {
            if ($references === []) {
                continue;
            }

            $permissions = array_map(static function (ElementReference $reference): Permission {
                [$identity, $definition] = match (true) {
                    $reference instanceof ToolReference => [$reference->tool->name, $reference->tool],
                    $reference instanceof ResourceReference => [$reference->resource->uri, $reference->resource],
                    $reference instanceof ResourceTemplateReference => [$reference->resourceTemplate->uriTemplate, $reference->resourceTemplate],
                    default => [$reference->prompt->name, $reference->prompt],
                };

                return new Permission($identity, $definition->title ?? $definition->name, $definition->description);
            }, $references);

            $fields[] = $this->permissionTreeField($heading, $attribute, array_values($permissions));
        }

        return $fields;
    }

    /** @param list<Permission> $permissions */
    private function permissionTreeField(string $heading, string $attribute, array $permissions): Field
    {
        return Field::make(
            control: PermissionTree::make($attribute)
                ->ariaLabel($heading)
                ->groups([
                    new PermissionGroup(handle: $attribute, heading: $heading, permissions: collect($permissions)),
                ]),
        );
    }

    private function booleanField(string $label, string $attribute, string $instructions): Field
    {
        return Field::make($label, Lightswitch::make($attribute))
            ->instructions($instructions);
    }

    /** @return list<Permission> */
    private function sitePermissions(): array
    {
        return $this->sites->getAllSites(true)
            ->map(fn ($site): Permission => new Permission($this->requiredHandle($site->handle, 'site'), t('Query elements in the “{name}” site', ['name' => $site->name])))
            ->all();
    }

    /** @return list<Permission> */
    private function sectionPermissions(): array
    {
        return $this->sections->getAllSections()
            ->map(fn ($section): Permission => new Permission($this->requiredHandle($section->handle, 'section'), t('Query entries in the “{name}” section', ['name' => $section->name])))
            ->all();
    }

    /** @return list<Permission> */
    private function nestedEntryFieldPermissions(): array
    {
        return $this->nestedEntryFields()
            ->map(fn (ElementContainerFieldInterface $field): Permission => new Permission($this->requiredHandle($field->handle, 'nested entry field'), t('Query entries in the “{name}” field', ['name' => $field->name])))
            ->all();
    }

    /** @return list<Permission> */
    private function volumePermissions(): array
    {
        return $this->volumes->getAllVolumes()
            ->map(fn ($volume): Permission => new Permission($this->requiredHandle($volume->handle, 'volume'), t('Query assets in the “{name}” volume', ['name' => $volume->name])))
            ->all();
    }

    /** @return list<Permission> */
    private function elementTypePermissions(): array
    {
        return collect($this->publicElementTypes->all())
            ->reject(static fn (string $type): bool => is_a($type, Entry::class, true) || is_a($type, Asset::class, true))
            ->map(fn (string $type): Permission => new Permission(
                $this->publicElementTypes->name($type),
                t('Query {type}', ['type' => t($type::pluralLowerDisplayName(), category: 'site')]),
            ))
            ->values()
            ->all();
    }

    /** @return list<Permission> */
    private function userGroupPermissions(): array
    {
        return $this->userGroups->getAllGroups()
            ->map(fn ($group): Permission => new Permission($this->requiredHandle($group->handle, 'user group'), t('Query users in the “{name}” user group', ['name' => $group->name])))
            ->all();
    }

    private function requiredHandle(?string $handle, string $type): string
    {
        if ($handle === null || $handle === '') {
            throw new LogicException("Public MCP cannot configure a {$type} without a handle.");
        }

        return $handle;
    }

    /** @return Collection<int, ElementContainerFieldInterface> */
    private function nestedEntryFields(): Collection
    {
        return $this->fields->getNestedEntryFieldTypes()
            ->flatMap(fn ($type): array => $this->fields->getFieldsByType($type)->all())
            ->values();
    }
}
