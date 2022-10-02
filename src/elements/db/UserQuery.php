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
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;
use craft\models\UserGroup;
use yii\db\Connection;
use yii\db\Expression;

/**
 * UserQuery represents a SELECT SQL statement for users in a way that is independent of DBMS.
 *
 * @property-write string|string[]|UserGroup|null $group The user group(s) that resulting users must belong to
 * @method User[]|array all($db = null)
 * @method User|array|null one($db = null)
 * @method User|array|null nth(int $n, ?Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @doc-path users.md
 * @supports-status-param
 * @replace {element} user
 * @replace {elements} users
 * @replace {twig-method} craft.users()
 * @replace {myElement} myUser
 * @replace {element-class} \craft\elements\User
 */
class UserQuery extends ElementQuery
{
    /**
     * @since 4.0.4
     */
    public const STATUS_CREDENTIALED = 'credentialed';

    /**
     * @inheritdoc
     */
    protected array $defaultOrderBy = ['users.username' => SORT_ASC];

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
     *   .admin()
     *   .all()%}
     *
     * {# fetch all the non-admins #}
     * {% set nonAdmins = craft.users()
     *     .admin(false)
     *     .all() %}
     * ```
     * @used-by admin()
     */
    public ?bool $admin = null;

    /**
     * @var bool|null Whether to only return users that are authors of an entry.
     * ---
     * ```php
     * // fetch all authors
     * $authors = \craft\elements\User::find()
     *     ->authors()
     *     ->all();
     * ```
     * ```twig
     * {# fetch all authors #}
     * {% set authors = craft.users()
     *   .authors()
     *   .all()%}
     * ```
     * @used-by authors()
     * @since 4.0.0
     */
    public ?bool $authors = null;

    /**
     * @var bool|null Whether to only return users that have uploaded an asset.
     * ---
     * ```php
     * // fetch all users who have uploaded an asset
     * $uploaders = \craft\elements\User::find()
     *     ->assetUploaders()
     *     ->all();
     * ```
     * ```twig
     * {# fetch all users who have uploaded an asset #}
     * {% set uploaders = craft.users()
     *   .assetUploaders()
     *   .all()%}
     * ```
     * @used-by assetUploaders()
     * @since 4.0.0
     */
    public ?bool $assetUploaders = null;

    /**
     * @var bool|null Whether to only return users that have (or don’t have) user photos.
     * @used-by hasPhoto()
     */
    public ?bool $hasPhoto = null;

    /**
     * @var mixed The permission that the resulting users must have.
     * ---
     * ```php
     * // fetch users with control panel access
     * $admins = \craft\elements\User::find()
     *     ->can('accessCp')
     *     ->all();
     * ```
     * ```twig
     * {# fetch users with control panel access #}
     * {% set admins = craft.users()
     *   .can('accessCp')
     *   .all() %}
     * ```
     * @used-by can()
     */
    public mixed $can = null;

    /**
     * @var mixed The user group ID(s) that the resulting users must belong to.
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
     *   .group('authors')
     *   .all() %}
     * ```
     * @used-by group()
     * @used-by groupId()
     */
    public mixed $groupId = null;

    /**
     * @var mixed The email address that the resulting users must have.
     * @used-by email()
     */
    public mixed $email = null;

    /**
     * @var mixed The username that the resulting users must have.
     * @used-by username()
     */
    public mixed $username = null;

    /**
     * @var mixed The full name that the resulting users must have.
     * @used-by fullName()
     * @since 4.0.0
     */
    public mixed $fullName = null;

    /**
     * @var mixed The first name that the resulting users must have.
     * @used-by firstName()
     */
    public mixed $firstName = null;

    /**
     * @var mixed The last name that the resulting users must have.
     * @used-by lastName()
     */
    public mixed $lastName = null;

    /**
     * @var mixed The date that the resulting users must have last logged in.
     * @used-by lastLoginDate()
     */
    public mixed $lastLoginDate = null;

    /**
     * @var bool Whether the users’ groups should be eager-loaded.
     * ---
     * ```php
     * // fetch users with their user groups
     * $users = \craft\elements\User::find()
     *     ->withGroups()
     *     ->all();
     * ```
     * ```twig
     * {# fetch users with their user groups #}
     * {% set users = craft.users()
     *   .withGroups()
     *   .all() %}
     * ```
     * @used-by withGroups()
     * @since 3.6.0
     */
    public bool $withGroups = false;

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
     * Narrows the query results to only users that have admin accounts.
     *
     * ---
     *
     * ```twig
     * {# Fetch admins #}
     * {% set {elements-var} = {twig-method}
     *   .admin()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch admins
     * ${elements-var} = {element-class}::find()
     *     ->admin()
     *     ->all();
     * ```
     *
     * @param bool $value The property value (defaults to true)
     * @return self self reference
     * @uses $admin
     */
    public function admin(bool $value = true): self
    {
        $this->admin = $value;
        return $this;
    }

    /**
     * Narrows the query results to only users that are authors of an entry.
     *
     * ---
     *
     * ```twig
     * {# Fetch authors #}
     * {% set {elements-var} = {twig-method}
     *   .authors()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch authors
     * ${elements-var} = {element-class}::find()
     *     ->authors()
     *     ->all();
     * ```
     *
     * @param bool|null $value The property value (defaults to true)
     * @return self self reference
     * @uses $authors
     * @since 4.0.0
     */
    public function authors(?bool $value = true): self
    {
        $this->authors = $value;
        return $this;
    }

    /**
     * Narrows the query results to only users that have uploaded an asset.
     *
     * ---
     *
     * ```twig
     * {# Fetch all users who have uploaded an asset #}
     * {% set {elements-var} = {twig-method}
     *   .assetUploaders()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all users who have uploaded an asset
     * ${elements-var} = {element-class}::find()
     *     ->assetUploaders()
     *     ->all();
     * ```
     *
     * @param bool|null $value The property value (defaults to true)
     * @return self self reference
     * @uses $assetUploaders
     * @since 4.0.0
     */
    public function assetUploaders(?bool $value = true): self
    {
        $this->assetUploaders = $value;
        return $this;
    }

    /**
     * Narrows the query results to only users that have (or don’t have) a user photo.
     *
     * ---
     *
     * ```twig
     * {# Fetch users with photos #}
     * {% set {elements-var} = {twig-method}
     *   .hasPhoto()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users without photos
     * ${elements-var} = {element-class}::find()
     *     ->hasPhoto()
     *     ->all();
     * ```
     *
     * @param bool $value The property value (defaults to true)
     * @return self self reference
     * @uses $hasPhoto
     */
    public function hasPhoto(bool $value = true): self
    {
        $this->hasPhoto = $value;
        return $this;
    }

    /**
     * Narrows the query results to only users that have a certain user permission, either directly on the user account or through one of their user groups.
     *
     * See [User Management](https://craftcms.com/docs/4.x/user-management.html) for a full list of available user permissions defined by Craft.
     *
     * ---
     *
     * ```twig
     * {# Fetch users that can access the control panel #}
     * {% set {elements-var} = {twig-method}
     *   .can('accessCp')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users that can access the control panel
     * ${elements-var} = {element-class}::find()
     *     ->can('accessCp')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $can
     */
    public function can(mixed $value): self
    {
        $this->can = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the user group the users belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'foo'` | in a group with a handle of `foo`.
     * | `'not foo'` | not in a group with a handle of `foo`.
     * | `['foo', 'bar']` | in a group with a handle of `foo` or `bar`.
     * | `['and', 'foo', 'bar']` | in both groups with handles of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a group with a handle of `foo` or `bar`.
     * | a [[UserGroup|UserGroup]] object | in a group represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch users in the Foo user group #}
     * {% set {elements-var} = {twig-method}
     *   .group('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users in the Foo user group
     * ${elements-var} = {php-method}
     *     ->group('foo')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $groupId
     */
    public function group(mixed $value): self
    {
        // If the value is a group handle, swap it with the user group
        if (is_string($value) && ($group = Craft::$app->getUserGroups()->getGroupByHandle($value))) {
            $value = $group;
        }

        if (Db::normalizeParam($value, function($item) {
            return $item instanceof UserGroup ? $item->id : null;
        })) {
            $this->groupId = $value;
        } else {
            $glue = Db::extractGlue($value);
            $this->groupId = (new Query())
                ->select(['id'])
                ->from([Table::USERGROUPS])
                ->where(Db::parseParam('handle', $value))
                ->column();
            if ($this->groupId && $glue !== null) {
                array_unshift($this->groupId, $glue);
            }
        }

        return $this;
    }

    /**
     * Narrows the query results based on the user group the users belong to, per the groups’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `1` | in a group with an ID of 1.
     * | `'not 1'` | not in a group with an ID of 1.
     * | `[1, 2]` | in a group with an ID of 1 or 2.
     * | `['and', 1, 2]` | in both groups with IDs of 1 or 2.
     * | `['not', 1, 2]` | not in a group with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch users in a group with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .groupId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users in a group with an ID of 1
     * ${elements-var} = {php-method}
     *     ->groupId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $groupId
     */
    public function groupId(mixed $value): self
    {
        $this->groupId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the users’ email addresses.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'me@domain.tld'` | with an email of `me@domain.tld`.
     * | `'not me@domain.tld'` | not with an email of `me@domain.tld`.
     * | `'*@domain.tld'` | with an email that ends with `@domain.tld`.
     *
     * ---
     *
     * ```twig
     * {# Fetch users with a .co.uk domain on their email address #}
     * {% set {elements-var} = {twig-method}
     *   .email('*.co.uk')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users with a .co.uk domain on their email address
     * ${elements-var} = {php-method}
     *     ->email('*.co.uk')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $email
     */
    public function email(mixed $value): self
    {
        $this->email = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the users’ usernames.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'foo'` | with a username of `foo`.
     * | `'not foo'` | not with a username of `foo`.
     *
     * ---
     *
     * ```twig
     * {# Get the requested username #}
     * {% set requestedUsername = craft.app.request.getSegment(2) %}
     *
     * {# Fetch that user #}
     * {% set {element-var} = {twig-method}
     *   .username(requestedUsername|literal)
     *   .one() %}
     * ```
     *
     * ```php
     * // Get the requested username
     * $requestedUsername = \Craft::$app->request->getSegment(2);
     *
     * // Fetch that user
     * ${element-var} = {php-method}
     *     ->username(\craft\helpers\Db::escapeParam($requestedUsername))
     *     ->one();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $username
     */
    public function username(mixed $value): self
    {
        $this->username = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the users’ full names.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'Jane Doe'` | with a full name of `Jane Doe`.
     * | `'not Jane Doe'` | not with a full name of `Jane Doe`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the Jane Doe's #}
     * {% set {elements-var} = {twig-method}
     *   .fullName('Jane Doe')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the Jane Doe's
     * ${elements-var} = {php-method}
     *     ->fullName('JaneDoe')
     *     ->one();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $fullName
     * @since 4.0.0
     */
    public function fullName(mixed $value): self
    {
        $this->fullName = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the users’ first names.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'Jane'` | with a first name of `Jane`.
     * | `'not Jane'` | not with a first name of `Jane`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the Jane's #}
     * {% set {elements-var} = {twig-method}
     *   .firstName('Jane')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the Jane's
     * ${elements-var} = {php-method}
     *     ->firstName('Jane')
     *     ->one();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $firstName
     */
    public function firstName(mixed $value): self
    {
        $this->firstName = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the users’ last names.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'Doe'` | with a last name of `Doe`.
     * | `'not Doe'` | not with a last name of `Doe`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the Doe's #}
     * {% set {elements-var} = {twig-method}
     *   .lastName('Doe')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the Doe's
     * ${elements-var} = {php-method}
     *     ->lastName('Doe')
     *     ->one();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $lastName
     */
    public function lastName(mixed $value): self
    {
        $this->lastName = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the users’ last login dates.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'>= 2018-04-01'` | that last logged in on or after 2018-04-01.
     * | `'< 2018-05-01'` | that last logged in before 2018-05-01.
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that last logged in between 2018-04-01 and 2018-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that last logged in at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch users that logged in recently #}
     * {% set aWeekAgo = date('7 days ago')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .lastLoginDate(">= #{aWeekAgo}")
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch users that logged in recently
     * $aWeekAgo = (new \DateTime('7 days ago'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->lastLoginDate(">= {$aWeekAgo}")
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $lastLoginDate
     */
    public function lastLoginDate(mixed $value): self
    {
        $this->lastLoginDate = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the users’ statuses.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'inactive'` | with inactive accounts.
     * | `'active'` | with active accounts.
     * | `'pending'` | with accounts that are still pending activation.
     * | `'credentialed'` | with either active or pending accounts.
     * | `'suspended'` | with suspended accounts.
     * | `'locked'` | with locked accounts (regardless of whether they’re active or suspended).
     * | `['active', 'suspended']` | with active or suspended accounts.
     * | `['not', 'active', 'suspended']` | without active or suspended accounts.
     *
     * ---
     *
     * ```twig
     * {# Fetch active and locked users #}
     * {% set {elements-var} = {twig-method}
     *   .status(['active', 'locked'])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch active and locked users
     * ${elements-var} = {element-class}::find()
     *     ->status(['active', 'locked'])
     *     ->all();
     * ```
     */
    public function status(array|string|null $value): self
    {
        /** @var self */
        return parent::status($value);
    }

    /**
     * Causes the query to return matching users eager-loaded with their user groups.
     *
     * Possible values include:
     *
     * | Value | Fetches users…
     * | - | -
     * | `'>= 2018-04-01'` | that last logged-in on or after 2018-04-01.
     * | `'< 2018-05-01'` | that last logged-in before 2018-05-01
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that last logged-in between 2018-04-01 and 2018-05-01.
     *
     * ---
     *
     * ```php
     * // fetch users with their user groups
     * $users = \craft\elements\User::find()
     *     ->withGroups()
     *     ->all();
     * ```
     *
     * ```twig
     * {# fetch users with their user groups #}
     * {% set users = craft.users()
     *   .withGroups()
     *   .all() %}
     * ```
     *
     * @param bool $value The property value (defaults to true)
     * @return self self reference
     * @uses $withGroups
     * @since 3.6.0
     */
    public function withGroups(bool $value = true): self
    {
        $this->withGroups = $value;
        return $this;
    }

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
            'users.photoId',
            'users.pending',
            'users.locked',
            'users.suspended',
            'users.admin',
            'users.username',
            'users.firstName',
            'users.lastName',
            'users.email',
            'users.unverifiedEmail',
            'users.lastLoginDate',
            'users.lockoutDate',
            'users.hasDashboard',
        ]);

        // todo: cleanup after next breakpoint
        $db = Craft::$app->getDb();
        $activeColumnExists = $db->columnExists(Table::USERS, 'active');
        $fullNameColumnExists = $db->columnExists(Table::USERS, 'fullName');

        if ($activeColumnExists) {
            $this->query->addSelect(['users.active']);
        }

        if ($fullNameColumnExists) {
            $this->query->addSelect(['users.fullName']);
        }

        if (is_bool($this->admin)) {
            $this->subQuery->andWhere(['users.admin' => $this->admin]);
        }

        if (is_bool($this->authors)) {
            $this->subQuery->andWhere([
                $this->authors ? 'exists' : 'not exists',
                (new Query())
                    ->from(Table::ENTRIES)
                    ->where(['authorId' => new Expression('[[elements.id]]')]),
            ]);
        }

        if (is_bool($this->assetUploaders)) {
            $this->subQuery->andWhere([
                $this->assetUploaders ? 'exists' : 'not exists',
                (new Query())
                    ->from(Table::ASSETS)
                    ->where(['uploaderId' => new Expression('[[elements.id]]')]),
            ]);
        }

        if (is_bool($this->hasPhoto)) {
            if ($this->hasPhoto) {
                $hasPhotoCondition = ['not', ['users.photoId' => null]];
            } else {
                $hasPhotoCondition = ['users.photoId' => null];
            }
            $this->subQuery->andWhere($hasPhotoCondition);
        }

        if ($this->admin !== true) {
            $this->_applyCanParam();
        }

        if ($this->groupId) {
            // Checking multiple groups?
            if (
                is_array($this->groupId) &&
                is_string(reset($this->groupId)) &&
                strtolower(reset($this->groupId)) === 'and'
            ) {
                $groupIdChecks = array_slice($this->groupId, 1);
            } else {
                $groupIdChecks = [$this->groupId];
            }

            foreach ($groupIdChecks as $i => $groupIdCheck) {
                if (
                    is_array($groupIdCheck) &&
                    is_string(reset($groupIdCheck)) &&
                    strtolower(reset($groupIdCheck)) === 'not'
                ) {
                    $groupIdOperator = 'not exists';
                    array_shift($groupIdCheck);
                    if (empty($groupIdCheck)) {
                        continue;
                    }
                } else {
                    $groupIdOperator = 'exists';
                }

                $this->subQuery->andWhere([
                    $groupIdOperator, (new Query())
                        ->from(["ugu$i" => Table::USERGROUPS_USERS])
                        ->where("[[elements.id]] = [[ugu$i.userId]]")
                        ->andWhere(Db::parseNumericParam('groupId', $groupIdCheck)),
                ]);
            }
        }

        if ($this->email) {
            $this->subQuery->andWhere(Db::parseParam('users.email', $this->email, '=', true));
        }

        if ($this->username) {
            $this->subQuery->andWhere(Db::parseParam('users.username', $this->username, '=', true));
        }

        if ($fullNameColumnExists && $this->fullName) {
            if (is_string($this->fullName)) {
                $this->fullName = Db::escapeCommas($this->fullName);
            }
            $this->subQuery->andWhere(Db::parseParam('users.fullName', $this->fullName, '=', true));
        }

        if ($this->firstName) {
            if (is_string($this->firstName)) {
                $this->firstName = Db::escapeCommas($this->firstName);
            }
            $this->subQuery->andWhere(Db::parseParam('users.firstName', $this->firstName, '=', true));
        }

        if ($this->lastName) {
            if (is_string($this->lastName)) {
                $this->lastName = Db::escapeCommas($this->lastName);
            }
            $this->subQuery->andWhere(Db::parseParam('users.lastName', $this->lastName, '=', true));
        }

        if ($this->lastLoginDate) {
            $this->subQuery->andWhere(Db::parseDateParam('users.lastLoginDate', $this->lastLoginDate));
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            User::STATUS_INACTIVE => [
                'users.active' => false,
                'users.pending' => false,
            ],
            User::STATUS_ACTIVE => [
                'users.active' => true,
                'users.suspended' => false,
            ],
            User::STATUS_PENDING => [
                'users.pending' => true,
            ],
            self::STATUS_CREDENTIALED => [
                'or',
                ['users.active' => true],
                ['users.pending' => true],
            ],
            User::STATUS_SUSPENDED => [
                'users.suspended' => true,
            ],
            User::STATUS_LOCKED => [
                'users.locked' => true,
            ],
            default => parent::statusCondition($status),
        };
    }

    /**
     * Applies the 'can' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyCanParam(): void
    {
        if ($this->can !== false && empty($this->can)) {
            return;
        }

        if (is_string($this->can) && !is_numeric($this->can)) {
            // Convert it to the actual permission ID, or false if the permission doesn't have an ID yet.
            $this->can = (new Query())
                ->select(['id'])
                ->from([Table::USERPERMISSIONS])
                ->where(['name' => strtolower($this->can)])
                ->scalar();
        }

        // False means that the permission doesn't have an ID yet.
        if ($this->can !== false) {
            // Get the users that have that permission directly
            $permittedUserIds = (new Query())
                ->select(['userId'])
                ->from([Table::USERPERMISSIONS_USERS])
                ->where(['permissionId' => $this->can])
                ->column();

            // Get the users that have that permission via a user group
            $permittedUserIdsViaGroups = (new Query())
                ->select(['g_u.userId'])
                ->from(['g_u' => Table::USERGROUPS_USERS])
                ->innerJoin(['p_g' => Table::USERPERMISSIONS_USERGROUPS], '[[p_g.groupId]] = [[g_u.groupId]]')
                ->where(['p_g.permissionId' => $this->can])
                ->column();

            $permittedUserIds = array_unique(array_merge($permittedUserIds, $permittedUserIdsViaGroups));
        }

        if (!empty($permittedUserIds)) {
            $condition = [
                'or',
                ['users.admin' => true],
                ['elements.id' => $permittedUserIds],
            ];
        } else {
            $condition = ['users.admin' => true];
        }

        $this->subQuery->andWhere($condition);
    }

    /**
     * @inheritdoc
     */
    public function afterPopulate(array $elements): array
    {
        /** @var User[] $elements */
        $elements = parent::afterPopulate($elements);

        // Eager-load user groups?
        if ($this->withGroups && !$this->asArray && Craft::$app->getEdition() === Craft::Pro) {
            Craft::$app->getUserGroups()->eagerLoadGroups($elements);
        }

        return $elements;
    }
}
