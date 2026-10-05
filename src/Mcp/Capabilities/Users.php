<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users as UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
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
            'group' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'array', 'items' => ['type' => 'string']],
                ],
                'description' => 'User group handle or handles.',
            ],
            'groupId' => [
                'anyOf' => [
                    ['type' => 'integer'],
                    ['type' => 'array', 'items' => ['type' => 'integer']],
                ],
                'description' => 'User group ID or IDs.',
            ],
            'username' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'array', 'items' => ['type' => 'string']],
                ],
                'description' => 'Username criteria.',
            ],
            'email' => [
                'anyOf' => [
                    ['type' => 'string'],
                    ['type' => 'array', 'items' => ['type' => 'string']],
                ],
                'description' => 'User email criteria.',
            ],
            'firstName' => ['type' => 'string', 'description' => 'First name criteria.'],
            'lastName' => ['type' => 'string', 'description' => 'Last name criteria.'],
            'fullName' => ['type' => 'string', 'description' => 'Full name criteria.'],
            'admin' => ['type' => 'boolean', 'description' => 'Whether to return admin users.'],
            'hasPhoto' => ['type' => 'boolean', 'description' => 'Whether to return users with a photo.'],
            'lastLoginDate' => ['type' => 'string', 'description' => 'Last login date criteria.'],
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
        'description' => 'Custom field values keyed by field handle.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private Request $request,
        private Elements $elements,
        private ElementQueryCriteria $elementQueryCriteria,
        private UserInitiatedElementSave $userInitiatedElementSave,
        private UserService $users,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria  Native Craft UserQuery criteria. Custom field criteria may be passed by field handle.
     * @return array{count: int, limit: int, offset: int, users: list<array<string, mixed>>}
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
    ): array {
        $query = User::find()
            ->status(null)
            ->orderBy('elements.id');

        $criteria = $this->elementQueryCriteria->apply($query, $criteria);
        $users = $query->all();

        return [
            'count' => count($users),
            'limit' => $criteria['limit'],
            'offset' => $criteria['offset'],
            'users' => array_map($this->serializeSummary(...), $users),
        ];
    }

    /**
     * @param  int|null  $id  User ID.
     * @param  string|null  $uid  User UID.
     * @param  string|null  $username  Username.
     * @param  string|null  $email  User email address.
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
    ): array {
        $user = $this->find($id, $uid, $username, $email);

        if (! $user) {
            throw new ToolCallException('User not found.');
        }

        return ['user' => $this->serialize($user)];
    }

    /**
     * @param  array<string, mixed>  $attributes  Built-in user attributes.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{user: array<string, mixed>}
     */
    #[McpTool(name: 'users.create', description: 'Creates a Craft CMS user.')]
    #[RequiresPermission('registerUsers')]
    public function create(
        #[Schema(definition: self::CreateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $actor = $this->actor();
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
        description: 'Updates a Craft CMS user.',
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

        $actor = $this->actor();

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

        if (! Gate::forUser($this->actor())->allows('delete', $user)) {
            throw new ToolCallException('You are not authorized to delete this user.');
        }

        if (! $this->elements->deleteElement($user, $hardDelete)) {
            throw new ToolCallException('User could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{user: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://users/{user}',
        name: 'craft-users-get',
        title: 'Craft User',
        description: 'A JSON Craft CMS user record addressed by user ID, UID, username, or email.',
        mimeType: 'application/json',
    )]
    #[RequiresPermission('viewUsers')]
    public function resourceByIdentifier(string $user): array
    {
        $resolved = match (true) {
            ctype_digit($user) => $this->find(id: (int) $user),
            Str::isUuid($user) => $this->find(uid: $user),
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

    private function actor(): CraftUser
    {
        $actor = $this->request->craftUser();

        if (! $actor) {
            throw new ToolCallException('Authentication is required.');
        }

        return $actor;
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

    /** @return array<string, mixed> */
    private function serializeSummary(User $user): array
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

    /** @return array<string, mixed> */
    private function serialize(User $user): array
    {
        return [
            ...$this->serializeSummary($user),
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'photoId' => $user->photoId,
            'affiliatedSiteId' => $user->affiliatedSiteId,
            'hasDashboard' => $user->hasDashboard,
            'lastLoginDate' => $this->date($user->lastLoginDate),
            'dateCreated' => $this->date($user->dateCreated),
            'dateUpdated' => $this->date($user->dateUpdated),
            'groups' => array_map($this->serializeGroup(...), $user->getGroups()),
        ];
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
