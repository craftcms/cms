<?php

declare(strict_types=1);

namespace craft\mcp\elements\adapters;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\mcp\ElementLifecycle;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\elements\BaseElementAdapter;
use craft\mcp\serializers\ElementSerializer;
use craft\models\UserGroup;
use craft\services\Users;
use Mcp\Exception\ToolCallException;

/**
 * @since 5.12.0
 */
class UserAdapter extends BaseElementAdapter
{
    /**
     * Attributes non-admins may set on any user they are allowed to save.
     */
    private const UnrestrictedAttributes = [
        'affiliatedSiteId',
        'firstName',
        'fullName',
        'hasDashboard',
        'lastName',
        'photoId',
    ];

    public function __construct(
        ElementSerializer $elementSerializer,
        ElementLifecycle $lifecycle,
        private readonly Users $users,
    ) {
        parent::__construct($elementSerializer, $lifecycle);
    }

    public static function handle(): string
    {
        return 'users';
    }

    public static function elementType(): string
    {
        return User::class;
    }

    protected function criteriaProperties(): array
    {
        return ElementQueryCriteria::UserSchemaProperties;
    }

    protected function createAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                ...$this->sharedAttributeProperties(),
                'active' => ['type' => 'boolean', 'description' => 'Whether the user is active.'],
                'pending' => ['type' => 'boolean', 'description' => 'Whether the user is pending activation.'],
                'affiliatedSiteId' => ['type' => ['integer', 'null'], 'description' => 'Affiliated site ID.'],
                'photoId' => ['type' => ['integer', 'null'], 'description' => 'Photo asset ID.'],
                'hasDashboard' => ['type' => 'boolean', 'description' => 'Whether the user has a dashboard.'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                ...$this->sharedAttributeProperties(),
                'enabled' => ['type' => 'boolean', 'description' => 'Whether the user is enabled.'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function notes(): array
    {
        return [
            'Listing and reading users requires the viewUsers permission, creating them requires registerUsers, updating them requires editUsers, and deleting them requires deleteUsers.',
            'Only admins may set every attribute. Other users may set names, photoId, affiliatedSiteId, and hasDashboard, plus email and username for new users. Users with administrateUsers may also set email.',
            'Find a user by username or email with elements.list and the username or email criteria.',
        ];
    }

    public function listQuery(User $actor): ElementQueryInterface
    {
        $this->requirePermission($actor, 'viewUsers', 'view users');

        return User::find()->status(null)->orderBy('elements.id');
    }

    public function find(?int $id, ?string $uid, ?int $siteId): ?ElementInterface
    {
        if (($id === null) === ($uid === null)) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        return $id !== null
            ? $this->users->getUserById($id)
            : $this->users->getUserByUid($uid);
    }

    public function canView(User $actor, ElementInterface $element): bool
    {
        return $actor->can('viewUsers');
    }

    public function canDelete(User $actor, ElementInterface $element): bool
    {
        return $actor->can('deleteUsers') && parent::canDelete($actor, $element);
    }

    /** @param User $element */
    public function serialize(ElementInterface $element, ?array $fields = null, bool $summary = false): array
    {
        $data = $this->summaryData($element);

        if (!$summary) {
            $data = [
                ...$data,
                'firstName' => $element->firstName,
                'lastName' => $element->lastName,
                'photoId' => $element->photoId,
                'affiliatedSiteId' => $element->affiliatedSiteId,
                'hasDashboard' => $element->hasDashboard,
                'lastLoginDate' => $this->date($element->lastLoginDate),
                'dateCreated' => $this->date($element->dateCreated),
                'dateUpdated' => $this->date($element->dateUpdated),
                'groups' => array_map($this->serializeGroup(...), $element->getGroups()),
            ];
        }

        return $this->elementSerializer->serialize($element, $data, filterNulls: false, fields: $fields);
    }

    public function create(array $attributes, array $fields, User $actor): array
    {
        $this->requirePermission($actor, 'registerUsers', 'register users');

        $user = new User();

        $this->authorizeSave($actor, $user);
        $this->authorizeAttributes($actor, $attributes, new: true);
        $this->populate($user, $attributes, $fields, $actor);
        $this->authorizeSave($actor, $user);

        return $this->save($user, $actor);
    }

    public function update(ElementInterface $element, array $attributes, array $fields, User $actor): array
    {
        $this->requirePermission($actor, 'editUsers', 'edit users');
        $this->authorizeAttributes($actor, $attributes);

        return parent::update($element, $attributes, $fields, $actor);
    }

    public function prepareValidation(ElementInterface $element, array $attributes, array $fields, User $actor): void
    {
        $this->authorizeSave($actor, $element);
        $this->requirePermission($actor, 'editUsers', 'edit users');
        $this->authorizeAttributes($actor, $attributes);
        $this->populate($element, $attributes, $fields, $actor);
        $this->authorizeSave($actor, $element);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sharedAttributeProperties(): array
    {
        return [
            'email' => ['type' => 'string', 'format' => 'email', 'description' => 'User email address.'],
            'username' => ['type' => 'string', 'description' => 'Username.'],
            'firstName' => ['type' => 'string', 'description' => 'First name.'],
            'lastName' => ['type' => 'string', 'description' => 'Last name.'],
            'fullName' => ['type' => 'string', 'description' => 'Full name.'],
            'newPassword' => ['type' => 'string', 'description' => 'New password.'],
            'admin' => ['type' => 'boolean', 'description' => 'Whether the user is an admin.'],
        ];
    }

    private function requirePermission(User $actor, string $permission, string $action): void
    {
        if (!$actor->can($permission)) {
            throw new ToolCallException("You are not authorized to {$action}.");
        }
    }

    /** @param array<string, mixed> $attributes */
    private function authorizeAttributes(User $actor, array $attributes, bool $new = false): void
    {
        if ($actor->admin) {
            return;
        }

        $allowed = self::UnrestrictedAttributes;

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
