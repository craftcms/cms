<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
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
 *
 * @method User[]|array all($db = null)
 * @method User|array|null one($db = null)
 * @method User|array|null nth(int $n, Connection $db = null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether to only return users that are admins.
     */
    public $admin = false;

    /**
     * @var bool Whether to only return the client user.
     */
    public $client = false;

    /**
     * @var string|int|false|null The permission that the resulting users must have.
     */
    public $can;

    /**
     * @var int|int[]|null The tag group ID(s) that the resulting users must be in.
     */
    public $groupId;

    /**
     * @var string|string[]|null The email address that the resulting users must have.
     */
    public $email;

    /**
     * @var string|string[]|null The username that the resulting users must have.
     */
    public $username;

    /**
     * @var string|string[]|null The first name that the resulting users must have.
     */
    public $firstName;

    /**
     * @var string|string[]|null The last name that the resulting users must have.
     */
    public $lastName;

    /**
     * @var mixed The date that the resulting users must have last logged in.
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
     * Sets the [[admin]] property.
     *
     * @param bool $value The property value (defaults to true)
     *
     * @return static self reference
     */
    public function admin(bool $value = true)
    {
        $this->admin = $value;

        return $this;
    }

    /**
     * Sets the [[client]] property.
     *
     * @param bool $value The property value (defaults to true)
     *
     * @return static self reference
     */
    public function client(bool $value = true)
    {
        $this->client = $value;

        return $this;
    }

    /**
     * Sets the [[can]] property.
     *
     * @param string|int|null $value The property value
     *
     * @return static self reference
     */
    public function can($value)
    {
        $this->can = $value;

        return $this;
    }

    /**
     * Sets the [[groupId]] property based on a given tag group(s)’s handle(s).
     *
     * @param string|string[]|UserGroup|null $value The property value
     *
     * @return static self reference
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
     * Sets the [[groupId]] property.
     *
     * @param int|int[]|null $value The property value
     *
     * @return static self reference
     */
    public function groupId($value)
    {
        $this->groupId = $value;

        return $this;
    }

    /**
     * Sets the [[email]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function email($value)
    {
        $this->email = $value;

        return $this;
    }

    /**
     * Sets the [[username]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function username($value)
    {
        $this->username = $value;

        return $this;
    }

    /**
     * Sets the [[firstName]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function firstName($value)
    {
        $this->firstName = $value;

        return $this;
    }

    /**
     * Sets the [[lastName]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function lastName($value)
    {
        $this->lastName = $value;

        return $this;
    }

    /**
     * Sets the [[lastLoginDate]] property.
     *
     * @param mixed $value The property value
     *
     * @return static self reference
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
            'users.client',
            'users.locked',
            'users.pending',
            'users.suspended',
            'users.archived',
            'users.lastLoginDate',
            'users.lockoutDate',
        ]);

        // TODO: remove after next breakpoint
        if (version_compare(Craft::$app->getInfo()->version, '3.0.0-alpha.2910', '>=')) {
            $this->query->addSelect(['users.photoId']);
        }

        if ($this->admin) {
            $this->subQuery->andWhere(['users.admin' => '1']);
        } else if ($this->client) {
            $this->subQuery->andWhere(['users.client' => '1']);
        } else {
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
                    'users.archived' => '0',
                    'users.suspended' => '0',
                    'users.locked' => '0',
                    'users.pending' => '0'
                ];
            case User::STATUS_PENDING:
                return [
                    'users.pending' => '1'
                ];
            case User::STATUS_LOCKED:
                return [
                    'users.locked' => '1'
                ];
            case User::STATUS_SUSPENDED:
                return [
                    'users.suspended' => '1'
                ];
            case User::STATUS_ARCHIVED:
                return [
                    'users.archived' => '1'
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
     * @return void
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
                ['users.admin' => '1'],
                ['elements.id' => $permittedUserIds]
            ];
        } else {
            $condition = ['users.admin' => '1'];
        }

        $this->subQuery->andWhere($condition);
    }
}
