<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\dates\DateInterval;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\elements\actions\DeleteUsers;
use craft\app\elements\actions\Edit;
use craft\app\elements\actions\SuspendUsers;
use craft\app\elements\actions\UnsuspendUsers;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\db\UserQuery;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\HtmlHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\UserGroup;
use craft\app\records\Session as SessionRecord;
use Exception;
use yii\base\ErrorHandler;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * User represents a user element.
 *
 * @property string|null $preferredLocale The user’s preferred locale
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends Element implements IdentityInterface
{
	// Constants
	// =========================================================================

	const STATUS_ACTIVE    = 'active';
	const STATUS_LOCKED    = 'locked';
	const STATUS_SUSPENDED = 'suspended';
	const STATUS_PENDING   = 'pending';
	const STATUS_ARCHIVED  = 'archived';

	const AUTH_INVALID_CREDENTIALS     = 'invalid_credentials';
	const AUTH_PENDING_VERIFICATION    = 'pending_verification';
	const AUTH_ACCOUNT_LOCKED          = 'account_locked';
	const AUTH_ACCOUNT_COOLDOWN        = 'account_cooldown';
	const AUTH_PASSWORD_RESET_REQUIRED = 'password_reset_required';
	const AUTH_ACCOUNT_SUSPENDED       = 'account_suspended';
	const AUTH_NO_CP_ACCESS            = 'no_cp_access';
	const AUTH_NO_CP_OFFLINE_ACCESS    = 'no_cp_offline_access';
	const AUTH_USERNAME_INVALID        = 'username_invalid';

	const IMPERSONATE_KEY = 'Craft.UserSessionService.prevImpersonateUserId';

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
	 * @return bool
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
			self::STATUS_ACTIVE    => Craft::t('app', 'Active'),
			self::STATUS_PENDING   => Craft::t('app', 'Pending'),
			self::STATUS_LOCKED    => Craft::t('app', 'Locked'),
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

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			foreach (Craft::$app->getUserGroups()->getAllGroups() as $group)
			{
				$key = 'group:'.$group->id;

				$sources[$key] = [
					'label'     => Craft::t('app', $group->name),
					'criteria'  => ['groupId' => $group->id],
					'hasThumbs' => true
				];
			}
		}

		// Allow plugins to modify the sources
		Craft::$app->getPlugins()->call('modifyUserSources', [&$sources, $context]);

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
			'type'  => Edit::className(),
			'label' => Craft::t('app', 'Edit user'),
		]);

		if (Craft::$app->getUser()->checkPermission('administrateUsers'))
		{
			// Suspend
			$actions[] = SuspendUsers::className();

			// Unsuspend
			$actions[] = UnsuspendUsers::className();
		}

		if (Craft::$app->getUser()->checkPermission('deleteUsers'))
		{
			// Delete
			$actions[] = DeleteUsers::className();
		}

		// Allow plugins to add additional actions
		$allPluginActions = Craft::$app->getPlugins()->call('addUserActions', [$source], true);

		foreach ($allPluginActions as $pluginActions)
		{
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
		if (Craft::$app->getConfig()->get('useEmailAsUsername'))
		{
			$attributes = [
				'email'         => Craft::t('app', 'Email'),
				'firstName'     => Craft::t('app', 'First Name'),
				'lastName'      => Craft::t('app', 'Last Name'),
				'dateCreated'   => Craft::t('app', 'Join Date'),
				'lastLoginDate' => Craft::t('app', 'Last Login'),
			];
		}
		else
		{
			$attributes = [
				'username'      => Craft::t('app', 'Username'),
				'firstName'     => Craft::t('app', 'First Name'),
				'lastName'      => Craft::t('app', 'Last Name'),
				'email'         => Craft::t('app', 'Email'),
				'dateCreated'   => Craft::t('app', 'Join Date'),
				'lastLoginDate' => Craft::t('app', 'Last Login'),
			];
		}

		// Allow plugins to modify the attributes
		Craft::$app->getPlugins()->call('modifyUserSortableAttributes', [&$attributes]);

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public static function defineTableAttributes($source = null)
	{
		if (Craft::$app->getConfig()->get('useEmailAsUsername'))
		{
			$attributes = [
				'email'         => Craft::t('app', 'Email'),
				'firstName'     => Craft::t('app', 'First Name'),
				'lastName'      => Craft::t('app', 'Last Name'),
				'dateCreated'   => Craft::t('app', 'Join Date'),
				'lastLoginDate' => Craft::t('app', 'Last Login'),
			];
		}
		else
		{
			$attributes = [
				'username'      => Craft::t('app', 'Username'),
				'firstName'     => Craft::t('app', 'First Name'),
				'lastName'      => Craft::t('app', 'Last Name'),
				'email'         => Craft::t('app', 'Email'),
				'dateCreated'   => Craft::t('app', 'Join Date'),
				'lastLoginDate' => Craft::t('app', 'Last Login'),
			];
		}

		// Allow plugins to modify the attributes
		Craft::$app->getPlugins()->call('modifyUserTableAttributes', [&$attributes, $source]);

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public static function getTableAttributeHtml(ElementInterface $element, $attribute)
	{
		/** @var User $element */
		// First give plugins a chance to set this
		$pluginAttributeHtml = Craft::$app->getPlugins()->callFirst('getUserTableAttributeHtml', [$element, $attribute], true);

		if ($pluginAttributeHtml !== null)
		{
			return $pluginAttributeHtml;
		}

		switch ($attribute)
		{
			case 'email':
			{
				$email = $element->email;

				if ($email)
				{
					return HtmlHelper::encodeParams('<a href="mailto:{email}">{email}</a>', [
						'email' => $email
					]);
				}
				else
				{
					return '';
				}
			}

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function getElementQueryStatusCondition(ElementQueryInterface $query, $status)
	{
		switch ($status)
		{
			case self::STATUS_ACTIVE:
			{
				return 'users.archived = 0 AND users.suspended = 0 AND users.locked = 0 and users.pending = 0';
			}

			case self::STATUS_PENDING:
			{
				return 'users.pending = 1';
			}

			case self::STATUS_LOCKED:
			{
				return 'users.locked = 1';
			}

			case self::STATUS_SUSPENDED:
			{
				return 'users.suspended = 1';
			}

			case self::STATUS_ARCHIVED:
			{
				return 'users.archived = 1';
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public static function getEditorHtml(ElementInterface $element)
	{
		/** @var User $element */
		$html = Craft::$app->getView()->renderTemplate('users/_accountfields', [
			'account'      => $element,
			'isNewAccount' => false,
		]);

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	/**
	 * @inheritdoc Element::saveElement()
	 *
	 * @return bool
	 */
	public static function saveElement(ElementInterface $element, $params)
	{
		/** @var User $element */
		if (isset($params['username']))
		{
			$element->username = $params['username'];
		}

		if (isset($params['firstName']))
		{
			$element->firstName = $params['firstName'];
		}

		if (isset($params['lastName']))
		{
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
		if ($model->locked)
		{
			$cooldownDuration = Craft::$app->getConfig()->get('cooldownDuration');

			if ($cooldownDuration)
			{
				if (!$model->getRemainingCooldownTime())
				{
					Craft::$app->getUsers()->unlockUser($model);
				}
			}
		}
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

		if ($user !== null)
		{
			if ($user->getStatus() == self::STATUS_ACTIVE)
			{
				return $user;
			}
			else
			{
				// If the previous user was an admin and we're impersonating the current user.
				if ($previousUserId = Craft::$app->getSession()->get(self::IMPERSONATE_KEY))
				{
					$previousUser = Craft::$app->getUsers()->getUserById($previousUserId);

					if ($previousUser && $previousUser->admin)
					{
						return $user;
					}
				}
			}
		}
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

		if (count($data) === 3 && isset($data[0], $data[1], $data[2]))
		{
			return $data;
		}
	}

	// Properties
	// =========================================================================

	/**
	 * @var string Username
	 */
	public $username;

	/**
	 * @var string Photo
	 */
	public $photo;

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
	 * @var string Auth error
	 */
	public $authError;

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
	public function __toString()
	{
		try
		{
			if (Craft::$app->getConfig()->get('useEmailAsUsername'))
			{
				return $this->email;
			}
			else
			{
				return $this->username;
			}
		}
		catch (Exception $e)
		{
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

		$rules[] = [['lastLoginDate'], 'craft\\app\\validators\\DateTime'];
		$rules[] = [['invalidLoginCount'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true];
		$rules[] = [['lastInvalidLoginDate'], 'craft\\app\\validators\\DateTime'];
		$rules[] = [['lockoutDate'], 'craft\\app\\validators\\DateTime'];
		$rules[] = [['lastPasswordChangeDate'], 'craft\\app\\validators\\DateTime'];
		$rules[] = [['verificationCodeIssuedDate'], 'craft\\app\\validators\\DateTime'];
		$rules[] = [['email', 'unverifiedEmail'], 'email'];
		$rules[] = [['email', 'unverifiedEmail'], 'string', 'min' => 5];
		$rules[] = [['username', 'photo'], 'string', 'max' => 100];
		$rules[] = [['email', 'unverifiedEmail'], 'string', 'max' => 255];

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

		if ($data)
		{
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
	 * @return bool
	 */
	public function authenticate($password)
	{
		switch ($this->getStatus())
		{
			case self::STATUS_ARCHIVED:
			{
				$this->authError = self::AUTH_INVALID_CREDENTIALS;
				return false;
			}

			case self::STATUS_PENDING:
			{
				$this->authError = self::AUTH_PENDING_VERIFICATION;
				return false;
			}

			case self::STATUS_SUSPENDED:
			{
				$this->authError = self::AUTH_ACCOUNT_SUSPENDED;
				return false;
			}

			case self::STATUS_LOCKED:
			{
				if (Craft::$app->getConfig()->get('cooldownDuration'))
				{
					$this->authError = self::AUTH_ACCOUNT_COOLDOWN;
				}
				else
				{
					$this->authError = self::AUTH_ACCOUNT_LOCKED;
				}
				return false;
			}

			case self::STATUS_ACTIVE:
			{
				// Validate the password
				if (!Craft::$app->getSecurity()->validatePassword($password, $this->password))
				{
					Craft::$app->getUsers()->handleInvalidLogin($this);

					// Was that one bad password too many?
					if ($this->locked)
					{
						// Will set the authError to either AccountCooldown or AccountLocked
						return $this->authenticate($password);
					}
					else
					{
						$this->authError = self::AUTH_INVALID_CREDENTIALS;
						return false;
					}
				}

				// Is a password reset required?
				if ($this->passwordResetRequired)
				{
					$this->authError = self::AUTH_PASSWORD_RESET_REQUIRED;
					return false;
				}

				$request = Craft::$app->getRequest();

				if (!$request->getIsConsoleRequest() && $request->getIsCpRequest())
				{
					if (!$this->can('accessCp'))
					{
						$this->authError = self::AUTH_NO_CP_ACCESS;
						return false;
					}

					if (!Craft::$app->isSystemOn() && !$this->can('accessCpWhenSystemIsOff'))
					{
						$this->authError = self::AUTH_NO_CP_OFFLINE_ACCESS;
						return false;
					}
				}

				return true;
			}
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
		if (!isset($this->_groups))
		{
			if (Craft::$app->getEdition() == Craft::Pro)
			{
				$this->_groups = Craft::$app->getUserGroups()->getGroupsByUserId($this->id);
			}
			else
			{
				$this->_groups = [];
			}
		}

		if (!$indexBy)
		{
			$groups = $this->_groups;
		}
		else
		{
			$groups = [];

			foreach ($this->_groups as $group)
			{
				$groups[$group->$indexBy] = $group;
			}
		}

		return $groups;
	}

	/**
	 * Returns whether the user is in a specific group.
	 *
	 * @param mixed $group The user group model, its handle, or ID.
	 *
	 * @return bool
	 */
	public function isInGroup($group)
	{
		if (Craft::$app->getEdition() == Craft::Pro)
		{
			if (is_object($group) && $group instanceof UserGroup)
			{
				$group = $group->id;
			}

			if (is_numeric($group))
			{
				$groups = array_keys($this->getGroups('id'));
			}
			else if (is_string($group))
			{
				$groups = array_keys($this->getGroups('handle'));
			}

			if (!empty($groups))
			{
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

		if ($fullName)
		{
			return $fullName;
		}
		else
		{
			return $this->username;
		}
	}

	/**
	 * Gets the user's first name or username.
	 *
	 * @return string|null
	 */
	public function getFriendlyName()
	{
		if ($firstName = trim($this->firstName))
		{
			return $firstName;
		}
		else
		{
			return $this->username;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getStatus()
	{
		if ($this->locked)
		{
			return self::STATUS_LOCKED;
		}

		if ($this->suspended)
		{
			return self::STATUS_SUSPENDED;
		}

		if ($this->archived)
		{
			return self::STATUS_ARCHIVED;
		}

		if ($this->pending)
		{
			return self::STATUS_PENDING;
		}

		return self::STATUS_ACTIVE;
	}

	/**
	 * Sets a user's status to active.
	 *
	 * @return null
	 */
	public function setActive()
	{
		$this->pending = false;
		$this->archived = false;
	}

	/**
	 * Returns the URL to the user's photo.
	 *
	 * @param int $size
	 *
	 * @return string|null
	 */
	public function getPhotoUrl($size = 100)
	{
		if ($this->photo)
		{
			return UrlHelper::getResourceUrl('userphotos/'.$this->username.'/'.$size.'/'.$this->photo);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getThumbUrl($size = 100)
	{
		$url = $this->getPhotoUrl($size);
		if (!$url)
		{
			$url = UrlHelper::getResourceUrl('defaultuserphoto/'.$size);
		}

		return $url;
	}

	/**
	 * @inheritdoc
	 */
	public function isEditable()
	{
		return Craft::$app->getUser()->checkPermission('editUsers');
	}

	/**
	 * Returns whether this is the current logged-in user.
	 *
	 * @return bool
	 */
	public function isCurrent()
	{
		if ($this->id)
		{
			$currentUser = Craft::$app->getUser()->getIdentity();

			if ($currentUser)
			{
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
	 * @return bool
	 */
	public function can($permission)
	{
		if (Craft::$app->getEdition() == Craft::Pro)
		{
			if ($this->admin || $this->client)
			{
				return true;
			}
			else if ($this->id)
			{
				return Craft::$app->getUserPermissions()->doesUserHavePermission($this->id, $permission);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}

	/**
	 * Returns whether the user has shunned a given message.
	 *
	 * @param string $message
	 *
	 * @return bool
	 */
	public function hasShunned($message)
	{
		if ($this->id)
		{
			return Craft::$app->getUsers()->hasUserShunnedMessage($this->id, $message);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the time when the user will be over their cooldown period.
	 *
	 * @return DateTime|null
	 */
	public function getCooldownEndTime()
	{
		if ($this->locked)
		{
			// There was an old bug that where a user's lockoutDate could be null if they've
			// passed their cooldownDuration already, but there account status is still locked.
			// If that's the case, just let it return null as if they are past the cooldownDuration.
			if ($this->lockoutDate)
			{
				$cooldownEnd = clone $this->lockoutDate;
				$cooldownEnd->add(new DateInterval(Craft::$app->getConfig()->get('cooldownDuration')));

				return $cooldownEnd;
			}
		}
	}

	/**
	 * Returns the remaining cooldown time for this user, if they've entered their password incorrectly too many times.
	 *
	 * @return DateInterval|null
	 */
	public function getRemainingCooldownTime()
	{
		if ($this->locked)
		{
			$currentTime = DateTimeHelper::currentUTCDateTime();
			$cooldownEnd = $this->getCooldownEndTime();

			if ($currentTime < $cooldownEnd)
			{
				return $currentTime->diff($cooldownEnd);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getCpEditUrl()
	{
		if ($this->isCurrent())
		{
			return UrlHelper::getCpUrl('myaccount');
		}
		else if (Craft::$app->getEdition() == Craft::Client && $this->client)
		{
			return UrlHelper::getCpUrl('clientaccount');
		}
		else if (Craft::$app->getEdition() == Craft::Pro)
		{
			return UrlHelper::getCpUrl('users/'.$this->id);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
	 * logged to the `craft/storage/logs` folder as a warning.
	 *
	 * In addition, we check that the username does not have any whitespace in it.
	 *
	 * @param null $attributes
	 * @param bool $clearErrors
	 *
	 * @return bool|null
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		// Don't allow whitespace in the username.
		if (preg_match('/\s+/', $this->username))
		{
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
		if ($this->_preferences === null)
		{
			$this->_preferences = Craft::$app->getUsers()->getUserPreferences($this->id);
		}

		return $this->_preferences;
	}

	/**
	 * Returns one of the user’s preferences by its key.
	 *
	 * @param string $key The preference’s key
	 * @param mixed $default The default value, if the preference hasn’t been set
	 * @return array The user’s preferences.
	 */
	public function getPreference($key, $default = null)
	{
		$preferences = $this->getPreferences();
		return isset($preferences[$key]) ? $preferences[$key] : $default;
	}

	/**
	 * Returns the user’s preferred locale, if they have one.
	 *
	 * @return string|null The preferred locale
	 */
	public function getPreferredLocale()
	{
		$locale = $this->getPreference('locale');

		// Make sure it's valid
		if ($locale !== null && in_array($locale, Craft::$app->getI18n()->getSiteLocaleIds()))
		{
			return $locale;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Merges new user preferences with the existing ones, and returns the result.
	 *
	 * @param array $preferences The new preferences
	 * @return array The user’s new preferences.
	 */
	public function mergePreferences($preferences)
	{
		$this->_preferences = array_merge($this->getPreferences(), $preferences);
		return $this->_preferences;
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
			->where(['and', 'userId=:userId', 'uid=:uid'], [':userId' => $this->id, ':uid' => $uid])
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
		if (Craft::$app->getConfig()->get('requireMatchingUserAgentForSession'))
		{
			$requestUserAgent = Craft::$app->getRequest()->getUserAgent();

			if ($userAgent !== $requestUserAgent)
			{
				Craft::warning('Tried to restore session from the the identity cookie, but the saved user agent ('.$userAgent.') does not match the current request’s ('.$requestUserAgent.').', __METHOD__);
				return false;
			}
		}

		return true;
	}
}
