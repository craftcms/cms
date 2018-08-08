<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\User;
use craft\helpers\Db;
use craft\models\UserGroup;
use yii\db\Connection;

/**
 * UserQuery represents a SELECT SQL statement for users in a way that is independent of DBMS.
 *
 * @property string|string[]|UserGroup $group The handle(s) of the tag group(s) that resulting users must belong to.
 * @method User[]|array all($db = null)
 * @method User|array|null one($db = null)
 * @method User|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool|null Whether to only return users that are admins.
     * ---
     * ```php
     * // fetch all the admins
     * $admins = \craft\elements\User::find()
     *     ->admin(true)
     *     ->all();
     *
     * // fetch all the non-admins
     * $nonAdmins = \craft\elements\User::find()
     *     ->admin(false)
     *     ->all();
     * ```
     * ```twig
     * {# fetch all the admins #}
     * {% set admins = craft.users()
     *     .admin()
     *     .all()%}
     *
     * {# fetch all the non-admins #}
     * {% set nonAdmins = craft.users()
     *     .admin(false)
     *     .all() %}
     * ```
     * @used-by admin()
     */
    public $admin;

    /**
     * @var string|int|false|null The permission that the resulting users must have.
     * ---
     * ```php
     * // fetch users with CP access
     * $admins = \craft\elements\User::find()
     *     ->can('accessCp')
     *     ->all();
     * ```
     * ```twig
     * {# fetch users with CP access #}
     * {% set admins = craft.users()
     *     .can('accessCp')
     *     .all() %}
     * ```
     * @used-by can()
     */
    public $can;

    /**
     * @var int|int[]|null The user group ID(s) that the resulting users must belong to.
     * ---
     * ```php
     * // fetch the authors
     * $admins = \craft\elements\User::find()
     *     ->group('authors')
     *     ->all();
     * ```
     * ```twig
     * {# fetch the authors #}
     * {% set admins = craft.users()
     *     .group('authors')
     *     .all() %}
     * ```
     * @used-by group()
     * @used-by groupId()
     */
    public $groupId;

    /**
     * @var string|string[]|null The email address that the resulting users must have.
     * @used-by email()
     */
    public $email;

    /**
     * @var string|string[]|null The username that the resulting users must have.
     * @used-by username()
     */
    public $username;

    /**
     * @var string|string[]|null The first name that the resulting users must have.
     * @used-by firstName()
     */
    public $firstName;

    /**
     * @var string|string[]|null The last name that the resulting users must have.
     * @used-by lastName()
     */
    public $lastName;

    /**
     * @var mixed The date that the resulting users must have last logged in.
     * @used-by lastLoginDate()
     */
    public $lastLoginDate;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'users.username';
        }

        // Default status
        if (!isset($config['status'])) {
            $config['status'] = [User::STATUS_ACTIVE];
        }

        parent::__construct($elementType, $config);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name === 'group') {
            $this->group($value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Sets the [[$admin]] property.
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     * @uses $admin
     */
    public function admin(bool $value = true)
    {
        $this->admin = $value;
        return $this;
    }

    /**
     * Sets the [[$can]] property.
     *
     * @param string|int|null $value The property value
     * @return static self reference
     * @uses $can
     */
    public function can($value)
    {
        $this->can = $value;
        return $this;
    }

    /**
     * Sets the [[$groupId]] property based on a given tag group(s)’s handle(s).
     *
     * @param string|string[]|UserGroup|null $value The property value
     * @return static self reference
     * @uses $groupId
     */
    public function group($value)
    {
        if ($value instanceof UserGroup) {
            $this->groupId = $value->id;
        } else if ($value !== null) {
            $this->groupId = (new Query())
                ->select(['id'])
                ->from(['{{%usergroups}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->groupId = null;
        }

        return $this;
    }

    /**
     * Sets the [[$groupId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $groupId
     */
    public function groupId($value)
    {
        $this->groupId = $value;
        return $this;
    }

    /**
     * Sets the [[$email]] property.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $email
     */
    public function email($value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * Sets the [[$username]] property.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $username
     */
    public function username($value)
    {
        $this->username = $value;
        return $this;
    }

    /**
     * Sets the [[$firstName]] property.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $firstName
     */
    public function firstName($value)
    {
        $this->firstName = $value;
        return $this;
    }

    /**
     * Sets the [[$lastName]] property.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $lastName
     */
    public function lastName($value)
    {
        $this->lastName = $value;
        return $this;
    }

    /**
     * Sets the [[$lastLoginDate]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $lastLoginDate
     */
    public function lastLoginDate($value)
    {
        $this->lastLoginDate = $value;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        // See if 'group' was set to an invalid handle
        if ($this->groupId === []) {
            return false;
        }

        $this->joinElementTable('users');

        $this->query->select([
            'users.username',
            // TODO: uncomment after next breakpoint
            //'users.photoId',
            'users.firstName',
            'users.lastName',
            'users.email',
            'users.admin',
            'users.locked',
            'users.pending',
            'users.suspended',
            'users.lastLoginDate',
            'users.lockoutDate',
            // TODO: uncomment after next breakpoint
            //'users.hasDashboard',
        ]);

        // TODO: remove after next breakpoint
        $version = Craft::$app->getInfo()->version;
        if (version_compare($version, '3.0.0-alpha.2910', '>=')) {
            $this->query->addSelect(['users.photoId']);
        }
        if (version_compare($version, '3.0.4', '>=')) {
            $this->query->addSelect(['users.hasDashboard']);
        }

        if (is_bool($this->admin)) {
            $this->subQuery->andWhere(['users.admin' => $this->admin]);
        }

        if ($this->admin !== true) {
            $this->_applyCanParam();
        }

        if ($this->groupId) {
            $userIds = (new Query())
                ->select(['userId'])
                ->from(['{{%usergroups_users}}'])
                ->where(Db::parseParam('groupId', $this->groupId))
                ->column();

            if (!empty($userIds)) {
                $this->subQuery->andWhere(['elements.id' => $userIds]);
            } else {
                return false;
            }
        }

        if ($this->email) {
            $this->subQuery->andWhere(Db::parseParam('users.email', $this->email));
        }

        if ($this->username) {
            $this->subQuery->andWhere(Db::parseParam('users.username', $this->username));
        }

        if ($this->firstName) {
            $this->subQuery->andWhere(Db::parseParam('users.firstName', $this->firstName));
        }

        if ($this->lastName) {
            $this->subQuery->andWhere(Db::parseParam('users.lastName', $this->lastName));
        }

        if ($this->lastLoginDate) {
            $this->subQuery->andWhere(Db::parseDateParam('users.lastLoginDate', $this->lastLoginDate));
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status)
    {
        switch ($status) {
            case User::STATUS_ACTIVE:
                return [
                    'users.suspended' => false,
                    'users.locked' => false,
                    'users.pending' => false
                ];
            case User::STATUS_PENDING:
                return [
                    'users.pending' => true
                ];
            case User::STATUS_LOCKED:
                return [
                    'users.locked' => true
                ];
            case User::STATUS_SUSPENDED:
                return [
                    'users.suspended' => true
                ];
            default:
                return parent::statusCondition($status);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Applies the 'can' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyCanParam()
    {
        if ($this->can !== false && empty($this->can)) {
            return;
        }

        if (is_string($this->can) && !is_numeric($this->can)) {
            // Convert it to the actual permission ID, or false if the permission doesn't have an ID yet.
            $this->can = (new Query())
                ->select(['id'])
                ->from(['{{%userpermissions}}'])
                ->where(['name' => strtolower($this->can)])
                ->scalar();
        }

        // False means that the permission doesn't have an ID yet.
        if ($this->can !== false) {
            // Get the users that have that permission directly
            $permittedUserIds = (new Query())
                ->select(['userId'])
                ->from(['{{%userpermissions_users}}'])
                ->where(['permissionId' => $this->can])
                ->column();

            // Get the users that have that permission via a user group
            $permittedUserIdsViaGroups = (new Query())
                ->select(['g_u.userId'])
                ->from(['{{%usergroups_users}} g_u'])
                ->innerJoin('{{%userpermissions_usergroups}} p_g', '[[p_g.groupId]] = [[g_u.groupId]]')
                ->where(['p_g.permissionId' => $this->can])
                ->column();

            $permittedUserIds = array_unique(array_merge($permittedUserIds, $permittedUserIdsViaGroups));
        }

        if (!empty($permittedUserIds)) {
            $condition = [
                'or',
                ['users.admin' => true],
                ['elements.id' => $permittedUserIds]
            ];
        } else {
            $condition = ['users.admin' => true];
        }

        $this->subQuery->andWhere($condition);
    }
}
