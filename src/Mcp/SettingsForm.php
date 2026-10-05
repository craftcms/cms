<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Controls\PermissionTree;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\Nodes\CopyAttribute;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Heading;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Support\Collection;
use LogicException;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class SettingsForm
{
    public function __construct(
        private Fields $fields,
        private GeneralConfig $generalConfig,
        private PublicElementTypes $publicElementTypes,
        private Sections $sections,
        private Sites $sites,
        private UserGroups $userGroups,
        private Volumes $volumes,
    ) {}

    public function make(): Form
    {
        $endpoint = route('craft.cp.mcp.server');

        return Form::make([
            Heading::make('mcp-connection-heading', t('Connection'))
                ->description($this->authenticationDescription()),
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
            $this->permissionTreeField(t('Tools'), 'publicTools', [
                new Permission('craft-context-get', t('Get the public Craft query context')),
                new Permission('craft-query', t('Query approved public Craft content')),
            ]),
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

    private function authenticationDescription(): string
    {
        if (app()->hasDebugModeEnabled() && ! is_null($this->generalConfig->mcp->debugUserId)) {
            return t('Authentication uses the configured MCP debug user.');
        }

        return t('Clients authenticate through Laravel Passport. The authorizing user must have control panel access and the “Use Craft MCP” permission.');
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
