<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\NameTrait;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\DeleteUsers;
use craft\elements\actions\Restore;
use craft\elements\actions\SuspendUsers;
use craft\elements\actions\UnsuspendUsers;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\users\UserCondition;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\events\AuthenticateUserEvent;
use craft\events\DefineValueEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\i18n\Locale;
use craft\models\FieldLayout;
use craft\models\UserGroup;
use craft\records\User as UserRecord;
use craft\validators\DateTimeValidator;
use craft\validators\UniqueValidator;
use craft\validators\UsernameValidator;
use craft\validators\UserPasswordValidator;
use DateInterval;
use DateTime;
use DateTimeZone;
use Throwable;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\validators\InlineValidator;
use yii\validators\Validator;
use yii\web\IdentityInterface;

/**
 * User represents a user element.
 *
 * @property Asset|null $photo the user’s photo
 * @property UserGroup[] $groups the user’s groups
 * @property string $name the user’s full name or username
 * @property string|null $friendlyName the user’s first name or username
 * @property-read Address[]|null $addresses the user’s addresses
 * @property-read DateInterval|null $remainingCooldownTime the remaining cooldown time for this user, if they've entered their password incorrectly too many times
 * @property-read DateTime|null $cooldownEndTime the time when the user will be over their cooldown period
 * @property-read array $preferences the user’s preferences
 * @property-read bool $isCredentialed whether the user account can be logged into
 * @property-read bool $isCurrent whether this is the current logged-in user
 * @property-read string|null $preferredLanguage the user’s preferred language
 * @property-read string|null $preferredLocale the user’s preferred formatting locale
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class User extends Element implements IdentityInterface
{
    use NameTrait;

    /**
     * @event AuthenticateUserEvent The event that is triggered before a user is authenticated.
     *
     * If you wish to offload authentication logic, then set [[AuthenticateUserEvent::$performAuthentication]] to `false`, and set [[$authError]] to
     * something if there is an authentication error.
     */
    public const EVENT_BEFORE_AUTHENTICATE = 'beforeAuthenticate';

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s name, as returned by [[getName()]] or [[__toString()]].
     * @since 3.7.0
     */
    public const EVENT_DEFINE_NAME = 'defineName';

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s friendly name, as returned by [[getFriendlyName()]].
     * @since 3.7.0
     */
    public const EVENT_DEFINE_FRIENDLY_NAME = 'defineFriendlyName';

    public const IMPERSONATE_KEY = 'Craft.UserSessionService.prevImpersonateUserId';

    // User statuses
    // -------------------------------------------------------------------------

    /**
     * @since 4.0.0
     */
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_LOCKED = 'locked';

    // Authentication error codes
    // -------------------------------------------------------------------------

    public const AUTH_INVALID_CREDENTIALS = 'invalid_credentials';
    public const AUTH_PENDING_VERIFICATION = 'pending_verification';
    public const AUTH_ACCOUNT_LOCKED = 'account_locked';
    public const AUTH_ACCOUNT_COOLDOWN = 'account_cooldown';
    public const AUTH_PASSWORD_RESET_REQUIRED = 'password_reset_required';
    public const AUTH_ACCOUNT_SUSPENDED = 'account_suspended';
    public const AUTH_NO_CP_ACCESS = 'no_cp_access';
    public const AUTH_NO_CP_OFFLINE_ACCESS = 'no_cp_offline_access';
    public const AUTH_NO_SITE_OFFLINE_ACCESS = 'no_site_offline_access';

    // Validation scenarios
    // -------------------------------------------------------------------------

    public const SCENARIO_REGISTRATION = 'registration';
    public const SCENARIO_PASSWORD = 'password';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'User');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'user');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Users');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'users');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => [
                'label' => Craft::t('app', 'Active'),
                'color' => 'green',
            ],
            self::STATUS_PENDING => [
                'label' => Craft::t('app', 'Pending'),
                'color' => 'orange',
            ],
            self::STATUS_SUSPENDED => [
                'label' => Craft::t('app', 'Suspended'),
                'color' => 'red',
            ],
            self::STATUS_LOCKED => [
                'label' => Craft::t('app', 'Locked'),
                'color' => 'red',
            ],
            self::STATUS_INACTIVE => [
                'label' => Craft::t('app', 'Inactive'),
            ],
        ];
    }

    /**
     * @inheritdoc
     * @return UserQuery The newly created [[UserQuery]] instance.
     */
    public static function find(): UserQuery
    {
        return new UserQuery(static::class);
    }

    /**
     * @inheritdoc
     * @return UserCondition
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(UserCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('app', 'All users'),
                'hasThumbs' => true,
                'data' => [
                    'slug' => 'all',
                ],
            ],
            [
                'key' => 'admins',
                'label' => Craft::t('app', 'Admins'),
                'criteria' => ['admin' => true],
                'hasThumbs' => true,
                'data' => [
                    'slug' => 'admins',
                ],
            ],
            [
                'heading' => Craft::t('app', 'Account Type'),
            ],
            [
                'key' => 'credentialed',
                'label' => Craft::t('app', 'Credentialed'),
                'criteria' => [
                    'status' => UserQuery::STATUS_CREDENTIALED,
                ],
                'hasThumbs' => true,
                'data' => [
                    'slug' => 'credentialed',
                ],
            ],
            [
                'key' => 'inactive',
                'label' => Craft::t('app', 'Inactive'),
                'criteria' => [
                    'status' => self::STATUS_INACTIVE,
                ],
                'hasThumbs' => true,
                'data' => [
                    'slug' => 'inactive',
                ],
            ],
        ];

        $groups = Craft::$app->getUserGroups()->getAllGroups();

        if (!empty($groups)) {
            $sources[] = ['heading' => Craft::t('app', 'Groups')];

            foreach ($groups as $group) {
                $sources[] = [
                    'key' => 'group:' . $group->uid,
                    'label' => Craft::t('site', $group->name),
                    'criteria' => ['groupId' => $group->id],
                    'hasThumbs' => true,
                    'data' => [
                        'slug' => $group->handle,
                    ],
                ];
            }
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source): array
    {
        $actions = [];

        if (Craft::$app->getUser()->checkPermission('moderateUsers')) {
            // Suspend
            $actions[] = SuspendUsers::class;

            // Unsuspend
            $actions[] = UnsuspendUsers::class;
        }

        if (Craft::$app->getUser()->checkPermission('deleteUsers')) {
            // Delete
            $actions[] = DeleteUsers::class;
        }

        // Restore
        $actions[] = Restore::class;

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['username', 'fullName', 'firstName', 'lastName', 'email'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $attributes = [
                'email' => Craft::t('app', 'Email'),
                'fullName' => Craft::t('app', 'Full Name'),
                'firstName' => Craft::t('app', 'First Name'),
                'lastName' => Craft::t('app', 'Last Name'),
                [
                    'label' => Craft::t('app', 'Last Login'),
                    'orderBy' => 'lastLoginDate',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'Date Created'),
                    'orderBy' => 'dateCreated',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'Date Updated'),
                    'orderBy' => 'dateUpdated',
                    'defaultDir' => 'desc',
                ],
                'id' => Craft::t('app', 'ID'),
            ];
        } else {
            $attributes = [
                'username' => Craft::t('app', 'Username'),
                'fullName' => Craft::t('app', 'Full Name'),
                'firstName' => Craft::t('app', 'First Name'),
                'lastName' => Craft::t('app', 'Last Name'),
                'email' => Craft::t('app', 'Email'),
                [
                    'label' => Craft::t('app', 'Last Login'),
                    'orderBy' => 'lastLoginDate',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'Date Created'),
                    'orderBy' => 'dateCreated',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'Date Updated'),
                    'orderBy' => 'dateUpdated',
                    'defaultDir' => 'desc',
                ],
                'id' => Craft::t('app', 'ID'),
            ];
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'email' => ['label' => Craft::t('app', 'Email')],
            'username' => ['label' => Craft::t('app', 'Username')],
            'fullName' => ['label' => Craft::t('app', 'Full Name')],
            'firstName' => ['label' => Craft::t('app', 'First Name')],
            'lastName' => ['label' => Craft::t('app', 'Last Name')],
            'groups' => ['label' => Craft::t('app', 'Groups')],
            'preferredLanguage' => ['label' => Craft::t('app', 'Preferred Language')],
            'preferredLocale' => ['label' => Craft::t('app', 'Preferred Locale')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'lastLoginDate' => ['label' => Craft::t('app', 'Last Login')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'fullName',
            'email',
            'dateCreated',
            'lastLoginDate',
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        /** @var UserQuery $elementQuery */
        if ($attribute === 'groups') {
            $elementQuery->withGroups();
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        // Get the source element IDs
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        if ($handle == 'addresses') {
            $map = (new Query())
                ->select([
                    'source' => 'ownerId',
                    'target' => 'id',
                ])
                ->from([Table::ADDRESSES])
                ->where(['ownerId' => $sourceElementIds])
                ->all();

            return [
                'elementType' => Address::class,
                'map' => $map,
            ];
        }

        if ($handle === 'photo') {
            $map = (new Query())
                ->select(['id as source', 'photoId as target'])
                ->from([Table::USERS])
                ->where(['id' => $sourceElementIds])
                ->andWhere(['not', ['photoId' => null]])
                ->all();

            return [
                'elementType' => Asset::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'User';
    }

    // IdentityInterface Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public static function findIdentity($id): ?self
    {
        /** @var User|null $user */
        $user = self::find()
            ->addSelect(['users.password'])
            ->id($id)
            ->status(null)
            ->one();

        if ($user === null) {
            return null;
        }

        /** @var static $user */
        if ($user->getStatus() === self::STATUS_ACTIVE) {
            return $user;
        }

        // If the current user is being impersonated by an admin, ignore their status
        if ($previousUserId = Session::get(self::IMPERSONATE_KEY)) {
            /** @var self|null $previousUser */
            $previousUser = self::find()
                ->id($previousUserId)
                ->status(null)
                ->one();

            if ($previousUser && $previousUser->can('impersonateUsers')) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * @var int|null Photo asset ID
     */
    public ?int $photoId = null;

    /**
     * @var bool Active
     * @since 4.0.0
     */
    public bool $active = false;

    /**
     * @var bool Pending
     */
    public bool $pending = false;

    /**
     * @var bool Locked
     */
    public bool $locked = false;

    /**
     * @var bool Suspended
     */
    public bool $suspended = false;

    /**
     * @var bool Admin
     */
    public bool $admin = false;

    /**
     * @var string|null Username
     */
    public ?string $username = null;

    /**
     * @var string|null Email
     */
    public ?string $email = null;

    /**
     * @var string|null Password
     */
    public ?string $password = null;

    /**
     * @var DateTime|null Last login date
     */
    public ?DateTime $lastLoginDate = null;

    /**
     * @var int|null Invalid login count
     */
    public ?int $invalidLoginCount = null;

    /**
     * @var DateTime|null Last invalid login date
     */
    public ?DateTime $lastInvalidLoginDate = null;

    /**
     * @var DateTime|null Lockout date
     */
    public ?DateTime $lockoutDate = null;

    /**
     * @var bool Whether the user has a dashboard
     * @since 3.0.4
     */
    public bool $hasDashboard = false;

    /**
     * @var bool Password reset required
     */
    public bool $passwordResetRequired = false;

    /**
     * @var DateTime|null Last password change date
     */
    public ?DateTime $lastPasswordChangeDate = null;

    /**
     * @var string|null Unverified email
     */
    public ?string $unverifiedEmail = null;

    /**
     * @var string|null New password
     */
    public ?string $newPassword = null;

    /**
     * @var string|null Current password
     */
    public ?string $currentPassword = null;

    /**
     * @var DateTime|null Verification code issued date
     */
    public ?DateTime $verificationCodeIssuedDate = null;

    /**
     * @var string|null Verification code
     */
    public ?string $verificationCode = null;

    /**
     * @var string|null Last login attempt IP address.
     */
    public ?string $lastLoginAttemptIp = null;

    /**
     * @var string|null Auth error
     */
    public ?string $authError = null;

    /**
     * @var self|null The user who should take over the user’s content if the user is deleted.
     */
    public ?User $inheritorOnDelete = null;

    /**
     * @var Address[] Addresses
     * @see getAddresses()
     */
    private array $_addresses;

    /**
     * @var string|null
     * @see getName()
     * @see setName()
     */
    private ?string $_name = null;

    /**
     * @var string|bool|null
     * @see getFriendlyName()
     * @see setFriendlyName()
     */
    private string|bool|null $_friendlyName = null;

    /**
     * @var Asset|false|null user photo
     */
    private Asset|null|false $_photo = null;

    /**
     * @var UserGroup[]|null The cached list of groups the user belongs to. Set by [[getGroups()]].
     */
    private ?array $_groups = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Is this user in cooldown mode, and are they past their window?
        if (
            $this->locked &&
            Craft::$app->getConfig()->getGeneral()->cooldownDuration &&
            !$this->getRemainingCooldownTime()
        ) {
            Craft::$app->getUsers()->unlockUser($this);
        }

        // Convert IDNA ASCII to Unicode
        if ($this->username) {
            $this->username = StringHelper::idnToUtf8Email($this->username);
        }
        if ($this->email) {
            $this->email = StringHelper::idnToUtf8Email($this->email);
        }

        $this->normalizeNames();
    }

    /**
     * Use the full name or username as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        try {
            if (($name = $this->getName()) !== '') {
                return $name;
            }
        } catch (Throwable $e) {
            ErrorHandler::convertExceptionToError($e);
        }

        return parent::__toString();
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        return $this->getName() ?: ($this->email ?? $this->id ?? static::class);
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'cooldownEndTime';
        $names[] = 'friendlyName';
        $names[] = 'fullName';
        $names[] = 'isCredentialed';
        $names[] = 'isCurrent';
        $names[] = 'name';
        $names[] = 'preferredLanguage';
        $names[] = 'remainingCooldownTime';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'groups';
        $names[] = 'addresses';
        $names[] = 'photo';
        return $names;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['currentPassword'] = Craft::t('app', 'Current Password');
        $labels['email'] = Craft::t('app', 'Email');
        $labels['fullName'] = Craft::t('app', 'Full Name');
        $labels['firstName'] = Craft::t('app', 'First Name');
        $labels['lastName'] = Craft::t('app', 'Last Name');
        $labels['newPassword'] = Craft::t('app', 'New Password');
        $labels['password'] = Craft::t('app', 'Password');
        $labels['unverifiedEmail'] = Craft::t('app', 'Email');
        $labels['username'] = Craft::t('app', 'Username');
        return $labels;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $treatAsActive = fn() => $this->active || $this->pending || $this->getScenario() === self::SCENARIO_REGISTRATION;

        $rules[] = [['lastLoginDate', 'lastInvalidLoginDate', 'lockoutDate', 'lastPasswordChangeDate', 'verificationCodeIssuedDate'], DateTimeValidator::class];
        $rules[] = [['invalidLoginCount', 'photoId'], 'number', 'integerOnly' => true];
        $rules[] = [['username', 'email', 'unverifiedEmail', 'fullName', 'firstName', 'lastName'], 'trim', 'skipOnEmpty' => true];
        $rules[] = [['email', 'unverifiedEmail'], 'email', 'enableIDN' => App::supportsIdn(), 'enableLocalIDN' => false];
        $rules[] = [['email', 'username', 'fullName', 'firstName', 'lastName', 'password', 'unverifiedEmail'], 'string', 'max' => 255];
        $rules[] = [['verificationCode'], 'string', 'max' => 100];
        $rules[] = [['email'], 'required', 'when' => $treatAsActive];
        $rules[] = [['lastLoginAttemptIp'], 'string', 'max' => 45];

        if (!Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $rules[] = [['username'], 'required', 'when' => $treatAsActive];
            $rules[] = [['username'], UsernameValidator::class];
        }

        if (Craft::$app->getIsInstalled()) {
            $rules[] = [
                ['username', 'email'],
                UniqueValidator::class,
                'targetClass' => UserRecord::class,
                'caseInsensitive' => true,
            ];

            $rules[] = [['unverifiedEmail'], 'validateUnverifiedEmail'];
        }

        if (isset($this->id) && $this->passwordResetRequired) {
            // Get the current password hash
            $currentPassword = (new Query())
                ->select(['password'])
                ->from([Table::USERS])
                ->where(['id' => $this->id])
                ->scalar();
        } else {
            $currentPassword = null;
        }

        $rules[] = [
            ['newPassword'],
            UserPasswordValidator::class,
            'forceDifferent' => $this->passwordResetRequired,
            'currentPassword' => $currentPassword,
        ];

        $rules[] = [
            ['fullName', 'firstName', 'lastName'], function($attribute, $params, Validator $validator) {
                if (str_contains($this->$attribute, '://')) {
                    $validator->addError($this, $attribute, Craft::t('app', 'Invalid value “{value}”.'));
                }
            },
        ];

        return $rules;
    }

    /**
     * Returns whether the user account can be logged into.
     *
     * @return bool
     * @since 4.0.0
     */
    public function getIsCredentialed(): bool
    {
        return $this->active || $this->pending;
    }

    /**
     * Validates the unverifiedEmail value is unique.
     *
     * @param string $attribute
     * @param array|null $params
     * @param InlineValidator $validator
     */
    public function validateUnverifiedEmail(string $attribute, ?array $params, InlineValidator $validator): void
    {
        $query = self::find()
            ->status(null);

        if (Craft::$app->getDb()->getIsMysql()) {
            $query->where([
                'email' => $this->unverifiedEmail,
            ]);
        } else {
            // Postgres is case-sensitive
            $query->where([
                'lower([[email]])' => mb_strtolower($this->unverifiedEmail),
            ]);
        }

        if ($this->id) {
            $query->andWhere(['not', ['elements.id' => $this->id]]);
        }

        if ($query->exists()) {
            $validator->addError($this, $attribute, Craft::t('yii', '{attribute} "{value}" has already been taken.'), $params);
        }
    }

    /**
     * @inheritdoc
     */
    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_PASSWORD] = ['newPassword'];
        $scenarios[self::SCENARIO_REGISTRATION] = ['username', 'email', 'newPassword'];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return Craft::$app->getFields()->getLayoutByType(self::class);
    }

    /**
     * Gets the user’s addresses.
     *
     * @return Address[]
     * @since 4.0.0
     */
    public function getAddresses(): array
    {
        if (!isset($this->_addresses)) {
            if (!$this->id) {
                return [];
            }

            /** @var Address[] $addresses */
            $addresses = Address::find()
                ->ownerId($this->id)
                ->orderBy(['id' => SORT_ASC])
                ->all();
            $this->_addresses = $addresses;
        }

        return $this->_addresses;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey(): ?string
    {
        $token = Craft::$app->getUser()->getToken();

        if ($token === null) {
            throw new Exception('No user session token exists.');
        }

        $userAgent = Craft::$app->getRequest()->getUserAgent();

        // The auth key is a combination of the hashed token, its row's UID, and the user agent string
        return json_encode([
            $token,
            null,
            md5($userAgent),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey): ?bool
    {
        $data = Json::decodeIfJson($authKey);

        if (!is_array($data) || count($data) !== 3 || !isset($data[0], $data[2])) {
            return false;
        }

        [$token, , $userAgent] = $data;

        if (!$this->_validateUserAgent($userAgent)) {
            return false;
        }

        $tokenId = (new Query())
            ->select(['id'])
            ->from([Table::SESSIONS])
            ->where([
                'token' => $token,
                'userId' => $this->id,
            ])
            ->scalar();

        if (!$tokenId) {
            return false;
        }

        // Update the session row's dateUpdated value so it doesn't get GC'd
        Db::update(Table::SESSIONS, [
            'dateUpdated' => Db::prepareDateForDb(new DateTime()),
        ], ['id' => $tokenId]);

        return true;
    }

    /**
     * Determines whether the user is allowed to be logged in with a given password.
     *
     * @param string $password The user’s plain text password.
     * @return bool
     */
    public function authenticate(string $password): bool
    {
        $this->authError = null;

        // Fire a 'beforeAuthenticate' event
        $event = new AuthenticateUserEvent([
            'password' => $password,
        ]);
        $this->trigger(self::EVENT_BEFORE_AUTHENTICATE, $event);

        if (!isset($this->authError) && $event->performAuthentication) {
            // Validate the password
            try {
                $passwordValid = Craft::$app->getSecurity()->validatePassword($password, $this->password);
            } catch (InvalidArgumentException) {
                $passwordValid = false;
            }

            if ($passwordValid) {
                $this->authError = $this->_getAuthError();
            } else {
                Craft::$app->getUsers()->handleInvalidLogin($this);
                // Was that one bad password too many?
                if ($this->locked && !Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
                    // Will set the authError to either AccountCooldown or AccountLocked
                    $this->authError = $this->_getAuthError();
                } else {
                    $this->authError = self::AUTH_INVALID_CREDENTIALS;
                }
            }
        }

        return !isset($this->authError);
    }

    /**
     * Returns the reference string to this element.
     *
     * @return string|null
     */
    public function getRef(): ?string
    {
        return $this->username;
    }

    /**
     * Returns the user’s groups.
     *
     * @return UserGroup[]
     */
    public function getGroups(): array
    {
        if (isset($this->_groups)) {
            return $this->_groups;
        }

        if (Craft::$app->getEdition() !== Craft::Pro || !isset($this->id)) {
            return [];
        }

        return $this->_groups = Craft::$app->getUserGroups()->getGroupsByUserId($this->id);
    }

    /**
     * Sets an array of user groups on the user.
     *
     * @param UserGroup[] $groups An array of UserGroup objects.
     */
    public function setGroups(array $groups): void
    {
        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_groups = $groups;
        }
    }

    /**
     * Returns whether the user is in a specific group.
     *
     * @param int|string|UserGroup $group The user group model, its handle, or ID.
     * @return bool
     */
    public function isInGroup(UserGroup|int|string $group): bool
    {
        if (Craft::$app->getEdition() !== Craft::Pro) {
            return false;
        }

        if (is_object($group) && $group instanceof UserGroup) {
            $group = $group->id;
        }

        if (is_numeric($group)) {
            return in_array($group, ArrayHelper::getColumn($this->getGroups(), 'id'), false);
        }

        return in_array($group, ArrayHelper::getColumn($this->getGroups(), 'handle'), true);
    }

    /**
     * Returns the user’s full name.
     *
     * @return string|null
     * @deprecated in 4.0.0. [[fullName]] should be used instead.
     */
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * Returns the user’s full name or username.
     *
     * @return string
     */
    public function getName(): string
    {
        if (!isset($this->_name)) {
            $this->_name = $this->_defineName();
        }

        return $this->_name;
    }

    /**
     * @return string
     */
    private function _defineName(): string
    {
        if ($this->hasEventHandlers(self::EVENT_DEFINE_NAME)) {
            $this->trigger(self::EVENT_DEFINE_NAME, $event = new DefineValueEvent());
            if ($event->value !== null) {
                return $event->value;
            }
        }

        return $this->fullName ?? (string)$this->username;
    }

    /**
     * Sets the user’s name.
     *
     * @param string $name
     * @since 3.7.0
     */
    public function setName(string $name): void
    {
        $this->_name = $name;
    }

    /**
     * Returns the user’s first name or username.
     *
     * @return string|null
     */
    public function getFriendlyName(): ?string
    {
        if (!isset($this->_friendlyName)) {
            $this->_friendlyName = $this->_defineFriendlyName() ?? false;
        }

        return $this->_friendlyName ?: null;
    }

    /**
     * @return string|null
     */
    private function _defineFriendlyName(): ?string
    {
        if ($this->hasEventHandlers(self::EVENT_DEFINE_FRIENDLY_NAME)) {
            $this->trigger(self::EVENT_DEFINE_FRIENDLY_NAME, $event = new DefineValueEvent());
            if ($event->handled || $event->value !== null) {
                return $event->value;
            }
        }

        return $this->firstName ?? $this->username;
    }

    /**
     * Sets the user’s friendly name.
     *
     * @param string $friendlyName
     * @since 3.7.0
     */
    public function setFriendlyName(string $friendlyName): void
    {
        $this->_friendlyName = $friendlyName;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        // If they're disabled or archived, go with that
        $status = parent::getStatus();
        if ($status !== self::STATUS_ENABLED) {
            return $status;
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

        if ($this->active) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_INACTIVE;
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size): ?string
    {
        $photo = $this->getPhoto();

        if ($photo) {
            return Craft::$app->getAssets()->getThumbUrl($photo, $size);
        }

        return Craft::$app->getAssetManager()->getPublishedUrl('@app/web/assets/cp/dist', true, 'images/user.svg');
    }

    /**
     * @inheritdoc
     */
    public function getThumbAlt(): ?string
    {
        return $this->getPhoto()?->alt ?? $this->getName();
    }

    /**
     * @inheritdoc
     */
    public function getHasRoundedThumb(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return (
            $user->id === $this->id ||
            $user->can('editUsers')
        );
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (!$this->id) {
            return $user->can('registerUsers');
        }

        if ($user->id === $this->id) {
            return true;
        }

        return $user->can('editUsers');
    }

    /**
     * @inheritdoc
     */
    public function canDuplicate(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return (
            $user->id !== $this->id &&
            $user->can('deleteUsers')
        );
    }

    /**
     * Returns whether this is the current logged-in user.
     *
     * @return bool
     */
    public function getIsCurrent(): bool
    {
        if (!$this->id) {
            return false;
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        return $currentUser && $currentUser->id == $this->id;
    }

    /**
     * Returns whether the user has permission to perform a given action.
     *
     * @param string $permission
     * @return bool
     */
    public function can(string $permission): bool
    {
        if (Craft::$app->getEdition() === Craft::Pro) {
            if ($this->admin) {
                return true;
            }

            if (isset($this->id)) {
                return Craft::$app->getUserPermissions()->doesUserHavePermission($this->id, $permission);
            }

            return false;
        }

        return true;
    }

    /**
     * Returns whether the user is authorized to assign any user groups to users.
     *
     * @return bool
     * @since 4.0.0
     */
    public function canAssignUserGroups(): bool
    {
        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            if ($this->can("assignUserGroup:$group->uid")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the user has shunned a given message.
     *
     * @param string $message
     * @return bool
     */
    public function hasShunned(string $message): bool
    {
        if (isset($this->id)) {
            return Craft::$app->getUsers()->hasUserShunnedMessage($this->id, $message);
        }

        return false;
    }

    /**
     * Returns the time when the user will be over their cooldown period.
     *
     * @return DateTime|null
     */
    public function getCooldownEndTime(): ?DateTime
    {
        // There was an old bug that where a user’s lockoutDate could be null if they’ve
        // passed their cooldownDuration already, but there account status is still locked.
        // If that’s the case, just let it return null as if they are past the cooldownDuration.
        if ($this->locked && $this->lockoutDate) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->cooldownDuration);
            $cooldownEnd = clone $this->lockoutDate;
            $cooldownEnd->add($interval);

            return $cooldownEnd;
        }

        return null;
    }

    /**
     * Returns the remaining cooldown time for this user, if they’ve entered their password incorrectly too many times.
     *
     * @return DateInterval|null
     */
    public function getRemainingCooldownTime(): ?DateInterval
    {
        if ($this->locked) {
            $currentTime = DateTimeHelper::currentUTCDateTime();
            $cooldownEnd = $this->getCooldownEndTime()?->setTimezone(new DateTimeZone('UTC'));

            if ($cooldownEnd && $currentTime < $cooldownEnd) {
                return $currentTime->diff($cooldownEnd);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function cpEditUrl(): ?string
    {
        if (Craft::$app->getRequest()->getIsCpRequest() && $this->getIsCurrent()) {
            return UrlHelper::cpUrl('myaccount');
        }

        if (Craft::$app->getEdition() === Craft::Pro) {
            return UrlHelper::cpUrl('users/' . $this->id);
        }

        return null;
    }

    /**
     * Returns the user’s preferences.
     *
     * @return array The user’s preferences.
     */
    public function getPreferences(): array
    {
        return $this->id ? Craft::$app->getUsers()->getUserPreferences($this->id) : [];
    }

    /**
     * Returns one of the user’s preferences by its key.
     *
     * @param string $key The preference’s key
     * @param mixed $default The default value, if the preference hasn’t been set
     * @return mixed The user’s preference
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        $preferences = $this->getPreferences();

        return $preferences[$key] ?? $default;
    }

    /**
     * Returns the user’s preferred language, if they have one.
     *
     * @return string|null The preferred language
     */
    public function getPreferredLanguage(): ?string
    {
        return $this->_validateLocale($this->getPreference('language'), false);
    }

    /**
     * Returns the user’s preferred locale to be used for date/number formatting, if they have one.
     *
     * If the user doesn’t have a preferred locale, their preferred language will be used instead.
     *
     * @return string|null The preferred locale
     * @since 3.5.0
     */
    public function getPreferredLocale(): ?string
    {
        return $this->_validateLocale($this->getPreference('locale'), true);
    }

    /**
     * Validates and returns a locale ID.
     *
     * @param string|null $locale
     * @param bool $checkAllLocales Whether to check all known locale IDs, rather than just the app locales
     * @return string|null
     */
    private function _validateLocale(?string $locale, bool $checkAllLocales): ?string
    {
        $locales = $checkAllLocales ? Craft::$app->getI18n()->getAllLocaleIds() : Craft::$app->getI18n()->getAppLocaleIds();
        if ($locale && in_array($locale, $locales, true)) {
            return $locale;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements): void
    {
        if ($handle === 'photo') {
            /** @var Asset|null $photo */
            $photo = $elements[0] ?? null;
            $this->setPhoto($photo);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    /**
     * Returns the user’s photo.
     *
     * @return Asset|null
     */
    public function getPhoto(): ?Asset
    {
        if (!isset($this->_photo)) {
            if (!$this->photoId) {
                return null;
            }

            $this->_photo = Craft::$app->getAssets()->getAssetById($this->photoId) ?? false;
        }

        return $this->_photo ?: null;
    }

    /**
     * Sets the entry’s author.
     *
     * @param Asset|null $photo
     */
    public function setPhoto(?Asset $photo = null): void
    {
        $this->_photo = $photo;
        $this->photoId = $photo->id ?? null;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'email':
                return $this->email ? Html::mailto(Html::encode($this->email)) : '';

            case 'groups':
                return implode(', ', array_map(function(UserGroup $group) {
                    return Html::encode(Craft::t('site', $group->name));
                }, $this->getGroups()));

            case 'preferredLanguage':
                $language = $this->getPreferredLanguage();
                return $language ? (new Locale($language))->getDisplayName(Craft::$app->language) : '';

            case 'preferredLocale':
                $locale = $this->getPreferredLocale();
                return $locale ? (new Locale($locale))->getDisplayName(Craft::$app->language) : '';
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        return [
            'data' => [
                'suspended' => $this->suspended,
                'can-suspend' => $currentUser && Craft::$app->getUsers()->canSuspend($currentUser, $this),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function metaFieldsHtml(bool $static): string
    {
        return implode("\n", [
            Craft::$app->getView()->renderTemplate('users/_accountfields.twig', [
                'user' => $this,
                'isNewUser' => !$this->id,
                'static' => $static,
            ]),
            parent::metaFieldsHtml($static),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function statusFieldHtml(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    protected function metadata(): array
    {
        $formatter = Craft::$app->getFormatter();

        return [
            Craft::t('app', 'Email') => Html::a($this->email, "mailto:$this->email"),
            Craft::t('app', 'Cooldown Time Remaining') => function() use ($formatter) {
                if (
                    !$this->locked ||
                    !Craft::$app->getConfig()->getGeneral()->cooldownDuration ||
                    ($duration = $this->getRemainingCooldownTime()) === null
                ) {
                    return false;
                }
                return $formatter->asDuration($duration);
            },
            Craft::t('app', 'Created at') => $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT),
            Craft::t('app', 'Last login') => function() use ($formatter) {
                if ($this->pending) {
                    return false;
                }
                if (!$this->lastLoginDate) {
                    return Craft::t('app', 'Never');
                }
                return $formatter->asDatetime($this->lastLoginDate, Formatter::FORMAT_WIDTH_SHORT);
            },
            Craft::t('app', 'Last login fail') => function() use ($formatter) {
                if (!$this->locked || !$this->lastInvalidLoginDate) {
                    return false;
                }
                return $formatter->asDatetime($this->lastInvalidLoginDate, Formatter::FORMAT_WIDTH_SHORT);
            },
            Craft::t('app', 'Login fail count') => function() use ($formatter) {
                if (!$this->locked) {
                    return false;
                }
                return $formatter->asDecimal($this->invalidLoginCount, 0);
            },
        ];
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        // Get the user record
        if (!$isNew) {
            $record = UserRecord::findOne($this->id);
            $isInactive = $record->active || $record->pending;

            if (!$record) {
                throw new InvalidConfigException("Invalid user ID: $this->id");
            }

            if ($this->active != $record->active) {
                throw new Exception('Unable to change a user’s active state like this.');
            }

            if ($this->pending != $record->pending) {
                if ($isInactive) {
                    throw new Exception('Unable to change a user’s pending state like this.');
                }
                $record->pending = $this->pending;
            }

            if ($this->locked != $record->locked) {
                throw new Exception('Unable to change a user’s locked state like this.');
            }

            if ($this->suspended != $record->suspended) {
                throw new Exception('Unable to change a user’s suspended state like this.');
            }
        } else {
            $record = new UserRecord();
            $record->id = (int)$this->id;
            $record->active = $this->active;
            $record->pending = $this->pending;
            $record->locked = $this->locked;
            $record->suspended = $this->suspended;
        }

        $this->prepareNamesForSave();

        $record->photoId = (int)$this->photoId ?: null;
        $record->admin = $this->admin;
        $record->username = $this->username;
        $record->fullName = $this->fullName;
        $record->firstName = $this->firstName;
        $record->lastName = $this->lastName;
        $record->email = $this->email;
        $record->passwordResetRequired = $this->passwordResetRequired;
        $record->unverifiedEmail = $this->unverifiedEmail;

        if ($changePassword = (isset($this->newPassword))) {
            $hash = Craft::$app->getSecurity()->hashPassword($this->newPassword);
            $this->lastPasswordChangeDate = DateTimeHelper::currentUTCDateTime();

            $record->password = $this->password = $hash;
            $record->invalidLoginWindowStart = null;
            $record->invalidLoginCount = $this->invalidLoginCount = null;
            $record->verificationCode = null;
            $record->verificationCodeIssuedDate = null;
            $record->lastPasswordChangeDate = Db::prepareDateForDb($this->lastPasswordChangeDate);

            // If the user required a password reset *before this request*, then set passwordResetRequired to false
            if (!$isNew && $record->getOldAttribute('passwordResetRequired')) {
                $record->passwordResetRequired = $this->passwordResetRequired = false;
            }

            $this->newPassword = null;
        }

        // Capture the dirty attributes from the record
        $dirtyAttributes = array_keys($record->getDirtyAttributes());

        $record->save(false);

        // Make sure that the photo is located in the right place
        if (!$isNew && $this->photoId) {
            Craft::$app->getUsers()->relocateUserPhoto($this);
        }

        $this->setDirtyAttributes($dirtyAttributes);

        parent::afterSave($isNew);

        if (!$isNew && $changePassword) {
            // Destroy all other sessions for this user
            $condition = ['userId' => $this->id];
            if ($this->getIsCurrent() && $token = Craft::$app->getUser()->getToken()) {
                $condition = ['and', $condition, ['not', ['token' => $token]]];
            }
            Db::delete(Table::SESSIONS, $condition);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $elementsService = Craft::$app->getElements();

        // Do all this stuff within a transaction
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Should we transfer the content to a new user?
            if ($this->inheritorOnDelete) {
                // Invalidate all entry caches
                $elementsService->invalidateCachesForElementType(Entry::class);

                // Update the entry/version/draft tables to point to the new user
                $userRefs = [
                    Table::ENTRIES => 'authorId',
                    Table::DRAFTS => 'creatorId',
                    Table::REVISIONS => 'creatorId',
                ];

                foreach ($userRefs as $table => $column) {
                    Db::update($table, [
                        $column => $this->inheritorOnDelete->id,
                    ], [
                        $column => $this->id,
                    ], [], false);
                }
            } else {
                // Delete the entries
                $entryQuery = Entry::find()
                    ->authorId($this->id)
                    ->status(null)
                    ->site('*')
                    ->unique();

                foreach (Db::each($entryQuery) as $entry) {
                    /** @var Entry $entry */
                    $elementsService->deleteElement($entry);
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Validates a cookie's stored user agent against the current request's user agent string,
     * if the 'requireMatchingUserAgentForSession' config setting is enabled.
     *
     * @param string $userAgent
     * @return bool
     */
    private function _validateUserAgent(string $userAgent): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->requireMatchingUserAgentForSession) {
            return true;
        }

        $requestUserAgent = Craft::$app->getRequest()->getUserAgent();

        if (!hash_equals($userAgent, md5($requestUserAgent))) {
            Craft::warning('Tried to restore session from the the identity cookie, but the saved user agent (' . $userAgent . ') does not match the current request’s (' . $requestUserAgent . ').', __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * Returns the [[authError]] value for [[authenticate()]]
     *
     * @return null|string
     */
    private function _getAuthError(): ?string
    {
        switch ($this->getStatus()) {
            case self::STATUS_INACTIVE:
            case self::STATUS_ARCHIVED:
                return self::AUTH_INVALID_CREDENTIALS;
            case self::STATUS_PENDING:
                return self::AUTH_PENDING_VERIFICATION;
            case self::STATUS_SUSPENDED:
                return self::AUTH_ACCOUNT_SUSPENDED;
            case self::STATUS_ACTIVE:
                if ($this->locked) {
                    // Let them know how much time they have to wait (if any) before their account is unlocked.
                    if (Craft::$app->getConfig()->getGeneral()->cooldownDuration) {
                        return self::AUTH_ACCOUNT_COOLDOWN;
                    }
                    return self::AUTH_ACCOUNT_LOCKED;
                }
                // Is a password reset required?
                if ($this->passwordResetRequired) {
                    return self::AUTH_PASSWORD_RESET_REQUIRED;
                }
                $request = Craft::$app->getRequest();
                if (!$request->getIsConsoleRequest()) {
                    if ($request->getIsCpRequest()) {
                        if (!$this->can('accessCp')) {
                            return self::AUTH_NO_CP_ACCESS;
                        }
                        if (
                            Craft::$app->getIsLive() === false &&
                            $this->can('accessCpWhenSystemIsOff') === false
                        ) {
                            return self::AUTH_NO_CP_OFFLINE_ACCESS;
                        }
                    } elseif (
                        Craft::$app->getIsLive() === false &&
                        $this->can('accessSiteWhenSystemIsOff') === false
                    ) {
                        return self::AUTH_NO_SITE_OFFLINE_ACCESS;
                    }
                }
        }

        return null;
    }
}
