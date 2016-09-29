<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\dates\DateInterval;
use craft\app\db\Query;
use craft\app\elements\actions\DeleteUsers;
use craft\app\elements\actions\Edit;
use craft\app\elements\actions\SuspendUsers;
use craft\app\elements\actions\UnsuspendUsers;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\db\UserQuery;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\Html;
use craft\app\helpers\Url;
use craft\app\i18n\Locale;
use craft\app\models\UserGroup;
use craft\app\records\Session as SessionRecord;
use craft\app\records\User as UserRecord;
use craft\app\validators\DateTimeValidator;
use craft\app\validators\UniqueValidator;
use Exception;
use yii\base\ErrorHandler;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * User represents a user element.
 *
 * @property boolean     $isCurrent         Whether this is the current logged-in user
 * @property string      $name              The user's full name or username
 * @property string|null $preferredLanguage The user’s preferred language
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class User extends Element implements IdentityInterface
{
    // Constants
    // =========================================================================

    const IMPERSONATE_KEY = 'Craft.UserSessionService.prevImpersonateUserId';

    // User statuses
    // -------------------------------------------------------------------------

    const STATUS_ACTIVE = 'active';
    const STATUS_LOCKED = 'locked';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';
    const STATUS_ARCHIVED = 'archived';

    // Authentication error keys
    // -------------------------------------------------------------------------

    const AUTH_INVALID_CREDENTIALS = 'invalid_credentials';
    const AUTH_PENDING_VERIFICATION = 'pending_verification';
    const AUTH_ACCOUNT_LOCKED = 'account_locked';
    const AUTH_ACCOUNT_COOLDOWN = 'account_cooldown';
    const AUTH_PASSWORD_RESET_REQUIRED = 'password_reset_required';
    const AUTH_ACCOUNT_SUSPENDED = 'account_suspended';
    const AUTH_NO_CP_ACCESS = 'no_cp_access';
    const AUTH_NO_CP_OFFLINE_ACCESS = 'no_cp_offline_access';
    const AUTH_NO_SITE_OFFLINE_ACCESS = 'no_site_offline_access';
    const AUTH_USERNAME_INVALID = 'username_invalid';

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'User');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent()
    {
        return true;
    }

    /**
     * Returns whether this element type can have statuses.
     *
     * @return boolean
     */
    public static function hasStatuses()
    {
        return true;
    }

    /**
     * Returns all of the possible statuses that elements of this type may have.
     *
     * @return array|null
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE => Craft::t('app', 'Active'),
            self::STATUS_PENDING => Craft::t('app', 'Pending'),
            self::STATUS_LOCKED => Craft::t('app', 'Locked'),
            self::STATUS_SUSPENDED => Craft::t('app', 'Suspended'),
            //self::STATUS_ARCHIVED  => Craft::t('app', 'Archived')
        ];
    }

    /**
     * @inheritdoc
     *
     * @return UserQuery The newly created [[UserQuery]] instance.
     */
    public static function find()
    {
        return new UserQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function getSources($context = null)
    {
        $sources = [
            '*' => [
                'label' => Craft::t('app', 'All users'),
                'hasThumbs' => true
            ]
        ];

        if (Craft::$app->getEdition() == Craft::Pro) {
            // Admin source
            $sources['admins'] = [
                'label' => Craft::t('app', 'Admins'),
                'criteria' => ['admin' => true],
                'hasThumbs' => true
            ];

            $groups = Craft::$app->getUserGroups()->getAllGroups();

            if ($groups) {
                $sources[] = ['heading' => Craft::t('app', 'Groups')];

                foreach ($groups as $group) {
                    $key = 'group:'.$group->id;

                    $sources[$key] = [
                        'label' => Craft::t('site', $group->name),
                        'criteria' => ['groupId' => $group->id],
                        'hasThumbs' => true
                    ];
                }
            }
        }

        // Allow plugins to modify the sources
        Craft::$app->getPlugins()->call('modifyUserSources', [
            &$sources,
            $context
        ]);

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function getAvailableActions($source = null)
    {
        $actions = [];

        // Edit
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Edit::class,
            'label' => Craft::t('app', 'Edit user'),
        ]);

        if (Craft::$app->getUser()->checkPermission('administrateUsers')) {
            // Suspend
            $actions[] = SuspendUsers::class;

            // Unsuspend
            $actions[] = UnsuspendUsers::class;
        }

        if (Craft::$app->getUser()->checkPermission('deleteUsers')) {
            // Delete
            $actions[] = DeleteUsers::class;
        }

        // Allow plugins to add additional actions
        $allPluginActions = Craft::$app->getPlugins()->call('addUserActions',
            [$source], true);

        foreach ($allPluginActions as $pluginActions) {
            $actions = array_merge($actions, $pluginActions);
        }

        return $actions;
    }

    /**
     * @inheritdoc
     */
    public static function defineSearchableAttributes()
    {
        return ['username', 'firstName', 'lastName', 'fullName', 'email'];
    }

    /**
     * @inheritdoc
     */
    public static function defineSortableAttributes()
    {
        if (Craft::$app->getConfig()->get('useEmailAsUsername')) {
            $attributes = [
                'email' => Craft::t('app', 'Email'),
                'firstName' => Craft::t('app', 'First Name'),
                'lastName' => Craft::t('app', 'Last Name'),
                'lastLoginDate' => Craft::t('app', 'Last Login'),
                'elements.dateCreated' => Craft::t('app', 'Date Created'),
                'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
            ];
        } else {
            $attributes = [
                'username' => Craft::t('app', 'Username'),
                'firstName' => Craft::t('app', 'First Name'),
                'lastName' => Craft::t('app', 'Last Name'),
                'email' => Craft::t('app', 'Email'),
                'lastLoginDate' => Craft::t('app', 'Last Login'),
                'elements.dateCreated' => Craft::t('app', 'Date Created'),
                'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
            ];
        }

        // Allow plugins to modify the attributes
        Craft::$app->getPlugins()->call('modifyUserSortableAttributes',
            [&$attributes]);

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function defineAvailableTableAttributes()
    {
        if (Craft::$app->getConfig()->get('useEmailAsUsername')) {
            // Start with Email and don't even give Username as an option
            $attributes = [
                'email' => ['label' => Craft::t('app', 'Email')],
            ];
        } else {
            $attributes = [
                'username' => ['label' => Craft::t('app', 'Username')],
                'email' => ['label' => Craft::t('app', 'Email')],
            ];
        }

        $attributes['fullName'] = ['label' => Craft::t('app', 'Full Name')];
        $attributes['firstName'] = ['label' => Craft::t('app', 'First Name')];
        $attributes['lastName'] = ['label' => Craft::t('app', 'Last Name')];

        if (Craft::$app->getIsMultiSite()) {
            $attributes['preferredLanguage'] = ['label' => Craft::t('app', 'Preferred Language')];
        }

        $attributes['id'] = ['label' => Craft::t('app', 'ID')];
        $attributes['dateCreated'] = ['label' => Craft::t('app', 'Join Date')];
        $attributes['lastLoginDate'] = ['label' => Craft::t('app', 'Last Login')];
        $attributes['elements.dateCreated'] = ['label' => Craft::t('app', 'Date Created')];
        $attributes['elements.dateUpdated'] = ['label' => Craft::t('app', 'Date Updated')];

        // Allow plugins to modify the attributes
        $pluginAttributes = Craft::$app->getPlugins()->call('defineAdditionalUserTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultTableAttributes($source = null)
    {
        if (Craft::$app->getConfig()->get('useEmailAsUsername')) {
            $attributes = ['fullName', 'dateCreated', 'lastLoginDate'];
        } else {
            $attributes = ['fullName', 'email', 'dateCreated', 'lastLoginDate'];
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public static function getTableAttributeHtml(ElementInterface $element, $attribute)
    {
        /** @var User $element */
        // First give plugins a chance to set this
        $pluginAttributeHtml = Craft::$app->getPlugins()->callFirst('getUserTableAttributeHtml',
            [$element, $attribute], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        switch ($attribute) {
            case 'email': {
                $email = $element->email;

                if ($email) {
                    return Html::encodeParams('<a href="mailto:{email}">{email}</a>',
                        [
                            'email' => $email
                        ]);
                } else {
                    return '';
                }
            }

            case 'preferredLanguage': {
                $language = $element->getPreferredLanguage();

                if ($language) {
                    $locale = new Locale($language);

                    return $locale->getDisplayName(Craft::$app->language);
                }

                return '';
            }

            default: {
                return parent::getTableAttributeHtml($element, $attribute);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getElementQueryStatusCondition(ElementQueryInterface $query, $status)
    {
        switch ($status) {
            case self::STATUS_ACTIVE:
                return 'users.archived = 0 AND users.suspended = 0 AND users.locked = 0 and users.pending = 0';
            case self::STATUS_PENDING:
                return 'users.pending = 1';
            case self::STATUS_LOCKED:
                return 'users.locked = 1';
            case self::STATUS_SUSPENDED:
                return 'users.suspended = 1';
            case self::STATUS_ARCHIVED:
                return 'users.archived = 1';
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public static function getEditorHtml(ElementInterface $element)
    {
        /** @var User $element */
        $html = Craft::$app->getView()->renderTemplate('users/_accountfields', [
            'account' => $element,
            'isNewAccount' => false,
            'meta' => true,
        ]);

        $html .= parent::getEditorHtml($element);

        return $html;
    }

    /**
     * @inheritdoc Element::saveElement()
     *
     * @return boolean
     */
    public static function saveElement(ElementInterface $element, $params)
    {
        /** @var User $element */
        if (isset($params['username'])) {
            $element->username = $params['username'];
        }

        if (isset($params['firstName'])) {
            $element->firstName = $params['firstName'];
        }

        if (isset($params['lastName'])) {
            $element->lastName = $params['lastName'];
        }

        return Craft::$app->getUsers()->saveUser($element);
    }

    /**
     * @inheritdoc
     */
    public static function populateModel($model, $config)
    {
        parent::populateModel($model, $config);

        // Is the user in cooldown mode, and are they past their window?
        /** @var static $model */
        if ($model->locked) {
            $cooldownDuration = Craft::$app->getConfig()->get('cooldownDuration');

            if ($cooldownDuration) {
                if (!$model->getRemainingCooldownTime()) {
                    Craft::$app->getUsers()->unlockUser($model);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getEagerLoadingMap($sourceElements, $handle)
    {
        if ($handle == 'photo') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('id as source, photoId as target')
                ->from('{{%users}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => Asset::class,
                'map' => $map
            ];
        }

        return parent::getEagerLoadingMap($sourceElements, $handle);
    }

    // IdentityInterface Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        $user = User::find()
            ->id($id)
            ->status(null)
            ->withPassword()
            ->one();

        if ($user !== null) {
            if ($user->getStatus() == self::STATUS_ACTIVE) {
                return $user;
            }

            // If the previous user was an admin and we're impersonating the current user.
            if ($previousUserId = Craft::$app->getSession()->get(self::IMPERSONATE_KEY)) {
                $previousUser = Craft::$app->getUsers()->getUserById($previousUserId);

                if ($previousUser && $previousUser->admin) {
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Returns the authentication data from a given auth key.
     *
     * @param string $authKey
     *
     * @return array|null The authentication data, or `null` if it was invalid.
     */
    public static function getAuthData($authKey)
    {
        $data = json_decode($authKey, true);

        if (count($data) === 3 && isset($data[0], $data[1], $data[2])) {
            return $data;
        }

        return null;
    }

    // Properties
    // =========================================================================

    /**
     * @var string Username
     */
    public $username;

    /**
     * @var integer Photo asset id
     */
    public $photoId;

    /**
     * @var string First name
     */
    public $firstName;

    /**
     * @var string Last name
     */
    public $lastName;

    /**
     * @var string Email
     */
    public $email;

    /**
     * @var string Password
     */
    public $password;

    /**
     * @var boolean Admin
     */
    public $admin = false;

    /**
     * @var boolean Client
     */
    public $client = false;

    /**
     * @var boolean Locked
     */
    public $locked = false;

    /**
     * @var boolean Suspended
     */
    public $suspended = false;

    /**
     * @var boolean Pending
     */
    public $pending = false;

    /**
     * @var \DateTime Last login date
     */
    public $lastLoginDate;

    /**
     * @var integer Invalid login count
     */
    public $invalidLoginCount;

    /**
     * @var \DateTime Last invalid login date
     */
    public $lastInvalidLoginDate;

    /**
     * @var \DateTime Lockout date
     */
    public $lockoutDate;

    /**
     * @var boolean Password reset required
     */
    public $passwordResetRequired = false;

    /**
     * @var \DateTime Last password change date
     */
    public $lastPasswordChangeDate;

    /**
     * @var string Unverified email
     */
    public $unverifiedEmail;

    /**
     * @var string New password
     */
    public $newPassword;

    /**
     * @var string Current password
     */
    public $currentPassword;

    /**
     * @var \DateTime Verification code issued date
     */
    public $verificationCodeIssuedDate;

    /**
     * @var string Verification code
     */
    public $verificationCode;

    /**
     * @var string Last login attempt IP address.
     */
    public $lastLoginAttemptIp;

    /**
     * @var string Auth error
     */
    public $authError;

    /**
     * @var Asset user photo
     */
    private $_photo;

    /**
     * @var array The cached list of groups the user belongs to. Set by [[getGroups()]].
     */
    private $_groups;

    /**
     * @var array The user’s preferences
     */
    private $_preferences;

    // Public Methods
    // =========================================================================

    /**
     * Use the full name or username as the string representation.
     *
     * @return string
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        try {
            return $this->getName();
        } catch (Exception $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes()
    {
        $names = parent::datetimeAttributes();
        $names[] = 'lastLoginDate';
        $names[] = 'lastInvalidLoginDate';
        $names[] = 'lockoutDate';
        $names[] = 'lastPasswordChangeDate';
        $names[] = 'verificationCodeIssuedDate';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['lastLoginDate', 'lastInvalidLoginDate', 'lockoutDate', 'lastPasswordChangeDate', 'verificationCodeIssuedDate'], DateTimeValidator::class];
        $rules[] = [['invalidLoginCount', 'photoId'], 'number', 'integerOnly' => true];
        $rules[] = [['email', 'unverifiedEmail'], 'email'];
        $rules[] = [['email', 'password', 'unverifiedEmail'], 'string', 'max' => 255];
        $rules[] = [['username', 'firstName', 'lastName', 'verificationCode'], 'string', 'max' => 100];
        $rules[] = [['username', 'email'], 'required'];
        $rules[] = [['lastLoginAttemptIp'], 'string', 'max' => 45];

        $rules[] = [
            ['username', 'email'],
            UniqueValidator::class,
            'targetClass' => UserRecord::class
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        $token = Craft::$app->getSecurity()->generateRandomString(100);
        $tokenUid = $this->_storeSessionToken($token);
        $userAgent = Craft::$app->getRequest()->getUserAgent();

        // The auth key is a combination of the hashed token, its row's UID, and the user agent string
        return json_encode([
            $token,
            $tokenUid,
            $userAgent,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        $data = static::getAuthData($authKey);

        if ($data) {
            list($token, $tokenUid, $userAgent) = $data;

            return (
                $this->_validateUserAgent($userAgent) &&
                ($token === $this->_findSessionTokenByUid($tokenUid))
            );
        }

        return false;
    }

    /**
     * Determines whether the user is allowed to be logged in with a given password.
     *
     * @param string $password The user's plain text passwerd.
     *
     * @return boolean
     */
    public function authenticate($password)
    {
        switch ($this->getStatus()) {
            case self::STATUS_ARCHIVED: {
                $this->authError = self::AUTH_INVALID_CREDENTIALS;
                break;
            }

            case self::STATUS_PENDING: {
                $this->authError = self::AUTH_PENDING_VERIFICATION;
                break;
            }

            case self::STATUS_SUSPENDED: {
                $this->authError = self::AUTH_ACCOUNT_SUSPENDED;
                break;
            }

            case self::STATUS_LOCKED: {
                // If the account is locked, but they just entered a valid password
                if (Craft::$app->getSecurity()->validatePassword($password, $this->password)) {
                    // Let them know how much time they have to wait (if any) before their account is unlocked.
                    if (Craft::$app->getConfig()->get('cooldownDuration')) {
                        $this->authError = self::AUTH_ACCOUNT_COOLDOWN;
                    } else {
                        $this->authError = self::AUTH_ACCOUNT_LOCKED;
                    }
                } else {
                    // Otherwise, just give them the invalid username/password message to help prevent user enumeration.
                    $this->authError = self::AUTH_INVALID_CREDENTIALS;
                }

                break;
            }

            case self::STATUS_ACTIVE: {
                // Validate the password
                if (!Craft::$app->getSecurity()->validatePassword($password, $this->password)) {
                    Craft::$app->getUsers()->handleInvalidLogin($this);

                    // Was that one bad password too many?
                    if ($this->locked) {
                        // Will set the authError to either AccountCooldown or AccountLocked
                        return $this->authenticate($password);
                    }

                    $this->authError = self::AUTH_INVALID_CREDENTIALS;
                    break;
                }

                // Is a password reset required?
                if ($this->passwordResetRequired) {
                    $this->authError = self::AUTH_PASSWORD_RESET_REQUIRED;
                    break;
                }

                $request = Craft::$app->getRequest();

                if (!$request->getIsConsoleRequest()) {
                    if ($request->getIsCpRequest()) {
                        if (!$this->can('accessCp')) {
                            if (!$this->authError) {
                                $this->authError = self::AUTH_NO_CP_ACCESS;
                            }
                        }

                        if (!Craft::$app->getIsSystemOn() && !$this->can('accessCpWhenSystemIsOff')) {
                            if (!$this->authError) {
                                $this->authError = self::AUTH_NO_CP_OFFLINE_ACCESS;
                            }
                        }
                    } else {
                        if (!Craft::$app->getIsSystemOn() && !$this->can('accessSiteWhenSystemIsOff')) {
                            if (!$this->authError) {
                                $this->authError = self::AUTH_NO_SITE_OFFLINE_ACCESS;
                            }
                        }
                    }
                }
            }
        }

        if (!$this->authError) {
            return true;
        }

        return false;
    }

    /**
     * Returns the reference string to this element.
     *
     * @return string|null
     */
    public function getRef()
    {
        return $this->username;
    }

    /**
     * Returns the user's groups.
     *
     * @param string|null $indexBy
     *
     * @return array
     */
    public function getGroups($indexBy = null)
    {
        if (!isset($this->_groups)) {
            if (Craft::$app->getEdition() == Craft::Pro) {
                $this->_groups = Craft::$app->getUserGroups()->getGroupsByUserId($this->id);
            } else {
                $this->_groups = [];
            }
        }

        if (!$indexBy) {
            $groups = $this->_groups;
        } else {
            $groups = [];

            foreach ($this->_groups as $group) {
                $groups[$group->$indexBy] = $group;
            }
        }

        return $groups;
    }

    /**
      * Sets an array of User element objects on the user.
      *
      * @param array $groups An array of User element objects.
      *
      * @return void
      */
     public function setGroups($groups)
     {
        if (Craft::$app->getEdition() == Craft::Pro)
        {
            $this->_groups = $groups;
        }
     }

    /**
     * Returns whether the user is in a specific group.
     *
     * @param mixed $group The user group model, its handle, or ID.
     *
     * @return boolean
     */
    public function isInGroup($group)
    {
        if (Craft::$app->getEdition() == Craft::Pro) {
            if (is_object($group) && $group instanceof UserGroup) {
                $group = $group->id;
            }

            if (is_numeric($group)) {
                $groups = array_keys($this->getGroups('id'));
            } else if (is_string($group)) {
                $groups = array_keys($this->getGroups('handle'));
            }

            if (!empty($groups)) {
                return in_array($group, $groups);
            }
        }

        return false;
    }

    /**
     * Gets the user's full name.
     *
     * @return string|null
     */
    public function getFullName()
    {
        $firstName = trim($this->firstName);
        $lastName = trim($this->lastName);

        return $firstName.($firstName && $lastName ? ' ' : '').$lastName;
    }

    /**
     * Returns the user's full name or username.
     *
     * @return string
     */
    public function getName()
    {
        $fullName = $this->getFullName();

        if ($fullName) {
            return $fullName;
        }

        return $this->username;
    }

    /**
     * Gets the user's first name or username.
     *
     * @return string|null
     */
    public function getFriendlyName()
    {
        if ($firstName = trim($this->firstName)) {
            return $firstName;
        }

        return $this->username;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        if ($this->locked) {
            return self::STATUS_LOCKED;
        }

        if ($this->suspended) {
            return self::STATUS_SUSPENDED;
        }

        if ($this->archived) {
            return self::STATUS_ARCHIVED;
        }

        if ($this->pending) {
            return self::STATUS_PENDING;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Sets a user's status to active.
     *
     * @return void
     */
    public function setActive()
    {
        $this->pending = false;
        $this->archived = false;
    }

    /**
     * Returns the URL to the user's photo.
     *
     * @param int $size The width and height the photo should be sized to
     *
     * @return string|null
     * @deprecated in 3.0. Use getPhoto().getUrl() instead.
     */
    public function getPhotoUrl($size = 100)
    {
        Craft::$app->getDeprecator()->log('User::getPhotoUrl()', 'User::getPhotoUrl() has been deprecated. Use getPhoto() to access the photo asset (if there is one), and call its getUrl() method to access the photo URL.');
        $photo = $this->getPhoto();

        if ($photo) {
            return $photo->getUrl([
                'width' => $size,
                'height' => $size
            ]);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl($size = 100)
    {
        $photo = $this->getPhoto();

        if ($photo) {
            return Url::getResourceUrl(
                'resized/'.$this->photoId.'/'.$size,
                [
                    Craft::$app->getResources()->dateParam => $photo->dateModified->getTimestamp()
                ]
            );
        }

        return $url = Url::getResourceUrl('defaultuserphoto');
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable()
    {
        return Craft::$app->getUser()->checkPermission('editUsers');
    }

    /**
     * Returns whether this is the current logged-in user.
     *
     * @return boolean
     */
    public function getIsCurrent()
    {
        if ($this->id) {
            $currentUser = Craft::$app->getUser()->getIdentity();

            if ($currentUser) {
                return ($this->id == $currentUser->id);
            }
        }

        return false;
    }

    /**
     * Returns whether the user has permission to perform a given action.
     *
     * @param string $permission
     *
     * @return boolean
     */
    public function can($permission)
    {
        if (Craft::$app->getEdition() >= Craft::Client) {
            if ($this->admin) {
                return true;
            }

            if ($this->id) {
                return Craft::$app->getUserPermissions()->doesUserHavePermission($this->id, $permission);
            }

            return false;
        }

        return true;
    }

    /**
     * Returns whether the user has shunned a given message.
     *
     * @param string $message
     *
     * @return boolean
     */
    public function hasShunned($message)
    {
        if ($this->id) {
            return Craft::$app->getUsers()->hasUserShunnedMessage($this->id, $message);
        }

        return false;
    }

    /**
     * Returns the time when the user will be over their cooldown period.
     *
     * @return \DateTime|null
     */
    public function getCooldownEndTime()
    {
        if ($this->locked) {
            // There was an old bug that where a user's lockoutDate could be null if they've
            // passed their cooldownDuration already, but there account status is still locked.
            // If that's the case, just let it return null as if they are past the cooldownDuration.
            if ($this->lockoutDate) {
                $cooldownEnd = clone $this->lockoutDate;
                $cooldownEnd->add(new DateInterval(Craft::$app->getConfig()->get('cooldownDuration')));

                return $cooldownEnd;
            }
        }

        return null;
    }

    /**
     * Returns the remaining cooldown time for this user, if they've entered their password incorrectly too many times.
     *
     * @return DateInterval|null
     */
    public function getRemainingCooldownTime()
    {
        if ($this->locked) {
            $currentTime = DateTimeHelper::currentUTCDateTime();
            $cooldownEnd = $this->getCooldownEndTime();

            if ($currentTime < $cooldownEnd) {
                return $currentTime->diff($cooldownEnd);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        if ($this->getIsCurrent()) {
            return Url::getCpUrl('myaccount');
        }

        if (Craft::$app->getEdition() == Craft::Client && $this->client) {
            return Url::getCpUrl('clientaccount');
        }

        if (Craft::$app->getEdition() == Craft::Pro) {
            return Url::getCpUrl('users/'.$this->id);
        }

        return false;
    }

    /**
     * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
     * logged to the `craft/storage/logs` folder as a warning.
     *
     * In addition, we check that the username does not have any whitespace in it.
     *
     * @param null    $attributes
     * @param boolean $clearErrors
     *
     * @return boolean|null
     */
    public function validate($attributes = null, $clearErrors = true)
    {
        // Don't allow whitespace in the username.
        if (preg_match('/\s+/', $this->username)) {
            $this->addError('username', Craft::t('app', 'Spaces are not allowed in the username.'));
        }

        return parent::validate($attributes, false);
    }

    /**
     * Returns the user’s preferences.
     *
     * @return array The user’s preferences.
     */
    public function getPreferences()
    {
        if ($this->_preferences === null) {
            $this->_preferences = Craft::$app->getUsers()->getUserPreferences($this->id);
        }

        return $this->_preferences;
    }

    /**
     * Returns one of the user’s preferences by its key.
     *
     * @param string $key     The preference’s key
     * @param mixed  $default The default value, if the preference hasn’t been set
     *
     * @return array The user’s preferences.
     */
    public function getPreference($key, $default = null)
    {
        $preferences = $this->getPreferences();

        return isset($preferences[$key]) ? $preferences[$key] : $default;
    }

    /**
     * Returns the user’s preferred language, if they have one.
     *
     * @return string|null The preferred language
     */
    public function getPreferredLanguage()
    {
        $language = $this->getPreference('language');

        // Make sure it's valid
        if ($language !== null && in_array($language, Craft::$app->getI18n()->getSiteLocaleIds())) {
            return $language;
        }

        return null;
    }

    /**
     * Merges new user preferences with the existing ones, and returns the result.
     *
     * @param array $preferences The new preferences
     *
     * @return array The user’s new preferences.
     */
    public function mergePreferences($preferences)
    {
        $this->_preferences = array_merge($this->getPreferences(), $preferences);

        return $this->_preferences;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements($handle, $elements)
    {
        if ($handle == 'photo') {
            $photo = isset($elements[0]) ? $elements[0] : null;
            $this->setPhoto($photo);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * Returns the user's photo.
     *
     * @return Asset|null
     */
    public function getPhoto()
    {
        if (!isset($this->_photo) && $this->photoId) {
            $this->_photo = Craft::$app->getAssets()->getAssetById($this->photoId);
        }

        return $this->_photo;
    }

    /**
     * Sets the entry's author.
     *
     * @param Asset|null $photo
     */
    public function setPhoto(Asset $photo = null)
    {
        $this->_photo = $photo;
    }

    // Private Methods
    // =========================================================================

    /**
     * Saves a new session record for the user.
     *
     * @param string $sessionToken
     *
     * @return string The new session row's UID.
     */
    private function _storeSessionToken($sessionToken)
    {
        $sessionRecord = new SessionRecord();
        $sessionRecord->userId = $this->id;
        $sessionRecord->token = $sessionToken;
        $sessionRecord->save();

        return $sessionRecord->uid;
    }

    /**
     * Finds a session token by its row's UID.
     *
     * @param string $uid
     *
     * @return string|null The session token, or `null` if it could not be found.
     */
    private function _findSessionTokenByUid($uid)
    {
        return (new Query())
            ->select('token')
            ->from('{{%sessions}}')
            ->where(['and', 'userId=:userId', 'uid=:uid'],
                [':userId' => $this->id, ':uid' => $uid])
            ->scalar();
    }

    /**
     * Validates a cookie's stored user agent against the current request's user agent string,
     * if the 'requireMatchingUserAgentForSession' config setting is enabled.
     *
     * @param string $userAgent
     *
     * @return boolean
     */
    private function _validateUserAgent($userAgent)
    {
        if (Craft::$app->getConfig()->get('requireMatchingUserAgentForSession')) {
            $requestUserAgent = Craft::$app->getRequest()->getUserAgent();

            if ($userAgent !== $requestUserAgent) {
                Craft::warning('Tried to restore session from the the identity cookie, but the saved user agent ('.$userAgent.') does not match the current request’s ('.$requestUserAgent.').', __METHOD__);

                return false;
            }
        }

        return true;
    }
}
