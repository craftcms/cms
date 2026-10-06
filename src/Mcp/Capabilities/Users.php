<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Field\Fields as FieldService;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\CustomFieldSchema;
use CraftCms\Cms\Mcp\Schema\FieldLayoutConfig;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users as UserService;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Users
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            ...ElementQueryCriteria::SchemaProperties,
            ...ElementQueryCriteria::UserSchemaProperties,
        ],
        'additionalProperties' => true,
    ];

    private const array CreateAttributesSchema = [
        'type' => 'object',
        'properties' => [
            'email' => ['type' => 'string', 'format' => 'email', 'description' => 'User email address.'],
            'username' => ['type' => 'string', 'description' => 'Username.'],
            'firstName' => ['type' => 'string', 'description' => 'First name.'],
            'lastName' => ['type' => 'string', 'description' => 'Last name.'],
            'fullName' => ['type' => 'string', 'description' => 'Full name.'],
            'newPassword' => ['type' => 'string', 'description' => 'New password.'],
            'active' => ['type' => 'boolean', 'description' => 'Whether the user is active.'],
            'pending' => ['type' => 'boolean', 'description' => 'Whether the user is pending activation.'],
            'admin' => ['type' => 'boolean', 'description' => 'Whether the user is an admin.'],
            'affiliatedSiteId' => ['type' => ['integer', 'null'], 'description' => 'Affiliated site ID.'],
            'photoId' => ['type' => ['integer', 'null'], 'description' => 'Photo asset ID.'],
            'hasDashboard' => ['type' => 'boolean', 'description' => 'Whether the user has a dashboard.'],
        ],
        'additionalProperties' => false,
    ];

    private const array UpdateAttributesSchema = [
        'type' => 'object',
        'properties' => [
            'email' => ['type' => 'string', 'format' => 'email', 'description' => 'User email address.'],
            'username' => ['type' => 'string', 'description' => 'Username.'],
            'firstName' => ['type' => 'string', 'description' => 'First name.'],
            'lastName' => ['type' => 'string', 'description' => 'Last name.'],
            'fullName' => ['type' => 'string', 'description' => 'Full name.'],
            'newPassword' => ['type' => 'string', 'description' => 'New password.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the user is enabled.'],
            'admin' => ['type' => 'boolean', 'description' => 'Whether the user is an admin.'],
        ],
        'additionalProperties' => false,
    ];

    private const array FieldsSchema = [
        'type' => 'object',
        'description' => 'Custom field values keyed by field handle. Use users.field-schema for the applicable schema.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private McpActor $actor,
        private CustomFieldSchema $customFieldSchema,
        private ElementSerializer $elementSerializer,
        private Elements $elements,
        private ElementQueryCriteria $elementQueryCriteria,
        private ElementResourceLinks $resourceLinks,
        private FieldLayoutConfig $fieldLayouts,
        private FieldService $fields,
        private UserInitiatedElementSave $userInitiatedElementSave,
        private UserService $users,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria  Native Craft UserQuery criteria. Custom field criteria may be passed by field handle.
     * @param  list<string>|null  $fields
     */
    #[McpTool(
        name: 'users.list',
        description: 'Lists Craft CMS users.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('viewUsers')]
    public function list(
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = [],
    ): CallToolResult {
        $query = User::find()
            ->status(null)
            ->orderBy('elements.id');

        $criteria = $this->elementQueryCriteria->apply($query, $criteria);
        $users = $query->all();

        return $this->resourceLinks->result([
            'count' => count($users),
            'limit' => $criteria['limit'],
            'offset' => $criteria['offset'],
            'users' => array_map(fn (User $user): array => $this->serializeSummary($user, $fields), $users),
        ], $users);
    }

    /**
     * @param  int|null  $id  User ID.
     * @param  string|null  $uid  User UID.
     * @param  string|null  $username  Username.
     * @param  string|null  $email  User email address.
     * @param  list<string>|null  $fields
     * @return array{user: array<string, mixed>}
     */
    #[McpTool(
        name: 'users.get',
        description: 'Gets a Craft CMS user by ID, UID, username, or email.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('viewUsers')]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $username = null,
        #[Schema(format: 'email')]
        ?string $email = null,
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = null,
    ): array {
        $user = $this->find($id, $uid, $username, $email);

        if (! $user) {
            throw new ToolCallException('User not found.');
        }

        return ['user' => $this->serialize($user, $fields)];
    }

    /**
     * Returns the writable custom-field schema for an existing user or a new user when no identifier is provided.
     *
     * @return array{schema: array<string, mixed>}
     */
    #[McpTool(
        name: 'users.field-schema',
        description: 'Gets the writable custom-field JSON Schema for an existing or new user.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function fieldSchema(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
    ): array {
        $user = $id !== null || $uid !== null
            ? $this->find($id, $uid)
            : new User;

        if (! $user) {
            throw new ToolCallException('User not found.');
        }

        $this->authorizeSave($this->actor->user(), $user);

        return ['schema' => $this->customFieldSchema->forElement($user)];
    }

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

    /**
     * @param  array<string, mixed>  $attributes  Built-in user attributes.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{user: array<string, mixed>}
     */
    #[McpTool(name: 'users.create', description: 'Creates a Craft CMS user. Use users.field-schema to discover custom fields.')]
    #[RequiresPermission('registerUsers')]
    public function create(
        #[Schema(definition: self::CreateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $actor = $this->actor->user();
        $user = new User;

        $this->authorizeSave($actor, $user);
        $this->authorizeAttributes($actor, $attributes, true);
        $this->populate($user, $attributes, $fields);
        $this->authorizeSave($actor, $user);

        return ['user' => $this->save($user, $actor)];
    }

    /**
     * @param  int|null  $id  User ID.
     * @param  string|null  $uid  User UID.
     * @param  array<string, mixed>  $attributes  Built-in user attributes to update.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{user: array<string, mixed>}
     */
    #[McpTool(
        name: 'users.update',
        description: 'Updates a Craft CMS user. Use users.field-schema to discover custom fields.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('editUsers')]
    public function update(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        #[Schema(definition: self::UpdateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $user = $this->find($id, $uid);

        if (! $user) {
            throw new ToolCallException('User not found.');
        }

        $actor = $this->actor->user();

        $this->authorizeSave($actor, $user);
        $this->authorizeAttributes($actor, $attributes);
        $this->populate($user, $attributes, $fields);
        $this->authorizeSave($actor, $user);

        return ['user' => $this->save($user, $actor)];
    }

    /**
     * @param  int|null  $id  User ID.
     * @param  string|null  $uid  User UID.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'users.delete',
        description: 'Deletes a Craft CMS user.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresPermission('deleteUsers')]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        bool $hardDelete = false,
    ): array {
        $user = $this->find($id, $uid);

        if (! $user) {
            throw new ToolCallException('User not found.');
        }

        if (! Gate::forUser($this->actor->user())->allows('delete', $user)) {
            throw new ToolCallException('You are not authorized to delete this user.');
        }

        if (! $this->elements->deleteElement($user, $hardDelete)) {
            throw new ToolCallException('User could not be deleted.');
        }

        return ['deleted' => true];
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
            default => $this->find(username: $user),
        };

        if (! $resolved) {
            throw new ResourceReadException('User not found.');
        }

        return ['user' => $this->serialize($resolved)];
    }

    private function find(
        ?int $id = null,
        ?string $uid = null,
        ?string $username = null,
        ?string $email = null,
    ): ?User {
        if (count(Arr::whereNotNull([$id, $uid, $username, $email])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, username, email.');
        }

        return match (true) {
            $id !== null => $this->users->getUserById($id),
            $uid !== null => $this->users->getUserByUid($uid),
            $username !== null => $this->users->getUserByUsernameOrEmail($username),
            $email !== null => $this->users->getUserByUsernameOrEmail($email),
            default => null,
        };
    }

    private function authorizeSave(CraftUser $actor, User $user): void
    {
        if (! Gate::forUser($actor)->allows('save', $user)) {
            throw new ToolCallException('You are not authorized to save this user.');
        }
    }

    /** @param array<string, mixed> $attributes */
    private function authorizeAttributes(CraftUser $actor, array $attributes, bool $new = false): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        $allowed = [
            'affiliatedSiteId',
            'firstName',
            'fullName',
            'hasDashboard',
            'lastName',
            'photoId',
        ];

        if ($new) {
            $allowed = [...$allowed, 'email', 'username'];
        }

        if ($actor->can('administrateUsers')) {
            $allowed[] = 'email';
        }

        $restricted = array_values(array_diff(array_keys($attributes), $allowed));

        if ($restricted !== []) {
            throw new ToolCallException(sprintf(
                'You are not authorized to set restricted user attributes: %s.',
                implode(', ', $restricted),
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     */
    private function populate(User $user, array $attributes, array $fields): void
    {
        Typecast::configure($user, $attributes);
        $user->setFieldValues($fields);
    }

    /** @return array<string, mixed> */
    private function save(User $user, CraftUser $actor): array
    {
        $result = $this->userInitiatedElementSave->save($user, $actor);

        if (! $result->successful || ! $result->element instanceof User) {
            throw new ToolCallException(implode("\n", $result->element->errors()->all()) ?: 'User could not be saved.');
        }

        return $this->serialize($result->element);
    }

    /**
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    private function serializeSummary(User $user, ?array $fields): array
    {
        return $this->elementSerializer->serialize($user, $this->summaryData($user), filterNulls: false, fields: $fields);
    }

    /** @return array<string, mixed> */
    private function summaryData(User $user): array
    {
        return [
            'id' => $user->id,
            'uid' => $user->uid,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->getName(),
            'friendlyName' => $user->getFriendlyName(),
            'status' => $user->getStatus(),
            'admin' => $user->admin,
            'active' => $user->active,
            'pending' => $user->pending,
            'locked' => $user->locked,
            'suspended' => $user->suspended,
        ];
    }

    /**
     * @param  list<string>|null  $fields
     * @return array<string, mixed>
     */
    private function serialize(User $user, ?array $fields = null): array
    {
        return $this->elementSerializer->serialize(
            $user,
            [
                ...$this->summaryData($user),
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'photoId' => $user->photoId,
                'affiliatedSiteId' => $user->affiliatedSiteId,
                'hasDashboard' => $user->hasDashboard,
                'lastLoginDate' => $this->date($user->lastLoginDate),
                'dateCreated' => $this->date($user->dateCreated),
                'dateUpdated' => $this->date($user->dateUpdated),
                'groups' => array_map($this->serializeGroup(...), $user->getGroups()),
            ],
            filterNulls: false,
            fields: $fields,
        );
    }

    /** @return array<string, mixed> */
    private function serializeGroup(UserGroup $group): array
    {
        return [
            'id' => $group->id,
            'uid' => $group->uid,
            'handle' => $group->handle,
            'name' => $group->name,
            'description' => $group->description,
        ];
    }

    private function date(mixed $date): ?string
    {
        return DateTimeHelper::toIso8601($date) ?: null;
    }
}
