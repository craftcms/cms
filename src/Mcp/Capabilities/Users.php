<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Field\Fields as FieldService;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\Elements\Adapters\UserAdapter;
use CraftCms\Cms\Mcp\Schema\FieldLayoutConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users as UserService;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * User-specific MCP capabilities. Users are listed and managed through the `elements.*` tools.
 *
 * @since 6.0.0
 */
readonly class Users
{
    public function __construct(
        private Elements $elements,
        private FieldLayoutConfig $fieldLayouts,
        private FieldService $fields,
        private UserAdapter $userAdapter,
        private UserService $users,
    ) {}

    /** @return array{fieldLayout: array<string, mixed>} */
    #[McpTool(
        name: 'users.field-layout.get',
        description: 'Gets the Craft CMS user field layout.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function getFieldLayout(): array
    {
        $fieldLayout = $this->fields->getLayoutByType(User::class);

        if (! $fieldLayout) {
            throw new ToolCallException('User field layout not found.');
        }

        return ['fieldLayout' => $this->fieldLayouts->serialize($fieldLayout)];
    }

    /**
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config. Pass null to clear the layout.
     * @return array{fieldLayout: array<string, mixed>}
     */
    #[McpTool(
        name: 'users.field-layout.update',
        description: 'Updates the Craft CMS user field layout.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function updateFieldLayout(
        #[Schema(definition: FieldLayoutConfig::NullableSchema)]
        ?array $fieldLayout,
    ): array {
        $existing = $this->fields->getLayoutByType(User::class);
        $fieldLayout = $this->fieldLayouts->make($fieldLayout ?? [], User::class, $existing);

        if (! $this->users->saveLayout($fieldLayout)) {
            throw new ToolCallException(implode("\n", $fieldLayout->errors()->all()) ?: 'User field layout could not be saved.');
        }

        return $this->getFieldLayout();
    }

    /** @return array{user: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: ElementResourceLinks::Templates[User::class],
        name: 'craft-users-get',
        title: 'Craft User',
        description: 'A JSON Craft CMS user record addressed by user ID, UID, username, or email.',
        mimeType: 'application/json',
    )]
    #[RequiresPermission('viewUsers')]
    public function resourceByIdentifier(string $user): array
    {
        $criteria = [
            'status' => [User::STATUS_ENABLED, User::STATUS_DISABLED, User::STATUS_ARCHIVED],
            'trashed' => null,
        ];

        $resolved = match (true) {
            ctype_digit($user) => $this->elements->getElementById((int) $user, User::class, criteria: $criteria),
            Str::isUuid($user) => $this->elements->getElementByUid($user, User::class, criteria: $criteria),
            default => $this->users->getUserByUsernameOrEmail($user),
        };

        if (! $resolved instanceof User) {
            throw new ResourceReadException('User not found.');
        }

        return ['user' => $this->userAdapter->serialize($resolved)];
    }
}
