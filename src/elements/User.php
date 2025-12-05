<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\NameTrait;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\DeleteUsers;
use craft\elements\actions\Restore;
use craft\elements\actions\SuspendUsers;
use craft\elements\actions\UnsuspendUsers;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\users\UserCondition;
use craft\elements\db\AddressQuery;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\enums\CmsEdition;
use craft\enums\Color;
use craft\enums\MenuItemType;
use craft\enums\PropagationMethod;
use craft\events\AuthenticateUserEvent;
use craft\events\DefineValueEvent;
use craft\fieldlayoutelements\users\FullNameField;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\models\FieldLayout;
use craft\models\Site;
use craft\models\UserGroup;
use craft\records\User as UserRecord;
use craft\records\WebAuthn as WebAuthnRecord;
use craft\validators\DateTimeValidator;
use craft\validators\UniqueValidator;
use craft\validators\UsernameValidator;
use craft\validators\UserPasswordValidator;
use craft\web\View;
use DateInterval;
use DateTime;
use DateTimeZone;
use Throwable;
use Webauthn\PublicKeyCredentialRequestOptions;
use yii\base\ErrorHandler;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\validators\InlineValidator;
use yii\validators\RequiredValidator;
use yii\validators\Validator;
use yii\web\BadRequestHttpException;
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
     * @since 5.0.0
     */
    public const GQL_TYPE_NAME = 'User';

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

    /**
     * @deprecated in 5.6.0. [[\craft\web\User:getImpersonatorId()]] should be used instead.
     */
    public const IMPERSONATE_KEY = '__impersonator_id';

    /**
     * @event RegisterUserActionsEvent The event that is triggered when a user’s available actions are being registered
     * @deprecated in 5.6.0
     */
    public const EVENT_REGISTER_USER_ACTIONS = 'registerUserActions';

    private static array $photoColors = [
        'red',
        'orange',
        'amber',
        'yellow',
        'lime',
        'green',
        'emerald',
        'teal',
        'cyan',
        'sky',
        'blue',
        'indigo',
        'violet',
        'purple',
        'fuchsia',
        'pink',
        'rose',
    ];

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

    /**
     * @since 4.4.8
     */
    public const SCENARIO_ACTIVATION = 'activation';
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
    public static function hasThumbs(): bool
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
            ],
            self::STATUS_PENDING => [
                'label' => Craft::t('app', 'Pending'),
                'color' => Color::Orange,
            ],
            self::STATUS_SUSPENDED => [
                'label' => Craft::t('app', 'Suspended'),
                'color' => Color::Red,
            ],
            self::STATUS_LOCKED => [
                'label' => Craft::t('app', 'Locked'),
                'color' => Color::Red,
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
        ];

        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
            $sources = array_merge($sources, [
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
            ]);

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
            ];
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return array_merge(parent::defineTableAttributes(), array_filter([
            'email' => ['label' => Craft::t('app', 'Email')],
            'username' => ['label' => Craft::t('app', 'Username')],
            'fullName' => ['label' => Craft::t('app', 'Full Name')],
            'firstName' => ['label' => Craft::t('app', 'First Name')],
            'lastName' => ['label' => Craft::t('app', 'Last Name')],
            'groups' => ['label' => Craft::t('app', 'Groups')],
            'affiliatedSite' => Craft::$app->getIsMultiSite() ? ['label' => Craft::t('app', 'Affiliated Site')] : null,
            'preferredLanguage' => ['label' => Craft::t('app', 'Preferred Language')],
            'preferredLocale' => ['label' => Craft::t('app', 'Preferred Locale')],
            'lastLoginDate' => ['label' => Craft::t('app', 'Last Login')],
            'isCredentialed' => ['label' => Craft::t('app', 'Credentialed')],
            'is2faEnabled' => ['label' => Craft::t('app', 'Two-Step Verification')],
        ]));
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'status',
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
    protected static function defineCardAttributes(): array
    {
        $i18n = Craft::$app->getI18n();

        return array_merge(parent::defineCardAttributes(), [
            'email' => [
                'label' => Craft::t('app', 'Email'),
                'placeholder' => fn() => 'test@example.com',
            ],
            'username' => [
                'label' => Craft::t('app', 'Username'),
                'placeholder' => fn() => Craft::t('app', 'Username'),
            ],
            'firstName' => [
                'label' => Craft::t('app', 'First Name'),
                'placeholder' => fn() => Craft::t('app', 'First Name'),
            ],
            'lastName' => [
                'label' => Craft::t('app', 'Last Name'),
                'placeholder' => fn() => Craft::t('app', 'Last Name'),
            ],
            'fullName' => [
                'label' => Craft::t('app', 'Full Name'),
                'placeholder' => Craft::t('app', 'Full Name'),
            ],
            'groups' => [
                'label' => Craft::t('app', 'Groups'),
                'placeholder' => fn() => Craft::t('app', 'Group Name'),
            ],
            'affiliatedSite' => [
                'label' => Craft::t('app', 'Affiliated Site'),
                'placeholder' => fn() => Craft::t('app', 'Site Name'),
            ],
            'preferredLanguage' => [
                'label' => Craft::t('app', 'Preferred Language'),
                'placeholder' => fn() => $i18n->getLocaleById('en')->getDisplayName(Craft::$app->language),
            ],
            'preferredLocale' => [
                'label' => Craft::t('app', 'Preferred Locale'),
                'placeholder' => fn() => $i18n->getLocaleById('en-US')->getDisplayName(Craft::$app->language),
            ],
            'isCredentialed' => [
                'label' => Craft::t('app', 'Credentialed'),
                'placeholder' => fn() => Template::raw(Cp::statusLabelHtml([
                    'color' => Color::Teal,
                    'label' => Craft::t('app', 'Credentialed'),
                    'icon' => 'check',
                ])),
            ],
            'lastLoginDate' => [
                'label' => Craft::t('app', 'Last Login'),
                'placeholder' => fn() => (new \DateTime())->sub(new \DateInterval('P14D')),
            ],
            'is2faEnabled' => [
                'label' => Craft::t('app', 'Two-Step Verification'),
                'placeholder' => fn() => Template::raw(Cp::statusLabelHtml([
                    'color' => Color::Teal,
                    'label' => Craft::t('app', 'Two-Step Verification'),
                    'icon' => 'check',
                ])),
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);

        if ($handle == 'addresses') {
            $map = (new Query())
                ->select([
                    'source' => 'primaryOwnerId',
                    'target' => 'id',
                ])
                ->from([Table::ADDRESSES])
                ->where(['primaryOwnerId' => $sourceElementIds])
                ->all();

            return [
                'elementType' => Address::class,
                'map' => $map,
                'createElement' => fn(AddressQuery $query, array $result, self $source) =>
                    // set the addresses' owners to the source user elements
                    // (must get set before behaviors - see https://github.com/craftcms/cms/issues/13400)
                    $query->createElement(['owner' => $source] + $result),
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

        // Only accept active users, unless they're being impersonated
        if (
            $user->getStatus() !== self::STATUS_ACTIVE &&
            !Craft::$app->getUser()->getImpersonator()
        ) {
            return null;
        }

        return $user;
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
     * @var int|null Affiliated site ID
     * @since 5.6.0
     */
    public ?int $affiliatedSiteId = null;

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
     * @var ElementCollection<Address> Addresses
     * @see getAddresses()
     */
    private ElementCollection $_addresses;

    /**
     * @see getAddressManager()
     */
    private NestedElementManager $_addressManager;

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

        if (empty($this->username) && Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $this->username = $this->email;
        }

        if ($this->password === '') {
            $this->password = null;
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
    public function getPostEditUrl(): ?string
    {
        if (
            Craft::$app->edition === CmsEdition::Solo ||
            !Craft::$app->getUser()->checkPermission('editUsers')
        ) {
            return null;
        }

        return 'users';
    }

    /**
     * @inheritdoc
     */
    protected function crumbs(): array
    {
        if (Craft::$app->edition === CmsEdition::Solo) {
            return [];
        }

        return [
            [
                'label' => Craft::t('app', 'Users'),
                'url' => 'users',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        return $this->getName() ?: $this->email;
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
    public function afterValidate(): void
    {
        $scenario = $this->getScenario();

        if ($scenario === self::SCENARIO_LIVE) {
            $fullNameElement = $this->getFieldLayout()->getFirstVisibleElementByType(FullNameField::class, $this);
            if ($fullNameElement && $fullNameElement->required) {
                if (Craft::$app->getConfig()->getGeneral()->showFirstAndLastNameFields) {
                    (new RequiredValidator(['attributes' => ['firstName', 'lastName']]))->validateAttributes($this, ['firstName', 'lastName']);
                } else {
                    (new RequiredValidator())->validateAttribute($this, 'fullName');
                }
            }
        }

        parent::afterValidate();
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $treatAsActive = fn() => $this->getIsCredentialed() || in_array($this->getScenario(), [
            self::SCENARIO_REGISTRATION,
            self::SCENARIO_ACTIVATION,
        ]);

        $rules[] = [['lastLoginDate', 'lastInvalidLoginDate', 'lockoutDate', 'lastPasswordChangeDate', 'verificationCodeIssuedDate'], DateTimeValidator::class];
        $rules[] = [['invalidLoginCount', 'photoId', 'affiliatedSiteId'], 'number', 'integerOnly' => true];
        $rules[] = [['username', 'email', 'unverifiedEmail', 'fullName', 'firstName', 'lastName'], 'trim', 'skipOnEmpty' => true];
        $rules[] = [['email', 'unverifiedEmail'], 'email', 'enableIDN' => App::supportsIdn(), 'enableLocalIDN' => false];
        $rules[] = [['email', 'username', 'fullName', 'firstName', 'lastName', 'password', 'unverifiedEmail'], 'string', 'max' => 255];
        $rules[] = [['verificationCode'], 'string', 'max' => 100];
        $rules[] = [['email'], 'required', 'when' => fn() => !$this->getIsDraft()];
        $rules[] = [['lastLoginAttemptIp'], 'string', 'max' => 45];

        if (!Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $rules[] = [['username'], 'required', 'when' => $treatAsActive];
            $rules[] = [['username'], UsernameValidator::class];
        }

        if (Craft::$app->getIsInstalled()) {
            $rules[] = [
                ['email'],
                UniqueValidator::class,
                'targetClass' => UserRecord::class,
                'caseInsensitive' => true,
                'filter' => ['or', ['active' => true], ['pending' => true]],
                'when' => $treatAsActive,
            ];

            if (!Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
                $rules[] = [
                    ['username'],
                    UniqueValidator::class,
                    'targetClass' => UserRecord::class,
                    'caseInsensitive' => true,
                    'filter' => ['or', ['active' => true], ['pending' => true]],
                    'when' => $treatAsActive,
                ];
            }

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
            ['fullName', 'firstName', 'lastName', 'username'], function($attribute, $params, Validator $validator) {
                if (str_contains($this->$attribute, '://')) {
                    $validator->addError($this, $attribute, Craft::t('app', 'Invalid value “{value}”.'));
                }
            },
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */

    public function safeAttributes(): array
    {
        return ArrayHelper::withoutValue(parent::safeAttributes(), 'photoId');
    }

    /**
     * @inheritdoc
     */
    public function setAttributesFromRequest($values): void
    {
        unset($values['unverifiedEmail']);

        if (isset($values['email'])) {
            $values['email'] = trim($values['email']);
            if ($values['email'] === '' || $values['email'] === $this->email) {
                unset($values['email']);
            }
        }

        if (isset($values['email'])) {
            // make sure they have an elevated session
            $userSession = Craft::$app->getUser();
            if (!$userSession->getHasElevatedSession()) {
                throw new BadRequestHttpException(Craft::t('app', 'An elevated session is required to change a user’s email.'));
            }

            if ($this->email !== null) {
                // are they allowed to set the email?
                if ($this->getIsCurrent() || $userSession->checkPermission('administrateUsers')) {
                    if (
                        Craft::$app->edition->value >= CmsEdition::Pro->value &&
                        Craft::$app->getProjectConfig()->get('users.requireEmailVerification') &&
                        !$userSession->checkPermission('administrateUsers')
                    ) {
                        // set it as the unverified email instead, and
                        $values['unverifiedEmail'] = ArrayHelper::remove($values, 'email');
                    }
                } else {
                    unset($values['email']);
                }
            }
        }

        parent::setAttributesFromRequest($values);
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true): void
    {
        if (array_key_exists('firstName', $values) || array_key_exists('lastName', $values)) {
            // Unset fullName so NameTrait::prepareNamesForSave() can set it
            $this->fullName = null;
        } elseif (array_key_exists('fullName', $values)) {
            // Unset firstName and lastName so NameTrait::prepareNamesForSave() can set them
            $this->firstName = $this->lastName = null;
        }

        parent::setAttributes($values, $safeOnly);
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
     * Returns whether the user has a password.
     *
     * @return bool
     * @since 5.6.0
     */
    public function getHasPassword(): bool
    {
        if (isset($this->password)) {
            return true;
        }

        return (bool)(new Query())
            ->select('password')
            ->from(Table::USERS)
            ->where(['id' => $this->id])
            ->scalar();
    }

    /**
     * Returns whether the user has an associated SSO identity.
     *
     * @return bool
     * @since 5.7.8
     */
    public function getHasSsoIdentity(): bool
    {
        if (Craft::$app->edition->value < CmsEdition::Enterprise->value) {
            return false;
        }

        return Craft::$app->getSso()->identityExists($this->id);
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

        $query->andWhere(['or', ['active' => true], ['pending' => true]]);

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
        $scenarios[self::SCENARIO_ACTIVATION] = ['username', 'email'];

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
     * @return ElementCollection<Address>
     * @since 4.0.0
     */
    public function getAddresses(): ElementCollection
    {
        if (!isset($this->_addresses)) {
            if (!$this->id) {
                /** @var ElementCollection<Address> */
                return ElementCollection::make();
            }

            $this->_addresses = $this->createAddressQuery()
                ->andWhere(['fieldId' => null])
                ->collect();
        }

        return $this->_addresses;
    }

    /**
     * Returns a nested element manager for the user’s addresses.
     *
     * @return NestedElementManager
     * @since 5.0.0
     */
    public function getAddressManager(): NestedElementManager
    {
        if (!isset($this->_addressManager)) {
            $this->_addressManager = new NestedElementManager(
                Address::class,
                fn() => $this->createAddressQuery(),
                [
                    'attribute' => 'addresses',
                    'propagationMethod' => PropagationMethod::None,
                ],
            );
        }

        return $this->_addressManager;
    }

    /**
     * @inheritdoc
     */
    public function afterRestore(): void
    {
        $this->getAddressManager()->restoreNestedElements($this);
        parent::afterRestore();
    }

    private function createAddressQuery(): AddressQuery
    {
        return Address::find()
            ->owner($this)
            ->orderBy(['id' => SORT_ASC]);
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
     * Handles an invalid login for a user and sets the authError param.
     *
     * @return void
     */
    public function handleInvalidLoginParam(): void
    {
        Craft::$app->getUsers()->handleInvalidLogin($this);
        // Was that one bad password/2fa code/passkey too many?
        if ($this->locked && !Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
            // Will set the authError to either AccountCooldown or AccountLocked
            $this->authError = $this->_getAuthError();
        } else {
            $this->authError = self::AUTH_INVALID_CREDENTIALS;
        }
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
        if ($this->hasEventHandlers(self::EVENT_BEFORE_AUTHENTICATE)) {
            $event = new AuthenticateUserEvent(['password' => $password]);
            $this->trigger(self::EVENT_BEFORE_AUTHENTICATE, $event);
            if (isset($this->authError)) {
                return false;
            }
            if (!$event->performAuthentication) {
                return true;
            }
        }

        // Validate the password
        try {
            $passwordValid = Craft::$app->getSecurity()->validatePassword($password, $this->password);
        } catch (InvalidArgumentException) {
            $passwordValid = false;
        }

        if (!$passwordValid) {
            $this->handleInvalidLoginParam();
            return false;
        }

        $this->authError = $this->_getAuthError();
        return !isset($this->authError);
    }

    /**
     * Determines whether the user is allowed to be logged in with a security key.
     *
     * @param PublicKeyCredentialRequestOptions|array|string $requestOptions The public key credential request options
     * @param string $response The authentication response data
     * @return bool
     * @since 5.0.0
     */
    public function authenticateWithPasskey(
        PublicKeyCredentialRequestOptions|array|string $requestOptions,
        string $response,
    ): bool {
        $this->authError = null;

        // Fire a 'beforeAuthenticate' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_AUTHENTICATE)) {
            $event = new AuthenticateUserEvent();
            $this->trigger(self::EVENT_BEFORE_AUTHENTICATE, $event);

            if (isset($this->authError)) {
                return false;
            }

            if (!$event->performAuthentication) {
                return true;
            }
        }

        // make sure the passkey exists and belongs to this user
        $credential = WebAuthnRecord::findOne(['credentialId' => Json::decode($response)['id']]);
        if (!$credential || $credential['userId'] != $this->id) {
            $this->authError = self::AUTH_INVALID_CREDENTIALS;
            return false;
        }

        // Validate the security key
        try {
            $keyValid = Craft::$app->getAuth()->verifyPasskey($this, $requestOptions, $response);
        } catch (InvalidArgumentException) {
            $keyValid = false;
        }

        if (!$keyValid) {
            $this->handleInvalidLoginParam();
            return false;
        }

        $this->authError = $this->_getAuthError();
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

        if (Craft::$app->edition < CmsEdition::Pro || !isset($this->id)) {
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
        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
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
        if (Craft::$app->edition < CmsEdition::Pro) {
            return false;
        }

        if ($group instanceof UserGroup) {
            $group = $group->id;
        }

        if (is_numeric($group)) {
            return ArrayHelper::contains($this->getGroups(), fn(UserGroup $g) => $g->id == $group);
        }

        return ArrayHelper::contains($this->getGroups(), fn(UserGroup $g) => $g->handle === $group);
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
        // Fire a 'defineName' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_NAME)) {
            $event = new DefineValueEvent();
            $this->trigger(self::EVENT_DEFINE_NAME, $event);
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
        // Fire a 'defineFriendlyName' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_FRIENDLY_NAME)) {
            $event = new DefineValueEvent();
            $this->trigger(self::EVENT_DEFINE_FRIENDLY_NAME, $event);
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
     * Returns the user’s affiliated site, if they have one.
     *
     * @return Site|null
     * @since 5.6.0
     */
    public function getAffiliatedSite(): ?Site
    {
        if ($this->affiliatedSiteId === null || !Craft::$app->getIsMultiSite()) {
            return null;
        }

        return Craft::$app->getSites()->getSiteById($this->affiliatedSiteId, true);
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
    protected function thumbUrl(int $size): ?string
    {
        $photo = $this->getPhoto();

        if ($photo) {
            return Craft::$app->getAssets()->getThumbUrl($photo, $size, iconFallback: false);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function thumbSvg(): ?string
    {
        if (!$this->uid) {
            return null;
        }

        $names = array_filter([$this->firstName, $this->lastName]) ?: [$this->getName()];
        $initials = implode('', array_map(fn($name) => mb_strtoupper(mb_substr($name, 0, 1)), $names));

        // Choose a color based on the UUID
        $uid = strtolower($this->uid ?? '00ff');
        $totalColors = count(self::$photoColors);
        /** @phpstan-ignore-next-line */
        $color1Index = base_convert(substr($uid, 0, 2), 16, 10) % $totalColors;
        /** @phpstan-ignore-next-line */
        $color2Index = base_convert(substr($uid, 2, 2), 16, 10) % $totalColors;
        if ($color2Index === $color1Index) {
            $color2Index = ($color1Index + 1) % $totalColors;
        }
        $color1 = self::$photoColors[$color1Index % $totalColors];
        $color2 = self::$photoColors[$color2Index % $totalColors];

        $gradientId = sprintf('gradient-%s', StringHelper::randomString(10));

        return <<<XML
<svg version="1.1" baseProfile="full" width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="$gradientId" x1="0" y1="1" x2="1"  y2="0">
        <stop offset="0%" style="stop-color:var(--$color1-500)" />
        <stop offset="100%" style="stop-color:var(--$color2-500)" />
      </linearGradient>
    </defs>
    <circle cx="50" cy="50" r="50" fill="url(#$gradientId)" opacity="0.25"/>
    <text x="50" y="66" font-size="46" font-weight="500" font-family="sans-serif" text-anchor="middle" fill="var(--text-color)">$initials</text>
</svg>
XML;
    }

    /**
     * @inheritdoc
     */
    protected function thumbAlt(): ?string
    {
        return $this->getPhoto()->alt ?? $this->getName();
    }

    /**
     * @inheritdoc
     */
    protected function hasRoundedThumb(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function createAnother(): ?ElementInterface
    {
        return Craft::createObject(self::class);
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
            $user->can('viewUsers')
        );
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        if (!$this->id) {
            return $user->canRegisterUsers();
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
        if (
            $this->admin ||
            Craft::$app->edition === CmsEdition::Solo
        ) {
            return true;
        }

        if (!isset($this->id)) {
            return false;
        }

        return Craft::$app->getUserPermissions()->doesUserHavePermission($this->id, $permission);
    }

    /**
     * Returns whether the user can register additional users.
     *
     * @return bool
     * @since 5.0.0
     */
    final public function canRegisterUsers(): bool
    {
        return (
            $this->can('registerUsers') &&
            Craft::$app->getUsers()->canCreateUsers()
        );
    }

    /**
     * Returns whether the user is authorized to assign any user groups to users.
     *
     * @return bool
     * @since 4.0.0
     */
    public function canAssignUserGroups(): bool
    {
        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
            foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
                if ($this->can("assignUserGroup:$group->uid")) {
                    return true;
                }
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

        if (Craft::$app->edition === CmsEdition::Solo) {
            return null;
        }

        return UrlHelper::cpUrl("users/$this->id");
    }

    /**
     * @inheritdoc
     */
    protected function safeActionMenuItems(): array
    {
        if (!$this->id || $this->getIsUnpublishedDraft()) {
            return parent::safeActionMenuItems();
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $view = Craft::$app->getView();
        $usersService = Craft::$app->getUsers();
        $userSession = Craft::$app->getUser();

        $canAdministrateUsers = $currentUser->can('administrateUsers');
        $canModerateUsers = $currentUser->can('moderateUsers');

        $isCurrentUser = $this->getIsCurrent();

        $statusItems = [];
        $sessionItems = [];
        $miscItems = [];

        if (Craft::$app->edition !== CmsEdition::Solo) {
            $status = $this->getStatus();

            switch ($status) {
                case Element::STATUS_ARCHIVED:
                case Element::STATUS_DISABLED:
                    if (Craft::$app->getElements()->canSave($this)) {
                        $statusItems[] = [
                            'label' => Craft::t('app', 'Enable'),
                            'action' => 'users/enable-user',
                            'params' => [
                                'userId' => $this->id,
                            ],
                        ];
                    }
                    break;
                case self::STATUS_INACTIVE:
                case self::STATUS_PENDING:
                    // Only provide activation actions if they have an email address
                    if ($this->email) {
                        if ($this->pending || $canModerateUsers) {
                            $statusItems[] = [
                                'icon' => 'paperplane',
                                'label' => Craft::t('app', 'Send activation email'),
                                'action' => 'users/send-activation-email',
                                'params' => [
                                    'userId' => $this->id,
                                ],
                            ];
                        }
                        if ($canAdministrateUsers) {
                            // Only need to show the "Copy activation URL" option if they don't have a password
                            if (!$this->password) {
                                $statusItems[] = $this->_copyPasswordResetUrlActionItem(Craft::t('app', 'Copy activation URL…'), $view);
                            }
                            $statusItems[] = [
                                'icon' => 'enabled',
                                'label' => Craft::t('app', 'Activate account'),
                                'action' => 'users/activate-user',
                                'params' => [
                                    'userId' => $this->id,
                                ],
                            ];
                        }
                    }
                    break;
                case self::STATUS_SUSPENDED:
                    if ($usersService->canSuspend($currentUser, $this)) {
                        $statusItems[] = [
                            'icon' => 'enabled',
                            'label' => Craft::t('app', 'Unsuspend'),
                            'action' => 'users/unsuspend-user',
                            'params' => [
                                'userId' => $this->id,
                            ],
                        ];
                    }
                    break;
                case self::STATUS_ACTIVE:
                    if ($this->locked) {
                        if (
                            !$isCurrentUser &&
                            ($currentUser->admin || !$this->admin) &&
                            $canModerateUsers &&
                            (
                                ($impersonatorId = $userSession->getImpersonatorId()) === null ||
                                $this->id !== $impersonatorId
                            )
                        ) {
                            $statusItems[] = [
                                'label' => Craft::t('app', 'Unlock'),
                                'action' => 'users/unlock-user',
                                'params' => [
                                    'userId' => $this->id,
                                ],
                            ];
                        }
                    }

                    if (!$isCurrentUser && $userSession->checkPermission('editUsers')) {
                        $statusItems[] = [
                            'icon' => 'paperplane',
                            'label' => Craft::t('app', 'Send password reset email'),
                            'action' => 'users/send-password-reset-email',
                            'params' => [
                                'userId' => $this->id,
                            ],
                        ];
                        if ($canAdministrateUsers) {
                            $statusItems[] = $this->_copyPasswordResetUrlActionItem(Craft::t('app', 'Copy password reset URL…'), $view);
                        }
                    }
                    break;
            }

            if (
                in_array($status, [self::STATUS_PENDING, self::STATUS_ACTIVE]) &&
                $canAdministrateUsers &&
                !$isCurrentUser
            ) {
                if ($this->passwordResetRequired) {
                    $statusItems[] = [
                        'icon' => 'asterisk-slash',
                        'iconColor' => 'gray',
                        'label' => Craft::t('app', 'Don’t require a password reset on next login'),
                        'action' => 'users/remove-password-reset-requirement',
                        'params' => [
                            'userId' => $this->id,
                        ],
                    ];
                } else {
                    $statusItems[] = [
                        'icon' => 'asterisk',
                        'label' => Craft::t('app', 'Require a password reset on next login'),
                        'action' => 'users/require-password-reset',
                        'params' => [
                            'userId' => $this->id,
                        ],
                    ];
                }
            }

            if (!$isCurrentUser) {
                if ($usersService->canImpersonate($currentUser, $this)) {
                    $sessionItems[] = [
                        'icon' => 'key',
                        'label' => trim($this->getName())
                            ? Craft::t('app', 'Sign in as {user}', ['user' => $this->getName()])
                            : Craft::t('app', 'Sign in as user'),
                        'action' => 'users/impersonate',
                        'params' => [
                            'userId' => $this->id,
                        ],
                        'requireElevatedSession' => true,
                    ];

                    $copyImpersonationUrlId = sprintf('action-copy-impersonation-url-%s', mt_rand());
                    $sessionItems[] = [
                        'id' => $copyImpersonationUrlId,
                        'icon' => 'clipboard',
                        'label' => Craft::t('app', 'Copy impersonation URL…'),
                    ];

                    $view->registerJsWithVars(fn($id, $userId, $message) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.elevatedSessionManager.requireElevatedSession(() => {
      Craft.sendActionRequest('POST', 'users/get-impersonation-url', {
        data: {userId: $userId},
      }).then((response) => {
        Craft.ui.createCopyTextPrompt({
          label: $message,
          value: response.data.url,
        });
      });
  });
});
JS, [
                        $view->namespaceInputId($copyImpersonationUrlId),
                        $this->id,
                        Craft::t('app', 'Copy the impersonation URL, and open it in a new private window.'),
                    ]);
                }
            }
        }

        return [
            ...parent::safeActionMenuItems(),
            ['type' => MenuItemType::HR],
            ...$statusItems,
            ['type' => MenuItemType::HR],
            ...$miscItems,
            ['type' => MenuItemType::HR],
            ...$sessionItems,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function destructiveActionMenuItems(): array
    {
        if (!$this->id || $this->getIsUnpublishedDraft()) {
            return parent::destructiveActionMenuItems();
        }

        // Intentionally not calling parent::destructiveActionMenuItems() here,
        // because we want to override the user deletion UX.

        $currentUser = Craft::$app->getUser()->getIdentity();
        $usersService = Craft::$app->getUsers();

        $canAdministrateUsers = $currentUser->can('administrateUsers');

        $isCurrentUser = $this->getIsCurrent();

        $items = [];

        if (Craft::$app->edition !== CmsEdition::Solo) {
            if (!$isCurrentUser) {
                if ($usersService->canSuspend($currentUser, $this) && $this->active && !$this->suspended) {
                    $items[] = [
                        'icon' => 'ban',
                        'label' => Craft::t('app', 'Suspend'),
                        'action' => 'users/suspend-user',
                        'params' => [
                            'userId' => $this->id,
                        ],
                    ];
                }
            }

            // Destructive actions that should only be performed on non-admins, unless the current user is also an admin
            if (!$this->admin || $currentUser->admin) {
                if (($isCurrentUser || $canAdministrateUsers) && ($this->active || $this->pending)) {
                    $items[] = [
                        'icon' => 'disabled',
                        'label' => Craft::t('app', 'Deactivate'),
                        'action' => 'users/deactivate-user',
                        'params' => [
                            'userId' => $this->id,
                        ],
                        'confirm' => Craft::t('app', 'Deactivating a user revokes their ability to sign in. Are you sure you want to continue?'),
                    ];
                }

                if ($isCurrentUser || $currentUser->can('deleteUsers')) {
                    $view = Craft::$app->getView();
                    $deleteId = sprintf('action-delete-%s', mt_rand());
                    $items[] = [
                        'id' => $deleteId,
                        'icon' => 'trash',
                        'label' => StringHelper::upperCaseFirst(Craft::t('app', 'Delete {type}', [
                            'type' => static::lowerDisplayName(),
                        ])),
                    ];

                    $view->registerJsWithVars(fn($id, $userId, $redirect) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.sendActionRequest('POST', 'users/user-content-summary', {
    data: {userId: $userId}
  }).then((response) => {
    new Craft.DeleteUserModal($userId, {
      contentSummary: response.data,
      redirect: $redirect,
    });
  });
});
JS,
                    [
                        $view->namespaceInputId($deleteId),
                        $this->id,
                        /** @phpstan-ignore-next-line */
                        Craft::$app->getSecurity()->hashData(Craft::$app->edition === CmsEdition::Solo ? 'dashboard' : 'users'),
                    ]);
                }
            }
        }

        return $items;
    }

    private function _copyPasswordResetUrlActionItem(string $label, View $view): array
    {
        $id = sprintf('action-copy-password-reset-url-%s', mt_rand());

        $view->registerJsWithVars(fn($id, $userId, $message) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.elevatedSessionManager.requireElevatedSession(() => {
    Craft.sendActionRequest('POST', 'users/get-password-reset-url', {
      data: {userId: $userId}
    }).then((response) => {
      Craft.ui.createCopyTextPrompt({
        label: $message,
        value: response.data.url,
      });
    }).catch(({response}) => {
      Craft.cp.displayError(response.data.message);
    });
  });
});
JS, [
            $view->namespaceInputId($id),
            $this->id,
            Craft::t('app', 'Copy the activation URL'),
        ]);

        return [
            'id' => $id,
            'icon' => 'clipboard',
            'label' => $label,
        ];
    }

    /**
     * Returns the user’s preferences.
     *
     * @return array The user’s preferences.
     */
    public function getPreferences(): array
    {
        // only CP users can save preferences
        if (!$this->can('accessCp')) {
            return [];
        }

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
     * Returns whether the user prefers to have form fields autofocused on page load.
     *
     * @return bool
     * @since 5.0.0
     */
    public function getAutofocusPreferred(): bool
    {
        return !$this->getPreference('disableAutofocus');
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
        if (!$locale) {
            return null;
        }

        $locales = $checkAllLocales ? Craft::$app->getI18n()->getAllLocaleIds() : Craft::$app->getI18n()->getAppLocaleIds();
        return in_array($locale, $locales, true) ? $locale : null;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if ($plan->handle === 'photo') {
            /** @var Asset|null $photo */
            $photo = $elements[0] ?? null;
            $this->setPhoto($photo);
        } else {
            parent::setEagerLoadedElements($handle, $elements, $plan);
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
     * Sets the user’s photo.
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
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'email':
                return $this->email ? Html::mailto(Html::encode($this->email)) : '';

            case 'groups':
                return implode(', ', array_map(fn(UserGroup $group) => Html::encode(Craft::t('site', $group->name)), $this->getGroups()));

            case 'preferredLanguage':
                $language = $this->getPreferredLanguage();
                return $language ? Craft::$app->getI18n()->getLocaleById($language)->getDisplayName(Craft::$app->language) : '';

            case 'preferredLocale':
                $locale = $this->getPreferredLocale();
                return $locale ? Craft::$app->getI18n()->getLocaleById($locale)->getDisplayName(Craft::$app->language) : '';

            case 'is2faEnabled':
                $enabled = Craft::$app->getAuth()->hasActiveMethod($this);
                if ($this->viewMode === 'cards') {
                    return Cp::statusLabelHtml([
                        'color' => $enabled ? Color::Teal : Color::Gray,
                        'label' => Craft::t('app', 'Two-Step Verification'),
                        'icon' => $enabled ? 'check' : 'xmark',
                    ]);
                } else {
                    if (!$enabled) {
                        return '';
                    }

                    return Html::tag('span', '', [
                        'class' => 'checkbox-icon',
                        'role' => 'img',
                        'title' => Craft::t('app', 'Enabled'),
                        'aria' => [
                            'label' => Craft::t('app', 'Enabled'),
                        ],
                    ]);
                }

                // no break
            case 'isCredentialed':
                $value = $this->getIsCredentialed();
                if ($this->viewMode === 'cards') {
                    return Cp::statusLabelHtml([
                        'color' => $value ? Color::Teal : Color::Gray,
                        'label' => Craft::t('app', 'Credentialed'),
                        'icon' => $value ? 'check' : 'xmark',
                    ]);
                }
        }

        return parent::attributeHtml($attribute);
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
        return self::GQL_TYPE_NAME;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    final public function beforeSave(bool $isNew): bool
    {
        if ($isNew && !Craft::$app->getUsers()->canCreateUsers()) {
            return false;
        }

        if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $this->username = $this->email;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew && Craft::$app->edition === CmsEdition::Solo) {
            // Make sure they're an admin
            $this->admin = true;
        }

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
            $record->id = $this->id;
            $record->active = $this->active;
            $record->pending = $this->pending;
            $record->locked = $this->locked;
            $record->suspended = $this->suspended;
        }

        $this->prepareNamesForSave();

        $record->photoId = $this->photoId;
        $record->affiliatedSiteId = $this->affiliatedSiteId;
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

        if (Craft::$app->edition === CmsEdition::Team) {
            // Make sure they're in the Team group
            $group = Craft::$app->getUserGroups()->getTeamGroup();
            if (!$this->isInGroup($group)) {
                Craft::$app->getUsers()->assignUserToGroups($this->id, [$group->id]);
            }
        }

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
                    Table::DRAFTS => 'creatorId',
                    Table::REVISIONS => 'creatorId',
                    Table::ENTRIES_AUTHORS => 'authorId',
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
                    // only delete their entry if they're the sole author
                    if ($entry->getAuthorIds() === [$this->id]) {
                        $elementsService->deleteElement($entry);
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->getAddressManager()->deleteNestedElements($this, $this->hardDelete);

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

        if (!$requestUserAgent) {
            return false;
        }

        if (!hash_equals($userAgent, md5($requestUserAgent))) {
            Craft::warning('Tried to restore session from the the identity cookie, but the saved user agent (' . $userAgent . ') does not match the current request’s (' . $requestUserAgent . ').', __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * Returns the [[authError]] value for [[authenticate()]] and [[authenticateWithPasskey()]].
     *
     * @todo Nate! Duplicate of UserHelper::getAuthStatus()
     *
     * @return self::AUTH_*|null
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
