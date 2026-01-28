<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\actions\DeleteUsers;
use craft\elements\actions\Restore;
use craft\elements\actions\SuspendUsers;
use craft\elements\actions\UnsuspendUsers;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\conditions\users\UserCondition;
use craft\elements\db\EagerLoadPlan;
use craft\elements\NestedElementManager;
use craft\events\DefineValueEvent;
use craft\fieldlayoutelements\users\FullNameField;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\twig\AllowedInSandbox;
use craft\web\View;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Concerns\HasNames;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Translation\Formatter;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Notifications\ResetPasswordNotification;
use CraftCms\Cms\User\Notifications\VerifyEmailNotification;
use CraftCms\Cms\User\Validation\UserRules;
use CraftCms\Cms\Validation\Attributes\Ruleset;
use DateInterval;
use DateTime;
use DateTimeZone;
use Deprecated;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB as DbFacade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Traits\Macroable;
use Override;
use Stringable;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\validators\RequiredValidator;
use yii\web\BadRequestHttpException;

use function CraftCms\Cms\t;

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
 */
#[Ruleset(UserRules::class)]
final class User extends Element implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, MustVerifyEmailContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use ConfirmsPasswords;
    use HasNames;
    use Macroable;
    use Notifiable;

    /**
     * @since 5.0.0
     */
    public const string GQL_TYPE_NAME = 'User';

    /**
     * @event AuthenticateUserEvent The event that is triggered before a user is authenticated.
     *
     * If you wish to offload authentication logic, then set [[AuthenticateUserEvent::$performAuthentication]] to `false`, and set [[$authError]] to
     * something if there is an authentication error.
     */
    public const string EVENT_BEFORE_AUTHENTICATE = 'beforeAuthenticate';

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s name, as returned by [[getName()]] or [[__toString()]].
     *
     * @since 3.7.0
     */
    public const string EVENT_DEFINE_NAME = 'defineName';

    /**
     * @event DefineValueEvent The event that is triggered when defining the user’s friendly name, as returned by [[getFriendlyName()]].
     *
     * @since 3.7.0
     */
    public const string EVENT_DEFINE_FRIENDLY_NAME = 'defineFriendlyName';

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
    public const string STATUS_INACTIVE = 'inactive';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_SUSPENDED = 'suspended';

    public const string STATUS_LOCKED = 'locked';

    // Validation scenarios
    // -------------------------------------------------------------------------

    /**
     * @since 4.4.8
     */
    public const string SCENARIO_ACTIVATION = 'activation';

    public const string SCENARIO_REGISTRATION = 'registration';

    public const string SCENARIO_PASSWORD = 'password';

    #[Override]
    public function scenarios(): array
    {
        return array_merge(parent::scenarios(), [
            self::SCENARIO_PASSWORD => ['newPassword'],
            self::SCENARIO_REGISTRATION => ['username', 'email', 'newPassword'],
            self::SCENARIO_ACTIVATION => ['username', 'email'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getKey(): ?int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('User');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('user');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Users');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('users');
    }

    /**
     * {@inheritdoc}
     */
    public static function refHandle(): string
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function hasThumbs(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => [
                'label' => t('Active'),
            ],
            self::STATUS_PENDING => [
                'label' => t('Pending'),
                'color' => Color::Orange,
            ],
            self::STATUS_SUSPENDED => [
                'label' => t('Suspended'),
                'color' => Color::Red,
            ],
            self::STATUS_LOCKED => [
                'label' => t('Locked'),
                'color' => Color::Red,
            ],
            self::STATUS_INACTIVE => [
                'label' => t('Inactive'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function find(): UserQuery
    {
        return new UserQuery;
    }

    /**
     * {@inheritdoc}
     *
     * @return UserCondition
     */
    #[Override]
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(UserCondition::class, [self::class]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineSources(string $context): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => t('All users'),
                'hasThumbs' => true,
                'data' => [
                    'slug' => 'all',
                ],
            ],
        ];

        if (Edition::get()->value >= Edition::Pro->value) {
            $sources = array_merge($sources, [
                [
                    'key' => 'admins',
                    'label' => t('Admins'),
                    'criteria' => ['admin' => true],
                    'hasThumbs' => true,
                    'data' => [
                        'slug' => 'admins',
                    ],
                ],
                [
                    'heading' => t('Account Type'),
                ],
                [
                    'key' => 'credentialed',
                    'label' => t('Credentialed'),
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
                    'label' => t('Inactive'),
                    'criteria' => [
                        'status' => self::STATUS_INACTIVE,
                    ],
                    'hasThumbs' => true,
                    'data' => [
                        'slug' => 'inactive',
                    ],
                ],
            ]);

            $groups = UserGroups::getAllGroups();

            if ($groups->isNotEmpty()) {
                $sources[] = ['heading' => t('Groups')];

                foreach ($groups as $group) {
                    $sources[] = [
                        'key' => 'group:'.$group->uid,
                        'label' => t($group->name, category: 'site'),
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
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineActions(string $source): array
    {
        $actions = [];

        if (Gate::check('moderateUsers')) {
            // Suspend
            $actions[] = SuspendUsers::class;

            // Unsuspend
            $actions[] = UnsuspendUsers::class;
        }

        if (Gate::check('deleteUsers')) {
            // Delete
            $actions[] = DeleteUsers::class;
        }

        // Restore
        $actions[] = Restore::class;

        return $actions;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineSearchableAttributes(): array
    {
        return ['username', 'fullName', 'firstName', 'lastName', 'email'];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineSortOptions(): array
    {
        if (Cms::config()->useEmailAsUsername) {
            return [
                'email' => t('Email'),
                'fullName' => t('Full Name'),
                'firstName' => t('First Name'),
                'lastName' => t('Last Name'),
                [
                    'label' => t('Last Login'),
                    'orderBy' => 'lastLoginDate',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => t('Date Created'),
                    'orderBy' => 'dateCreated',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => t('Date Updated'),
                    'orderBy' => 'dateUpdated',
                    'defaultDir' => 'desc',
                ],
            ];
        }

        return [
            'username' => t('Username'),
            'fullName' => t('Full Name'),
            'firstName' => t('First Name'),
            'lastName' => t('Last Name'),
            'email' => t('Email'),
            [
                'label' => t('Last Login'),
                'orderBy' => 'lastLoginDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Date Created'),
                'orderBy' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => t('Date Updated'),
                'orderBy' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineTableAttributes(): array
    {
        return array_merge(parent::defineTableAttributes(), array_filter([
            'email' => ['label' => t('Email')],
            'username' => ['label' => t('Username')],
            'fullName' => ['label' => t('Full Name')],
            'firstName' => ['label' => t('First Name')],
            'lastName' => ['label' => t('Last Name')],
            'groups' => ['label' => t('Groups')],
            'affiliatedSite' => Sites::isMultiSite() ? ['label' => t('Affiliated Site')] : null,
            'preferredLanguage' => ['label' => t('Preferred Language')],
            'preferredLocale' => ['label' => t('Preferred Locale')],
            'lastLoginDate' => ['label' => t('Last Login')],
            'isCredentialed' => ['label' => t('Credentialed')],
            'is2faEnabled' => ['label' => t('Two-Step Verification')],
        ]));
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     * {@inheritdoc}
     */
    #[Override]
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        /** @var \CraftCms\Cms\Element\Queries\UserQuery $elementQuery */
        if ($attribute === 'groups') {
            $elementQuery->withGroups();
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected static function defineCardAttributes(): array
    {
        return array_merge(parent::defineCardAttributes(), [
            'email' => [
                'label' => t('Email'),
                'placeholder' => fn () => 'test@example.com',
            ],
            'username' => [
                'label' => t('Username'),
                'placeholder' => fn () => t('Username'),
            ],
            'firstName' => [
                'label' => t('First Name'),
                'placeholder' => fn () => t('First Name'),
            ],
            'lastName' => [
                'label' => t('Last Name'),
                'placeholder' => fn () => t('Last Name'),
            ],
            'fullName' => [
                'label' => t('Full Name'),
                'placeholder' => t('Full Name'),
            ],
            'groups' => [
                'label' => t('Groups'),
                'placeholder' => fn () => t('Group Name'),
            ],
            'affiliatedSite' => [
                'label' => t('Affiliated Site'),
                'placeholder' => fn () => t('Site Name'),
            ],
            'preferredLanguage' => [
                'label' => t('Preferred Language'),
                'placeholder' => fn () => I18N::getLocaleById('en')->getDisplayName(app()->getLocale()),
            ],
            'preferredLocale' => [
                'label' => t('Preferred Locale'),
                'placeholder' => fn () => I18N::getLocaleById('en-US')->getDisplayName(app()->getLocale()),
            ],
            'isCredentialed' => [
                'label' => t('Credentialed'),
                'placeholder' => fn () => Template::raw(Cp::statusLabelHtml([
                    'color' => Color::Teal,
                    'label' => t('Credentialed'),
                    'icon' => 'check',
                ])),
            ],
            'lastLoginDate' => [
                'label' => t('Last Login'),
                'placeholder' => fn () => (new DateTime)->sub(new DateInterval('P14D')),
            ],
            'is2faEnabled' => [
                'label' => t('Two-Step Verification'),
                'placeholder' => fn () => Template::raw(Cp::statusLabelHtml([
                    'color' => Color::Teal,
                    'label' => t('Two-Step Verification'),
                    'icon' => 'check',
                ])),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        // Get the source element IDs
        $sourceElementIds = array_map(fn (ElementInterface $element) => $element->id, $sourceElements);

        if ($handle === 'addresses') {
            $map = DbFacade::table(Table::ADDRESSES)
                ->select([
                    'primaryOwnerId as source',
                    'id as target',
                ])
                ->where('primaryOwnerId', $sourceElementIds)
                ->get()
                ->map(fn (object $row) => (array) $row)
                ->all();

            return [
                'elementType' => Address::class,
                'map' => $map,
                'createElement' => fn (AddressQuery $query, array $result, self $source) =>
                    // set the addresses' owners to the source user elements
                    // (must get set before behaviors - see https://github.com/craftcms/cms/issues/13400)
                $query->createElement(['owner' => $source] + $result),
            ];
        }

        if ($handle === 'photo') {
            $map = DbFacade::table(Table::USERS)
                ->select(['id as source', 'photoId as target'])
                ->whereIn('id', $sourceElementIds)
                ->whereNotNull('photoId')
                ->get()
                ->map(fn (object $row) => (array) $row)
                ->all();

            return [
                'elementType' => Asset::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @var int|null Photo asset ID
     */
    #[AllowedInSandbox]
    public ?int $photoId = null;

    /**
     * @var bool Active
     *
     * @since 4.0.0
     */
    #[AllowedInSandbox]
    public bool $active = false;

    /**
     * @var bool Pending
     */
    #[AllowedInSandbox]
    public bool $pending = false;

    /**
     * @var bool Locked
     */
    #[AllowedInSandbox]
    public bool $locked = false;

    /**
     * @var bool Suspended
     */
    #[AllowedInSandbox]
    public bool $suspended = false;

    /**
     * @var bool Admin
     */
    #[AllowedInSandbox]
    public bool $admin = false;

    /**
     * @var string|null Username
     */
    #[AllowedInSandbox]
    public ?string $username = null;

    /**
     * @var string|null Email
     */
    #[AllowedInSandbox]
    public ?string $email = null;

    /**
     * @var string|null Password
     */
    public ?string $password = null;

    /**
     * @var int|null Affiliated site ID
     *
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public ?int $affiliatedSiteId = null;

    /**
     * @var DateTime|null Last login date
     */
    #[AllowedInSandbox]
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
     *
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
     * @var string|null Last login attempt IP address.
     */
    public ?string $lastLoginAttemptIp = null;

    /**
     * @var string|null Session remember token
     */
    public ?string $remember_token = null;

    /**
     * @var self|null The user who should take over the user’s content if the user is deleted.
     */
    public ?User $inheritorOnDelete = null;

    /**
     * @var ElementCollection<Address> Addresses
     *
     * @see getAddresses()
     */
    private ElementCollection $_addresses;

    /**
     * @see getAddressManager()
     */
    private NestedElementManager $_addressManager;

    /**
     * @see getName()
     * @see setName()
     */
    private ?string $_name = null;

    /**
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
     * @see setAttributesFromRequest()
     * @see afterSave()
     */
    private bool $sendVerificationEmailAfterRequest = false;

    /**
     * {@inheritdoc}
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * {@inheritdoc}
     */
    public function hasVerifiedEmail(): bool
    {
        return is_null($this->unverifiedEmail);
    }

    /**
     * {@inheritdoc}
     */
    public function markEmailAsVerified(): bool
    {
        try {
            Users::verifyEmailForUser($this);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendEmailVerificationNotification(): void
    {
        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker('craft');

        $this->notify(new VerifyEmailNotification($broker->createToken($this)));
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailForVerification(): string
    {
        return $this->unverifiedEmail ?? $this->email;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function init(): void
    {
        parent::init();

        // Is this user in cooldown mode, and are they past their window?
        if (
            $this->locked &&
            Cms::config()->cooldownDuration &&
            ! $this->getRemainingCooldownTime()
        ) {
            Users::unlockUser($this);
        }

        // Convert IDNA ASCII to Unicode
        if ($this->username) {
            $this->username = Str::idnToUtf8Email($this->username);
        }
        if ($this->email) {
            $this->email = Str::idnToUtf8Email($this->email);
        }

        if (empty($this->username) && Cms::config()->useEmailAsUsername) {
            $this->username = $this->email;
        }

        if ($this->password === '') {
            $this->password = null;
        }

        $this->normalizeNames();
    }

    /**
     * Use the full name or username as the string representation.
     */
    #[Override]
    public function __toString(): string
    {
        $name = $this->getName();
        if ($name !== '') {
            return $name;
        }

        return parent::__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getPostEditUrl(): ?string
    {
        if (
            Edition::get() === Edition::Solo ||
            ! Gate::check('editUsers')
        ) {
            return null;
        }

        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function crumbs(): array
    {
        if (Edition::get() === Edition::Solo) {
            return [];
        }

        return [
            [
                'label' => t('Users'),
                'url' => 'users',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function uiLabel(): ?string
    {
        return $this->getName() ?: $this->email;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     * {@inheritdoc}
     */
    #[Override]
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'groups';
        $names[] = 'addresses';
        $names[] = 'photo';

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['currentPassword'] = t('Current Password');
        $labels['email'] = t('Email');
        $labels['fullName'] = t('Full Name');
        $labels['firstName'] = t('First Name');
        $labels['lastName'] = t('Last Name');
        $labels['newPassword'] = t('New Password');
        $labels['password'] = t('Password');
        $labels['unverifiedEmail'] = t('Email');
        $labels['username'] = t('Username');

        return $labels;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function afterValidate(): void
    {
        $scenario = $this->getScenario();

        if ($scenario === self::SCENARIO_LIVE) {
            $fullNameElement = $this->getFieldLayout()->getFirstVisibleElementByType(FullNameField::class, $this);
            if ($fullNameElement && $fullNameElement->required) {
                if (Cms::config()->showFirstAndLastNameFields) {
                    new RequiredValidator(['attributes' => ['firstName', 'lastName']])->validateAttributes($this, ['firstName', 'lastName']);
                } else {
                    (new RequiredValidator)->validateAttribute($this, 'fullName');
                }
            }
        }

        parent::afterValidate();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function safeAttributes(): array
    {
        return Arr::except(parent::safeAttributes(), ['photoId']);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
            if (! $this->isPasswordConfirmed()) {
                throw new BadRequestHttpException(t('An elevated session is required to change a user’s email.'));
            }

            if ($this->email !== null) {
                // are they allowed to set the email?
                if ($this->getIsCurrent() || Gate::check('administrateUsers')) {
                    if (
                        Edition::get()->value >= Edition::Pro->value &&
                        app(ProjectConfig::class)->get('users.requireEmailVerification') &&
                        ! Gate::check('administrateUsers')
                    ) {
                        // set it as the unverified email instead, and
                        $values['unverifiedEmail'] = Arr::pull($values, 'email');
                        $this->sendVerificationEmailAfterRequest = true;
                    }
                } else {
                    unset($values['email']);
                }
            }
        }

        parent::setAttributesFromRequest($values);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     * @since 4.0.0
     */
    public function getIsCredentialed(): bool
    {
        return $this->active || $this->pending;
    }

    /**
     * Returns whether the user has a password.
     *
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public function getHasPassword(): bool
    {
        if (isset($this->password)) {
            return true;
        }

        return DbFacade::table(Table::USERS)
            ->where('id', $this->id)
            ->value('password') !== null;
    }

    /**
     * Returns whether the user has an associated SSO identity.
     *
     * @since 5.7.8
     */
    #[AllowedInSandbox]
    public function getHasSsoIdentity(): bool
    {
        if (Edition::get()->value < Edition::Enterprise->value) {
            return false;
        }

        return Craft::$app->getSso()->identityExists($this->id);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        // @TODO: Field layout for non-legacy
        return app(Fields::class)->getLayoutByType(User::class);
    }

    /**
     * Gets the user’s addresses.
     *
     * @return ElementCollection<Address>
     *
     * @since 4.0.0
     */
    #[AllowedInSandbox]
    public function getAddresses(): ElementCollection
    {
        if (! isset($this->_addresses)) {
            if (! $this->id) {
                /** @var ElementCollection<Address> */
                return ElementCollection::make();
            }

            $this->_addresses = $this->createAddressQuery()
                ->whereNull('fieldId')
                ->get();
        }

        return $this->_addresses;
    }

    /**
     * Returns a nested element manager for the user’s addresses.
     *
     * @since 5.0.0
     */
    public function getAddressManager(): NestedElementManager
    {
        if (! isset($this->_addressManager)) {
            $this->_addressManager = new NestedElementManager(
                Address::class,
                fn () => $this->createAddressQuery(),
                [
                    'attribute' => 'addresses',
                    'propagationMethod' => PropagationMethod::None,
                ],
            );
        }

        return $this->_addressManager;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     * Returns the reference string to this element.
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
    #[AllowedInSandbox]
    public function getGroups(): array
    {
        if (isset($this->_groups)) {
            return $this->_groups;
        }

        if (Edition::get() < Edition::Pro || ! isset($this->id)) {
            return [];
        }

        return $this->_groups = UserGroups::getGroupsByUserId($this->id)->all();
    }

    /**
     * Sets an array of user groups on the user.
     *
     * @param  UserGroup[]|\CraftCms\Cms\User\Data\UserGroup[]  $groups  An array of UserGroup objects.
     */
    public function setGroups(array $groups): void
    {
        if (Edition::get()->value >= Edition::Pro->value) {
            $this->_groups = $groups;
        }
    }

    /**
     * Returns whether the user is in a specific group.
     *
     * @param  int|string|\CraftCms\Cms\User\Data\UserGroup  $group  The user group model, its handle, or ID.
     */
    #[AllowedInSandbox]
    public function isInGroup(UserGroup|int|string $group): bool
    {
        if (Edition::get() < Edition::Pro) {
            return false;
        }

        if ($group instanceof UserGroup) {
            $group = $group->id;
        }

        if (is_numeric($group)) {
            return Collection::make($this->getGroups())->contains('id', $group);
        }

        /** @phpstan-ignore argument.type */
        return Collection::make($this->getGroups())->containsStrict('handle', $group);
    }

    /**
     * Returns whether the user is in any/all the given user groups.
     *
     * By default, `true` will be returned if the user is in *any* of the groups. To change that so `true` is only
     * returned if the user is in *all* of the groups, pass `true` to the second argument.
     *
     * @param  array<int|string|UserGroup>  $groups  The user groups, handles, or IDs
     * @param  bool  $all  Whether to only return `true` if the user is in *all* of the provided groups
     *
     * @since 5.9.0
     */
    #[AllowedInSandbox]
    public function isInGroups(array $groups, bool $all = false): bool
    {
        if (! $all) {
            return array_any($groups, fn ($group) => $this->isInGroup($group));
        }

        return array_all($groups, fn ($group) => $this->isInGroup($group));
    }

    /**
     * Returns the user’s full name.
     */
    #[Deprecated(message: 'in 4.0.0. [[fullName]] should be used instead.')]
    #[AllowedInSandbox]
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * Returns the user’s full name or username.
     */
    #[AllowedInSandbox]
    public function getName(): string
    {
        if (! isset($this->_name)) {
            $this->_name = $this->_defineName();
        }

        return $this->_name;
    }

    private function _defineName(): string
    {
        // Fire a 'defineName' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_NAME)) {
            $event = new DefineValueEvent;
            $this->trigger(self::EVENT_DEFINE_NAME, $event);
            if ($event->value !== null) {
                return $event->value;
            }
        }

        return $this->fullName ?? (string) $this->username;
    }

    /**
     * Sets the user’s name.
     *
     * @since 3.7.0
     */
    public function setName(string $name): void
    {
        $this->_name = $name;
    }

    /**
     * Returns the user’s first name or username.
     */
    #[AllowedInSandbox]
    public function getFriendlyName(): ?string
    {
        if (! isset($this->_friendlyName)) {
            $this->_friendlyName = $this->_defineFriendlyName() ?? false;
        }

        return $this->_friendlyName ?: null;
    }

    private function _defineFriendlyName(): ?string
    {
        // Fire a 'defineFriendlyName' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_FRIENDLY_NAME)) {
            $event = new DefineValueEvent;
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
     * @since 3.7.0
     */
    public function setFriendlyName(string $friendlyName): void
    {
        $this->_friendlyName = $friendlyName;
    }

    /**
     * Returns the user’s affiliated site, if they have one.
     *
     * @since 5.6.0
     */
    #[AllowedInSandbox]
    public function getAffiliatedSite(): ?Site
    {
        if ($this->affiliatedSiteId === null || ! Sites::isMultiSite()) {
            return null;
        }

        return Sites::getSiteById($this->affiliatedSiteId, true);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function thumbSvg(): ?string
    {
        if (! $this->uid) {
            return null;
        }

        $names = array_filter([$this->firstName, $this->lastName]) ?: [$this->getName()];
        $initials = implode('', array_map(fn ($name) => mb_strtoupper(mb_substr($name, 0, 1)), $names));

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

        $gradientId = sprintf('gradient-%s', Str::random(10));

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
     * {@inheritdoc}
     */
    protected function thumbAlt(): string
    {
        return $this->getPhoto()->alt ?? $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function hasRoundedThumb(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createAnother(): ElementInterface
    {
        return new self;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function canView(AuthorizableContract $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return
            $user->id === $this->id ||
            $user->can('viewUsers');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function canSave(AuthorizableContract $user): bool
    {
        if (! $this->id) {
            return $user->canRegisterUsers();
        }

        if ($user->id === $this->id) {
            return true;
        }

        return $user->can('editUsers');
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function canDuplicate(AuthorizableContract $user): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function canDelete(AuthorizableContract $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return
            $user->id !== $this->id &&
            $user->can('deleteUsers') &&
            (! $this->admin || $user->admin);
    }

    /**
     * Returns whether this is the current logged-in user.
     */
    public function getIsCurrent(): bool
    {
        if (! $this->id) {
            return false;
        }

        return Auth::user()?->id === $this->id;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * Returns whether the user can register additional users.
     *
     * @since 5.0.0
     */
    final public function canRegisterUsers(): bool
    {
        return
            $this->can('registerUsers') &&
            Users::canCreateUsers();
    }

    /**
     * Returns whether the user is authorized to assign any user groups to users.
     *
     * @since 4.0.0
     */
    public function canAssignUserGroups(): bool
    {
        if (Edition::get()->value < Edition::Pro->value) {
            return false;
        }

        if ($this->admin) {
            return true;
        }

        foreach (Craft::$app->getUserGroups()->getAllGroups() as $group) {
            if ($this->can("assignUserGroup:$group->uid")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the user has shunned a given message.
     */
    public function hasShunned(string $message): bool
    {
        if (isset($this->id)) {
            return Users::hasUserShunnedMessage($this->id, $message);
        }

        return false;
    }

    /**
     * Returns the time when the user will be over their cooldown period.
     */
    public function getCooldownEndTime(): ?DateTime
    {
        // There was an old bug that where a user’s lockoutDate could be null if they’ve
        // passed their cooldownDuration already, but there account status is still locked.
        // If that’s the case, just let it return null as if they are past the cooldownDuration.
        if ($this->locked && $this->lockoutDate) {
            $generalConfig = Cms::config();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->cooldownDuration);
            $cooldownEnd = clone $this->lockoutDate;
            $cooldownEnd->add($interval);

            return $cooldownEnd;
        }

        return null;
    }

    /**
     * Returns the remaining cooldown time for this user, if they’ve entered their password incorrectly too many times.
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
     * {@inheritdoc}
     */
    protected function cpEditUrl(): ?string
    {
        if (Craft::$app->getRequest()->getIsCpRequest() && $this->getIsCurrent()) {
            return UrlHelper::cpUrl('myaccount');
        }

        if (Edition::get() === Edition::Solo) {
            return null;
        }

        return UrlHelper::cpUrl("users/$this->id");
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function safeActionMenuItems(): array
    {
        if (! $this->id || $this->getIsUnpublishedDraft()) {
            return parent::safeActionMenuItems();
        }

        $currentUser = Auth::user();
        $view = Craft::$app->getView();
        Craft::$app->getUser();

        $canAdministrateUsers = $currentUser->can('administrateUsers');
        $canModerateUsers = $currentUser->can('moderateUsers');

        $isCurrentUser = $this->getIsCurrent();

        $statusItems = [];
        $sessionItems = [];
        $miscItems = [];

        if (Edition::get() !== Edition::Solo) {
            $status = $this->getStatus();

            switch ($status) {
                case Element::STATUS_ARCHIVED:
                case Element::STATUS_DISABLED:
                    if (Craft::$app->getElements()->canSave($this)) {
                        $statusItems[] = [
                            'label' => t('Enable'),
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
                                'label' => t('Send activation email'),
                                'action' => 'users/send-activation-email',
                                'params' => [
                                    'userId' => $this->id,
                                ],
                            ];
                        }
                        if ($canAdministrateUsers) {
                            // Only need to show the "Copy activation URL" option if they don't have a password
                            if (! $this->password) {
                                $statusItems[] = $this->_copyPasswordResetUrlActionItem(t('Copy activation URL…'), $view);
                            }
                            $statusItems[] = [
                                'icon' => 'enabled',
                                'label' => t('Activate account'),
                                'action' => 'users/activate-user',
                                'params' => [
                                    'userId' => $this->id,
                                ],
                            ];
                        }
                    }
                    break;
                case self::STATUS_SUSPENDED:
                    if (Users::canSuspend($currentUser, $this)) {
                        $statusItems[] = [
                            'icon' => 'enabled',
                            'label' => t('Unsuspend'),
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
                            ! $isCurrentUser &&
                            ($currentUser->admin || ! $this->admin) &&
                            $canModerateUsers &&
                            (
                                ($impersonatorId = app(Impersonation::class)->getImpersonatorId()) === null ||
                                $this->id !== $impersonatorId
                            )
                        ) {
                            $statusItems[] = [
                                'label' => t('Unlock'),
                                'action' => 'users/unlock-user',
                                'params' => [
                                    'userId' => $this->id,
                                ],
                            ];
                        }
                    }

                    if (! $isCurrentUser && Gate::check('editUsers')) {
                        $statusItems[] = [
                            'icon' => 'paperplane',
                            'label' => t('Send password reset email'),
                            'action' => 'users/send-password-reset-email',
                            'params' => [
                                'userId' => $this->id,
                            ],
                        ];
                        if ($canAdministrateUsers) {
                            $statusItems[] = $this->_copyPasswordResetUrlActionItem(t('Copy password reset URL…'), $view);
                        }
                    }
                    break;
            }

            if (
                in_array($status, [self::STATUS_PENDING, self::STATUS_ACTIVE]) &&
                $canAdministrateUsers &&
                ! $isCurrentUser
            ) {
                if ($this->passwordResetRequired) {
                    $statusItems[] = [
                        'icon' => 'asterisk-slash',
                        'iconColor' => 'gray',
                        'label' => t('Don’t require a password reset on next login'),
                        'action' => 'users/remove-password-reset-requirement',
                        'params' => [
                            'userId' => $this->id,
                        ],
                    ];
                } else {
                    $statusItems[] = [
                        'icon' => 'asterisk',
                        'label' => t('Require a password reset on next login'),
                        'action' => 'users/require-password-reset',
                        'params' => [
                            'userId' => $this->id,
                        ],
                    ];
                }
            }

            if (! $isCurrentUser) {
                if (Users::canImpersonate($currentUser, $this)) {
                    $sessionItems[] = [
                        'icon' => 'key',
                        'label' => trim($this->getName())
                            ? t('Sign in as {user}', ['user' => $this->getName()])
                            : t('Sign in as user'),
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
                        'label' => t('Copy impersonation URL…'),
                    ];

                    $view->registerJsWithVars(fn ($id, $userId, $message) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.elevatedSessionManager.requireElevatedSession(() => {
      Craft.sendActionRequest('POST', 'users/get-impersonation-url', {
        data: {userId: $userId},
      }).then((response) => {
        Craft.ui.createCopyTextPrompt({
          label: $message,
          value: response.data.url,
        })
      });
  });
});
JS, [
                        $view->namespaceInputId($copyImpersonationUrlId),
                        $this->id,
                        t('Copy the impersonation URL, and open it in a new private window.'),
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
     * {@inheritdoc}
     */
    #[Override]
    protected function destructiveActionMenuItems(): array
    {
        if (! $this->id || $this->getIsUnpublishedDraft()) {
            return parent::destructiveActionMenuItems();
        }

        // Intentionally not calling parent::destructiveActionMenuItems() here,
        // because we want to override the user deletion UX.

        /** @var User $currentUser */
        $currentUser = Auth::user();
        $canAdministrateUsers = $currentUser->can('administrateUsers');

        $isCurrentUser = $this->getIsCurrent();

        $items = [];

        if (Edition::get() !== Edition::Solo) {
            if (! $isCurrentUser) {
                if (Users::canSuspend($currentUser, $this) && $this->active && ! $this->suspended) {
                    $items[] = [
                        'icon' => 'ban',
                        'label' => t('Suspend'),
                        'action' => 'users/suspend-user',
                        'params' => [
                            'userId' => $this->id,
                        ],
                    ];
                }
            }

            // Destructive actions that should only be performed on non-admins, unless the current user is also an admin
            if (! $this->admin || $currentUser->admin) {
                if (($isCurrentUser || $canAdministrateUsers) && ($this->active || $this->pending)) {
                    $items[] = [
                        'icon' => 'disabled',
                        'label' => t('Deactivate'),
                        'action' => 'users/deactivate-user',
                        'params' => [
                            'userId' => $this->id,
                        ],
                        'confirm' => t('Deactivating a user revokes their ability to sign in. Are you sure you want to continue?'),
                    ];
                }

                if ($isCurrentUser || $currentUser->can('deleteUsers')) {
                    $view = Craft::$app->getView();
                    $deleteId = sprintf('action-delete-%s', mt_rand());
                    $items[] = [
                        'id' => $deleteId,
                        'icon' => 'trash',
                        'label' => mb_ucfirst(t('Delete {type}', [
                            'type' => self::lowerDisplayName(),
                        ])),
                    ];

                    $view->registerJsWithVars(fn ($id, $userId, $redirect) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.sendActionRequest('POST', 'users/user-content-summary', {
    data: {userId: $userId}
  }).then((response) => {
    new Craft.DeleteUserModal($userId, {
      contentSummary: response.data,
      redirect: $redirect,
    })
  });
});
JS,
                        [
                            $view->namespaceInputId($deleteId),
                            $this->id,
                            /** @phpstan-ignore-next-line */
                            Crypt::encrypt(Edition::get() === Edition::Solo ? 'dashboard' : 'users'),
                        ]);
                }
            }
        }

        return $items;
    }

    private function _copyPasswordResetUrlActionItem(string $label, View $view): array
    {
        $id = sprintf('action-copy-password-reset-url-%s', mt_rand());

        $view->registerJsWithVars(fn ($id, $userId, $message) => <<<JS
$('#' + $id).on('activate', () => {
  Craft.elevatedSessionManager.requireElevatedSession(() => {
    Craft.sendActionRequest('POST', 'users/get-password-reset-url', {
      data: {userId: $userId}
    }).then((response) => {
      Craft.ui.createCopyTextPrompt({
        label: $message,
        value: response.data.url,
      })
    }).catch(({response}) => {
      Craft.cp.displayError(response.data.message);
    });
  });
});
JS, [
            $view->namespaceInputId($id),
            $this->id,
            t('Copy the activation URL'),
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
        if (! $this->can('accessCp')) {
            return [];
        }

        return $this->id ? Users::getUserPreferences($this->id) : [];
    }

    /**
     * Returns one of the user’s preferences by its key.
     *
     * @param  string  $key  The preference’s key
     * @param  mixed  $default  The default value, if the preference hasn’t been set
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
     *
     * @since 3.5.0
     */
    public function getPreferredLocale(): ?string
    {
        return $this->_validateLocale($this->getPreference('locale'), true);
    }

    /**
     * Returns whether the user prefers to have form fields autofocused on page load.
     *
     * @since 5.0.0
     */
    public function getAutofocusPreferred(): bool
    {
        return ! $this->getPreference('disableAutofocus');
    }

    /**
     * Validates and returns a locale ID.
     *
     * @param  bool  $checkAllLocales  Whether to check all known locale IDs, rather than just the app locales
     */
    private function _validateLocale(?string $locale, bool $checkAllLocales): ?string
    {
        if (! $locale) {
            return null;
        }

        $locales = $checkAllLocales
            ? I18N::getAllLocaleIds()
            : I18N::getAppLocaleIds();

        if ($locales->contains($locale)) {
            return $locale;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
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
     */
    #[AllowedInSandbox]
    public function getPhoto(): ?Asset
    {
        if (! isset($this->_photo)) {
            if (! $this->photoId) {
                return null;
            }

            $this->_photo = Craft::$app->getAssets()->getAssetById($this->photoId) ?? false;
        }

        return $this->_photo ?: null;
    }

    /**
     * Sets the user’s photo.
     */
    public function setPhoto(?Asset $photo = null): void
    {
        $this->_photo = $photo;
        $this->photoId = $photo->id ?? null;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function attributeHtml(string $attribute): string|Stringable
    {
        switch ($attribute) {
            case 'email':
                return $this->email ? Html::mailto(Html::encode($this->email)) : '';

            case 'groups':
                return implode(', ', array_map(fn (UserGroup $group) => Html::encode(t($group->name, category: 'site')), $this->getGroups()));

            case 'preferredLanguage':
                $language = $this->getPreferredLanguage();

                return $language ? I18N::getLocaleById($language)->getDisplayName(app()->getLocale()) : '';

            case 'preferredLocale':
                $locale = $this->getPreferredLocale();

                return $locale ? I18N::getLocaleById($locale)->getDisplayName(app()->getLocale()) : '';

            case 'is2faEnabled':
                $enabled = app(\CraftCms\Cms\Auth\Auth::class)->hasActiveMethod($this);
                if ($this->viewMode === 'cards') {
                    return Cp::statusLabelHtml([
                        'color' => $enabled ? Color::Teal : Color::Gray,
                        'label' => t('Two-Step Verification'),
                        'icon' => $enabled ? 'check' : 'xmark',
                    ]);
                }
                if (! $enabled) {
                    return '';
                }

                return Html::tag('span', '', [
                    'class' => 'checkbox-icon',
                    'role' => 'img',
                    'title' => t('Enabled'),
                    'aria' => [
                        'label' => t('Enabled'),
                    ],
                ]);

                // no break
            case 'isCredentialed':
                $value = $this->getIsCredentialed();
                if ($this->viewMode === 'cards') {
                    return Cp::statusLabelHtml([
                        'color' => $value ? Color::Teal : Color::Gray,
                        'label' => t('Credentialed'),
                        'icon' => $value ? 'check' : 'xmark',
                    ]);
                }
        }

        return parent::attributeHtml($attribute);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function htmlAttributes(string $context): array
    {
        $currentUser = Auth::user();

        return [
            'data' => [
                'suspended' => $this->suspended,
                'can-suspend' => $currentUser && Users::canSuspend($currentUser, $this),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function statusFieldHtml(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function metadata(): array
    {
        $formatter = I18N::getFormatter();

        return [
            t('Email') => Html::a($this->email, "mailto:$this->email"),
            t('Cooldown Time Remaining') => function () use ($formatter) {
                if (
                    ! $this->locked ||
                    ! Cms::config()->cooldownDuration ||
                    ($duration = $this->getRemainingCooldownTime()) === null
                ) {
                    return false;
                }

                return $formatter->asDuration($duration);
            },
            t('Created at') => $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT),
            t('Last login') => function () use ($formatter) {
                if ($this->pending) {
                    return false;
                }
                if (! $this->lastLoginDate) {
                    return t('Never');
                }

                return $formatter->asDatetime($this->lastLoginDate, Formatter::FORMAT_WIDTH_SHORT);
            },
            t('Last login fail') => function () use ($formatter) {
                if (! $this->locked || ! $this->lastInvalidLoginDate) {
                    return false;
                }

                return $formatter->asDatetime($this->lastInvalidLoginDate, Formatter::FORMAT_WIDTH_SHORT);
            },
            t('Login fail count') => function () use ($formatter) {
                if (! $this->locked) {
                    return false;
                }

                return $formatter->asDecimal($this->invalidLoginCount, 0);
            },
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @since 3.3.0
     */
    #[Override]
    public function getGqlTypeName(): string
    {
        return self::GQL_TYPE_NAME;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    #[Override]
    final public function beforeSave(bool $isNew): bool
    {
        if (
            ($isNew || $this->applyingDraft) &&
            ! Users::canCreateUsers()
        ) {
            return false;
        }

        if (Cms::config()->useEmailAsUsername) {
            $this->username = $this->email;
        }

        return parent::beforeSave($isNew);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     * @throws Exception
     */
    #[Override]
    public function afterSave(bool $isNew): void
    {
        if ($isNew && Edition::get() === Edition::Solo) {
            // Make sure they're an admin
            $this->admin = true;
        }

        // Get the user record
        if (! $isNew) {
            $model = UserModel::findOrFail($this->id);
            $isInactive = $model->active || $model->pending;

            if ($this->active !== $model->active) {
                throw new Exception('Unable to change a user’s active state like this.');
            }

            if ($this->pending !== $model->pending) {
                if ($isInactive) {
                    throw new Exception('Unable to change a user’s pending state like this.');
                }
                $model->pending = $this->pending;
            }

            if ($this->locked !== $model->locked) {
                throw new Exception('Unable to change a user’s locked state like this.');
            }

            if ($this->suspended !== $model->suspended) {
                throw new Exception('Unable to change a user’s suspended state like this.');
            }
        } else {
            $model = new UserModel;
            $model->id = $this->id;
            $model->active = $this->active;
            $model->pending = $this->pending;
            $model->locked = $this->locked;
            $model->suspended = $this->suspended;
        }

        $this->prepareNamesForSave();

        $model->photoId = $this->photoId;
        $model->affiliatedSiteId = $this->affiliatedSiteId;
        $model->admin = $this->admin;
        $model->username = $this->username;
        $model->fullName = $this->fullName;
        $model->firstName = $this->firstName;
        $model->lastName = $this->lastName;
        $model->email = $this->email;
        $model->passwordResetRequired = $this->passwordResetRequired;
        $model->unverifiedEmail = $this->unverifiedEmail;

        if ($changePassword = (isset($this->newPassword))) {
            $hash = Hash::make($this->newPassword);
            $this->lastPasswordChangeDate = now();

            $model->password = $this->password = $hash;
            $model->invalidLoginWindowStart = null;
            $model->invalidLoginCount = $this->invalidLoginCount = null;
            $model->lastPasswordChangeDate = $this->lastPasswordChangeDate;

            // If the user required a password reset *before this request*, then set passwordResetRequired to false
            if (! $isNew && $model->getOriginal('passwordResetRequired')) {
                $model->passwordResetRequired = $this->passwordResetRequired = false;
            }

            $newPassword = $this->newPassword;
            $this->newPassword = null;
        }

        // Capture the dirty attributes from the record
        $dirtyAttributes = array_keys($model->getDirty());

        $model->save();

        // Make sure that the photo is located in the right place
        if (! $isNew && $this->photoId) {
            Users::relocateUserPhoto($this);
        }

        $this->setDirtyAttributes($dirtyAttributes);

        parent::afterSave($isNew);

        if (Edition::get() === Edition::Team) {
            // Make sure they're in the Team group
            $group = UserGroups::getTeamGroup();
            if (! $this->isInGroup($group)) {
                Users::assignUserToGroups($this->id, [$group->id]);
            }
        }

        if (! $isNew && $changePassword && isset($newPassword) && ! app()->runningInConsole()) {
            Auth::logoutOtherDevices($newPassword);
        }

        if ($this->sendVerificationEmailAfterRequest && isset($this->unverifiedEmail)) {
            // Temporarily set the unverified email on the User so the verification email goes to the right place
            $originalEmail = $this->email;
            $this->email = $this->unverifiedEmail;

            $this->sendEmailVerificationNotification();

            // Put the original email back into place
            $this->email = $originalEmail;
        }
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function beforeDelete(): bool
    {
        if (! parent::beforeDelete()) {
            return false;
        }

        $elementsService = Craft::$app->getElements();

        // Do all this stuff within a transaction
        DbFacade::beginTransaction();

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
                    DbFacade::table($table)
                        ->where($column, $this->id)
                        ->update([
                            $column => $this->inheritorOnDelete->id,
                        ]);
                }
            } else {
                // Delete the entries
                $entryQuery = Entry::find()
                    ->authorId($this->id)
                    ->status(null)
                    ->site('*')
                    ->unique();

                $entryQuery->each(function (Entry $entry) use ($elementsService) {
                    // only delete their entry if they're the sole author
                    if ($entry->getAuthorIds() === [$this->id]) {
                        $elementsService->deleteElement($entry);
                    }
                }, 100);
            }

            DbFacade::commit();
        } catch (Throwable $e) {
            DbFacade::rollBack();
            throw $e;
        }

        $this->getAddressManager()->deleteNestedElements($this, $this->hardDelete);

        return true;
    }
}
