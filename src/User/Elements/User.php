<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Elements;

use Closure;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Auth\AuthMethods;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;
use CraftCms\Cms\Element\DeletionBlockers\EntryAuthorsBlocker;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Enums\ElementActionContext;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Http\ViewModels\UserEditViewModel;
use CraftCms\Cms\Shared\Concerns\HasNames;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Assets as AssetsService;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Formatter;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Actions\SuspendUsers;
use CraftCms\Cms\User\Actions\UnsuspendUsers;
use CraftCms\Cms\User\Concerns\CraftUserTrait;
use CraftCms\Cms\User\Concerns\LegacyConstants;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Events\UserFriendlyNameResolving;
use CraftCms\Cms\User\Events\UserNameResolving;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Validation\UserRules;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use DateInterval;
use DateTimeInterface;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB as DbFacade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Traits\Macroable;
use Override;
use Stringable;

use function CraftCms\Cms\currentUser;
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
 * @property-read DateTimeInterface|null $cooldownEndTime the time when the user will be over their cooldown period
 * @property-read array<string, mixed> $preferences the user’s preferences
 * @property-read bool $isCredentialed whether the user account can be logged into
 * @property-read bool $isCurrent whether this is the current logged-in user
 * @property-read string|null $preferredLanguage the user’s preferred language
 * @property-read string|null $preferredLocale the user’s preferred formatting locale
 */
#[Ruleset(UserRules::class)]
class User extends Element implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, CraftUser, HasLocalePreference, MustVerifyEmailContract
{
    use Authenticatable {
        getAuthPassword as getAuthPasswordAuthenticatable;
    }
    use Authorizable;
    use CanResetPassword, CraftUserTrait {
        CraftUserTrait::sendPasswordResetNotification insteadof CanResetPassword;
    }
    use ConfirmsPasswords;
    use HasNames;
    use LegacyConstants;
    use Macroable;
    use Notifiable;

    public const string GQL_TYPE_NAME = 'User';

    /** @var string[] */
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

    public const string STATUS_INACTIVE = 'inactive';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_SUSPENDED = 'suspended';

    public const string STATUS_LOCKED = 'locked';

    /**
     * These attributes can only be saved through the User
     * routes. The generic save element routes will not
     * allow changes to these attributes.
     */
    public const array SENSITIVE_ATTRIBUTES = [
        'active',
        'admin',
        'affiliatedSiteId',
        'currentPassword',
        'email',
        'invalidLoginCount',
        'lastInvalidLoginDate',
        'lastLoginAttemptIp',
        'lastLoginDate',
        'lastPasswordChangeDate',
        'locked',
        'lockoutDate',
        'newPassword',
        'password',
        'passwordResetRequired',
        'pending',
        'photoId',
        'suspended',
        'unverifiedEmail',
    ];

    /**
     * @var int|null Photo asset ID
     */
    #[AllowedInSandbox]
    public ?int $photoId = null;

    /**
     * @var bool Active
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
     */
    #[AllowedInSandbox]
    public ?int $affiliatedSiteId = null;

    /**
     * @var DateTimeInterface|null Last login date
     */
    #[AllowedInSandbox]
    public ?DateTimeInterface $lastLoginDate = null;

    /**
     * @var int|null Invalid login count
     */
    public ?int $invalidLoginCount = null;

    /**
     * @var DateTimeInterface|null Last invalid login date
     */
    public ?DateTimeInterface $lastInvalidLoginDate = null;

    /**
     * @var DateTimeInterface|null Lockout date
     */
    public ?DateTimeInterface $lockoutDate = null;

    /**
     * @var bool Whether the user has a dashboard
     */
    public bool $hasDashboard = false;

    /**
     * @var bool Password reset required
     */
    public bool $passwordResetRequired = false;

    /**
     * @var DateTimeInterface|null Last password change date
     */
    public ?DateTimeInterface $lastPasswordChangeDate = null;

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
     * @var ElementCollection<int, Address> Addresses
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

    public function __construct($config = [])
    {
        parent::__construct($config);

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

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthPassword(): ?string
    {
        $password = $this->getAuthPasswordAuthenticatable();

        if (is_null($password)) {
            return null;
        }

        // Ensure the password starts with `$2y$` for BC with older passwords
        // (h/t https://stackoverflow.com/a/79217475)
        return Str::replaceStart('$2a$', '$2y$', $password);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function methodAllowedInSandbox(string $method): bool
    {
        // Allow can() to be called from sandboxed templates
        return strtolower($method) === 'can' || parent::methodAllowedInSandbox($method);
    }

    public function asElement(): self
    {
        return $this;
    }

    public function getKey(): ?int
    {
        return $this->id;
    }

    #[Override]
    public static function displayName(): string
    {
        return t('User');
    }

    #[Override]
    public static function objectTemplateSuggestions(): array
    {
        return [
            ...parent::objectTemplateSuggestions(),
            'username' => t('Username'),
            'email' => t('Email'),
            'firstName' => t('First Name'),
            'lastName' => t('Last Name'),
            'fullName' => t('Full Name'),
            'preferredLanguage' => t('Preferred Language'),
            'preferredLocale' => t('Preferred Locale'),
        ];
    }

    #[Override]
    public static function lowerDisplayName(): string
    {
        return t('user');
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return t('Users');
    }

    #[Override]
    public static function pluralLowerDisplayName(): string
    {
        return t('users');
    }

    public static function refHandle(): string
    {
        return 'user';
    }

    #[Override]
    public static function editViewModelClass(): string
    {
        return UserEditViewModel::class;
    }

    #[Override]
    public static function trackChanges(): bool
    {
        return true;
    }

    #[Override]
    public static function hasThumbs(): bool
    {
        return true;
    }

    #[Override]
    public static function hasStatuses(): bool
    {
        return true;
    }

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

    #[Override]
    public static function find(): UserQuery
    {
        return new UserQuery;
    }

    #[Override]
    public static function createCondition(): UserCondition
    {
        return new UserCondition(self::class);
    }

    /** @return array<int, array<array-key, scalar|array<array-key, scalar|array<array-key, scalar|null>|null>|null>> */
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

    /** @return array<int, class-string|object> */
    #[Override]
    protected static function defineActions(string $source): array
    {
        return collect()
            ->when(Gate::check('moderateUsers'), fn ($actions) => $actions->push(SuspendUsers::class, UnsuspendUsers::class))
            ->push(Restore::class)
            ->all();
    }

    #[Override]
    protected static function defineSearchableAttributes(): array
    {
        return ['username', 'fullName', 'firstName', 'lastName', 'email'];
    }

    /** @return array<array-key, string|array<string, scalar|callable|null>> */
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

    /** @return array<string, array<string, string>> */
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

    #[Override]
    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        /** @var UserQuery $elementQuery */
        if ($attribute === 'groups') {
            $elementQuery->withGroups();
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    /** @return array<string, array<string, string|Stringable|callable>> */
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
                'placeholder' => fn () => Template::raw(app(StatusHtml::class)->statusLabelHtml([
                    'color' => Color::Teal,
                    'label' => t('Credentialed'),
                    'icon' => 'check',
                ])),
            ],
            'lastLoginDate' => [
                'label' => t('Last Login'),
                'placeholder' => fn () => now()->subDays(14),
            ],
            'is2faEnabled' => [
                'label' => t('Two-Step Verification'),
                'placeholder' => fn () => Template::raw(app(StatusHtml::class)->statusLabelHtml([
                    'color' => Color::Teal,
                    'label' => t('Two-Step Verification'),
                    'icon' => 'check',
                ])),
            ],
        ]);
    }

    /** @return array<string, class-string|array<array-key, mixed>|callable>|null|false */
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

    /** @return array<int, array<string, string>> */
    #[Override]
    protected function crumbs(): array
    {
        if (Edition::get() === Edition::Solo) {
            return [];
        }

        return [
            [
                'label' => t('Users'),
                'href' => Url::cpUrl('users'),
            ],
        ];
    }

    protected function uiLabel(): ?string
    {
        return $this->getName() ?: $this->email;
    }

    /** @return string[] */
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

    #[Override]
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'groups';
        $names[] = 'addresses';
        $names[] = 'photo';

        return $names;
    }

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

    /** @return string[] */
    #[Override]
    public function safeAttributes(): array
    {
        return Arr::except(parent::safeAttributes(), ['photoId']);
    }

    /** @param array<string, mixed> $values */
    #[Override]
    public function setAttributesFromRequest(array $values): void
    {
        unset(
            $values['invalidLoginCount'],
            $values['lastInvalidLoginDate'],
            $values['lastLoginAttemptIp'],
            $values['lastLoginDate'],
            $values['lastPasswordChangeDate'],
            $values['lockoutDate'],
            $values['newPassword'],
            $values['password'],
            $values['unverifiedEmail'],
            $values['verificationCodeIssuedDate'],
        );

        if (isset($values['email'])) {
            $values['email'] = trim((string) $values['email']);
            if ($values['email'] === '' || $values['email'] === $this->email) {
                unset($values['email']);
            }
        }

        if (isset($values['email']) && $this->email !== null) {
            // make sure they have an elevated session
            if (! $this->isPasswordConfirmed()) {
                abort(400, t('An elevated session is required to change a user’s email.'));
            }

            // are they allowed to set the email?
            if ($this->getIsCurrent() || Gate::check('administrateUsers')) {
                if (
                    Edition::get()->value >= Edition::Pro->value &&
                    ProjectConfig::get('users.requireEmailVerification') &&
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

        parent::setAttributesFromRequest($values);
    }

    #[Override]
    public function setAttributes($values): void
    {
        if (array_key_exists('firstName', $values) || array_key_exists('lastName', $values)) {
            // Unset fullName so NameTrait::prepareNamesForSave() can set it
            $this->fullName = null;
        } elseif (array_key_exists('fullName', $values)) {
            // Unset firstName and lastName so NameTrait::prepareNamesForSave() can set them
            $this->firstName = $this->lastName = null;
        }

        parent::setAttributes($values);
    }

    /**
     * Returns whether the user account can be logged into.
     */
    public function getIsCredentialed(): bool
    {
        return $this->active || $this->pending;
    }

    /**
     * Returns whether the user has a password.
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
     */
    #[AllowedInSandbox]
    public function getHasSsoIdentity(): bool
    {
        return $this->id !== null && app(OAuth::class)->hasIdentity($this->id);
    }

    #[Override]
    public function getFieldLayout(): ?FieldLayout
    {
        return app(Fields::class)->getLayoutByType(User::class);
    }

    /**
     * Gets the user’s addresses.
     *
     * @return ElementCollection<int, Address>
     */
    #[AllowedInSandbox]
    public function getAddresses(): ElementCollection
    {
        if (isset($this->_addresses)) {
            return $this->_addresses;
        }

        if (! $this->id) {
            return new ElementCollection;
        }

        return $this->_addresses = $this->createAddressQuery()
            ->whereNull('fieldId')
            ->get();
    }

    /**
     * Returns a nested element manager for the user’s addresses.
     */
    public function getAddressManager(): NestedElementManager
    {
        return $this->_addressManager ??= new NestedElementManager(
            Address::class,
            fn () => $this->createAddressQuery(),
            [
                'attribute' => 'addresses',
                'propagationMethod' => PropagationMethod::None,
            ],
        );
    }

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
            ->orderBy('id');
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
     * @param  UserGroup[]  $groups  An array of UserGroup objects.
     */
    public function setGroups(array $groups): void
    {
        if (Edition::isAtLeast(Edition::Pro)) {
            $this->_groups = $groups;
        }
    }

    /**
     * Returns whether the user is in a specific group.
     *
     * @param  int|string|UserGroup  $group  The user group model, its handle, or ID.
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
            return collect($this->getGroups())->contains('id', $group);
        }

        /** @phpstan-ignore argument.type */
        return collect($this->getGroups())->containsStrict('handle', $group);
    }

    /**
     * Returns whether the user is in any/all the given user groups.
     *
     * By default, `true` will be returned if the user is in *any* of the groups. To change that so `true` is only
     * returned if the user is in *all* of the groups, pass `true` to the second argument.
     *
     * @param  array<int|string|UserGroup>  $groups  The user groups, handles, or IDs
     * @param  bool  $all  Whether to only return `true` if the user is in *all* of the provided groups
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
     * Returns the user’s full name or username.
     */
    #[AllowedInSandbox]
    public function getName(): string
    {
        return $this->_name ??= $this->_defineName();
    }

    private function _defineName(): string
    {
        event($event = new UserNameResolving($this));

        return $event->name ?? $this->defaultName();
    }

    /**
     * Sets the user’s name.
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
        event($event = new UserFriendlyNameResolving($this));

        return $event->name ?? $this->defaultFriendlyName();
    }

    /**
     * Sets the user’s friendly name.
     */
    public function setFriendlyName(string $friendlyName): void
    {
        $this->_friendlyName = $friendlyName;
    }

    /**
     * Returns the user’s affiliated site, if they have one.
     */
    #[AllowedInSandbox]
    public function getAffiliatedSite(): ?Site
    {
        if ($this->affiliatedSiteId === null || ! Sites::isMultiSite()) {
            return null;
        }

        return Sites::getSiteById($this->affiliatedSiteId, true);
    }

    #[Override]
    public function getStatus(): string
    {
        // If they're disabled or archived, go with that
        $status = parent::getStatus();

        return match (true) {
            $status !== self::STATUS_ENABLED => $status,
            $this->suspended => self::STATUS_SUSPENDED,
            $this->archived => self::STATUS_ARCHIVED,
            $this->pending => self::STATUS_PENDING,
            $this->active => self::STATUS_ACTIVE,
            default => self::STATUS_INACTIVE,
        };
    }

    protected function thumbUrl(int $size): ?string
    {
        if ($photo = $this->getPhoto()) {
            return AssetsService::getThumbUrl($photo, $size, iconFallback: false);
        }

        return null;
    }

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
<svg version="1.1" baseProfile="full" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
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

    protected function thumbAlt(): string
    {
        return $this->getPhoto()->alt ?? $this->getName();
    }

    #[Override]
    protected function hasRoundedThumb(): bool
    {
        return true;
    }

    public function createAnother(): ElementInterface
    {
        return new self;
    }

    /**
     * Returns whether this is the current logged-in user.
     */
    public function getIsCurrent(): bool
    {
        if (! $this->id) {
            return false;
        }

        return currentUser()?->getCraftUserId() === $this->id;
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
    public function getCooldownEndTime(): ?DateTimeInterface
    {
        // There was an old bug where a user's lockoutDate could be null if they've
        // passed their cooldownDuration already, but their account status is still locked.
        // If that's the case, just let it return null as if they are past the cooldownDuration.
        if ($this->locked && $this->lockoutDate) {
            $generalConfig = Cms::config();
            $cooldownDuration = (int) $generalConfig->cooldownDuration;
            $cooldownEnd = Date::instance($this->lockoutDate);

            if ($cooldownDuration !== 0) {
                $cooldownEnd->addSeconds($cooldownDuration);
            }

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
            $currentTime = now('UTC');
            $cooldownEnd = $this->getCooldownEndTime();
            $cooldownEnd = $cooldownEnd ? Date::instance($cooldownEnd)->setTimezone('UTC') : null;

            if ($cooldownEnd && $currentTime < $cooldownEnd) {
                return $currentTime->diff($cooldownEnd);
            }
        }

        return null;
    }

    protected function cpEditUrl(): ?string
    {
        if (request()->isCpRequest() && $this->getIsCurrent()) {
            return Url::cpUrl('myaccount');
        }

        if (Edition::get() === Edition::Solo) {
            return null;
        }

        return Url::cpUrl("users/$this->id");
    }

    /**
     * The account actions for the Inertia editor — the Form-system counterpart
     * to the items {@see safeActionMenuItems()} and
     * {@see destructiveActionMenuItems()} build with inline jQuery.
     *
     * Everything here posts to an existing `users/*` action; the client
     * dispatches them, so nothing needs an inline script.
     *
     * @return list<array<string, mixed>>
     */
    #[Override]
    protected function extraActionMenuDescriptors(
        ElementActionContext $context = ElementActionContext::Editor,
    ): array {
        $currentUser = currentUser();

        if (
            ! $this->id ||
            $this->getIsUnpublishedDraft() ||
            ! $currentUser instanceof CraftUser ||
            Edition::get() === Edition::Solo
        ) {
            return [];
        }

        $isCurrentUser = $this->getIsCurrent();
        $canAdministrateUsers = $currentUser->can('administrateUsers');
        $status = $this->getStatus();

        $items = [
            ...$this->statusActionDescriptors($currentUser, $status, $isCurrentUser),
            ...$this->passwordResetActionDescriptors($canAdministrateUsers, $status, $isCurrentUser),
            ...$this->sessionActionDescriptors($currentUser, $isCurrentUser),
        ];

        // Suspending and deactivating revoke access, so they're flagged the way
        // deleting is and sort with it.
        if (! $isCurrentUser && Users::canSuspend($currentUser, $this) && $this->active && ! $this->suspended) {
            $items[] = [
                'label' => t('Suspend'),
                'icon' => 'ban',
                'destructive' => true,
                'behavior' => [
                    'type' => 'submit',
                    'actionUrl' => Url::actionUrl('users/suspend-user'),
                    'params' => ['userId' => $this->id],
                ],
            ];
        }

        if (Gate::check('deactivate', $this) && ($this->active || $this->pending)) {
            $items[] = [
                'label' => t('Deactivate'),
                'icon' => 'disabled',
                'destructive' => true,
                'behavior' => [
                    'type' => 'submit',
                    'actionUrl' => Url::actionUrl('users/deactivate-user'),
                    'params' => ['userId' => $this->id],
                    'confirm' => t('Deactivating a user revokes their ability to sign in. Are you sure you want to continue?'),
                ],
            ];
        }

        return $items;
    }

    /**
     * Actions that move the account between statuses — enabling, activating,
     * unsuspending, unlocking, and the password-reset emails that go with them.
     *
     * @return list<array<string, mixed>>
     */
    private function statusActionDescriptors(CraftUser $currentUser, string $status, bool $isCurrentUser): array
    {
        $items = [];

        switch ($status) {
            case Element::STATUS_ARCHIVED:
            case Element::STATUS_DISABLED:
                if (Gate::check('save', $this)) {
                    $items[] = $this->userActionDescriptor(t('Enable'), 'users/enable-user');
                }
                break;

            case self::STATUS_INACTIVE:
            case self::STATUS_PENDING:
                // Activation only means something for an account that can be
                // emailed about it.
                if (! $this->email) {
                    break;
                }

                if (Gate::check('sendActivationEmail', $this)) {
                    $items[] = $this->userActionDescriptor(t('Send activation email'), 'users/send-activation-email', 'paperplane');
                }

                if (Gate::check('activate', $this)) {
                    if (! $this->password && (! $this->admin || $currentUser->isAdmin())) {
                        $items[] = $this->copyUrlDescriptor(t('Copy activation URL…'), 'users/get-password-reset-url');
                    }

                    $items[] = $this->userActionDescriptor(t('Activate account'), 'users/activate-user', 'enabled');
                }
                break;

            case self::STATUS_SUSPENDED:
                if (Users::canSuspend($currentUser, $this)) {
                    $items[] = $this->userActionDescriptor(t('Unsuspend'), 'users/unsuspend-user', 'enabled');
                }
                break;

            case self::STATUS_ACTIVE:
                if (
                    $this->locked &&
                    ! $isCurrentUser &&
                    ($currentUser->isAdmin() || ! $this->admin) &&
                    $currentUser->can('moderateUsers') &&
                    (
                        ($impersonatorId = app(Impersonation::class)->getImpersonatorId()) === null ||
                        $this->id !== $impersonatorId
                    )
                ) {
                    $items[] = $this->userActionDescriptor(t('Unlock'), 'users/unlock-user');
                }

                if (! $isCurrentUser && Gate::check('editUsers')) {
                    $items[] = $this->userActionDescriptor(t('Send password reset email'), 'users/send-password-reset-email', 'paperplane');

                    if ($currentUser->can('administrateUsers') && (! $this->admin || $currentUser->isAdmin())) {
                        $items[] = $this->copyUrlDescriptor(t('Copy password reset URL…'), 'users/get-password-reset-url');
                    }
                }
                break;
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    private function passwordResetActionDescriptors(bool $canAdministrateUsers, string $status, bool $isCurrentUser): array
    {
        if (
            ! $canAdministrateUsers ||
            $isCurrentUser ||
            ! in_array($status, [self::STATUS_PENDING, self::STATUS_ACTIVE], true)
        ) {
            return [];
        }

        return [$this->passwordResetRequired
            ? [
                ...$this->userActionDescriptor(
                    t('Don’t require a password reset on next login'),
                    'users/remove-password-reset-requirement',
                    'asterisk-slash',
                ),
                'color' => Color::Gray->value,
            ]
            : $this->userActionDescriptor(
                t('Require a password reset on next login'),
                'users/require-password-reset',
                'asterisk',
            ),
        ];
    }

    /**
     * Signing in as this user, which always needs a fresh password first.
     *
     * @return list<array<string, mixed>>
     */
    private function sessionActionDescriptors(CraftUser $currentUser, bool $isCurrentUser): array
    {
        if ($isCurrentUser || ! Users::canImpersonate($currentUser, $this)) {
            return [];
        }

        return [
            [
                'label' => trim($this->getName())
                    ? t('Sign in as {user}', ['user' => $this->getName()])
                    : t('Sign in as user'),
                'icon' => 'key',
                'behavior' => [
                    'type' => 'submit',
                    'actionUrl' => Url::actionUrl('users/impersonate'),
                    'params' => ['userId' => $this->id],
                    'requireElevatedSession' => true,
                ],
            ],
            $this->copyUrlDescriptor(t('Copy impersonation URL…'), 'users/get-impersonation-url'),
        ];
    }

    /** @return array<string, mixed> */
    private function userActionDescriptor(string $label, string $action, ?string $icon = null): array
    {
        return array_filter([
            'label' => $label,
            'icon' => $icon,
            'behavior' => [
                'type' => 'submit',
                'actionUrl' => Url::actionUrl($action),
                'params' => ['userId' => $this->id],
            ],
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * An action that fetches a one-off URL and offers it for copying, behind an
     * elevated session — these URLs grant access to the account.
     *
     * @return array<string, mixed>
     */
    private function copyUrlDescriptor(string $label, string $action): array
    {
        return [
            'label' => $label,
            'icon' => 'clipboard',
            'behavior' => [
                'type' => 'copyUrl',
                'actionUrl' => Url::actionUrl($action),
                'params' => ['userId' => $this->id],
                'prompt' => t('Copy the URL, and open it in a new private window.'),
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    #[Override]
    protected function safeActionMenuItems(): array
    {
        if (! $this->id || $this->getIsUnpublishedDraft()) {
            return parent::safeActionMenuItems();
        }

        $currentUser = currentUser();

        if (! $currentUser instanceof CraftUser) {
            return parent::safeActionMenuItems();
        }

        $canAdministrateUsers = $currentUser->can('administrateUsers');
        $canModerateUsers = $currentUser->can('moderateUsers');
        $canActivate = Gate::check('activate', $this);
        $canSendActivationEmail = Gate::check('sendActivationEmail', $this);

        $isCurrentUser = $this->getIsCurrent();

        $statusItems = [];
        $sessionItems = [];
        $miscItems = [];

        if (Edition::get() !== Edition::Solo) {
            $status = $this->getStatus();

            switch ($status) {
                case Element::STATUS_ARCHIVED:
                case Element::STATUS_DISABLED:
                    if (Gate::check('save', $this)) {
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
                        if ($canSendActivationEmail) {
                            $statusItems[] = [
                                'icon' => 'paperplane',
                                'label' => t('Send activation email'),
                                'action' => 'users/send-activation-email',
                                'params' => [
                                    'userId' => $this->id,
                                ],
                            ];
                        }
                        if ($canActivate) {
                            // Only need to show the "Copy activation URL" option if they don't have a password
                            if (! $this->password && (! $this->admin || $currentUser->isAdmin())) {
                                $statusItems[] = $this->_copyPasswordResetUrlActionItem(t('Copy activation URL…'));
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
                            ($currentUser->isAdmin() || ! $this->admin) &&
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
                        if ($canAdministrateUsers && (! $this->admin || $currentUser->isAdmin())) {
                            $statusItems[] = $this->_copyPasswordResetUrlActionItem(t('Copy password reset URL…'));
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

                    HtmlStack::jsWithVars(fn ($id, $userId, $message) => <<<JS
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
                        InputNamespace::namespaceId($copyImpersonationUrlId),
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

    /** @return array<int, array<string, mixed>> */
    #[Override]
    protected function destructiveActionMenuItems(): array
    {
        if (! $this->id || $this->getIsUnpublishedDraft()) {
            return parent::destructiveActionMenuItems();
        }

        $currentUser = currentUser();

        if (! $currentUser instanceof CraftUser) {
            return parent::destructiveActionMenuItems();
        }

        $currentUser->can('administrateUsers');

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

            if (Gate::check('deactivate', $this) && ($this->active || $this->pending)) {
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
        }

        return [
            ...$items,
            ...parent::destructiveActionMenuItems(),
        ];
    }

    /** @return array<string, string> */
    private function _copyPasswordResetUrlActionItem(string $label): array
    {
        $id = sprintf('action-copy-password-reset-url-%s', mt_rand());

        HtmlStack::jsWithVars(fn ($id, $userId, $message) => <<<JS
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
            InputNamespace::namespaceId($id),
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
     * @return array<string, mixed> The user’s preferences.
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
     * Returns the user’s preferred language, if they have one.
     *
     * @return string|null The preferred language
     */
    public function getPreferredLanguage(): ?string
    {
        return $this->_validateLocale($this->getPreference('language'), false);
    }

    public function preferredLocale(): ?string
    {
        return $this->getPreferredLanguage();
    }

    /**
     * Returns the user’s preferred locale to be used for date/number formatting, if they have one.
     *
     * If the user doesn’t have a preferred locale, their preferred language will be used instead.
     *
     * @return string|null The preferred locale
     */
    public function getPreferredLocale(): ?string
    {
        return $this->_validateLocale($this->getPreference('locale'), true);
    }

    /**
     * Returns whether the user prefers to have form fields autofocused on page load.
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

    #[Override]
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        switch ($plan->handle) {
            case 'photo':
                /** @var Asset|null $photo */
                $photo = $elements[0] ?? null;
                $this->setPhoto($photo);
                break;
            case 'addresses':
                /** @var Address[] $elements */
                $this->_addresses = ElementCollection::make($elements);
                break;
        }

        parent::setEagerLoadedElements($handle, $elements, $plan);
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

            $this->_photo = AssetsService::getAssetById($this->photoId) ?? false;
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
                $enabled = app(AuthMethods::class)->hasActiveMethod($this);
                if ($this->viewMode === 'cards') {
                    return app(StatusHtml::class)->statusLabelHtml([
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
                    return app(StatusHtml::class)->statusLabelHtml([
                        'color' => $value ? Color::Teal : Color::Gray,
                        'label' => t('Credentialed'),
                        'icon' => $value ? 'check' : 'xmark',
                    ]);
                }
        }

        return parent::attributeHtml($attribute);
    }

    /** @return array<string, array<string, bool>> */
    #[Override]
    protected function htmlAttributes(string $context): array
    {
        $currentUser = currentUser();

        return [
            'data' => [
                'suspended' => $this->suspended,
                'can-suspend' => $currentUser instanceof CraftUser && Users::canSuspend($currentUser, $this),
            ],
        ];
    }

    /**
     * A user's status follows from the account actions (activate, suspend,
     * deactivate…), not from an editable switch, so the editor sidebar shows
     * none — matching {@see statusFieldHtml()}, which renders nothing.
     */
    #[Override]
    protected function showStatusField(): bool
    {
        return false;
    }

    #[Override]
    protected function statusFieldHtml(): string
    {
        return '';
    }

    /** @return array<string, Closure|string|Stringable> */
    #[Override]
    protected function metadata(): array
    {
        $formatter = I18N::getFormatter();

        return [
            // A brand-new account has no email yet, and the editor renders this
            // for unsaved drafts too.
            t('Email') => fn () => $this->email
                ? Html::a($this->email, "mailto:$this->email")
                : false,
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
            t('Created at') => $formatter->asDatetime($this->dateCreated, Formatter::FORMAT_WIDTH_SHORT, true),
            t('Last login') => function () use ($formatter) {
                if ($this->pending) {
                    return false;
                }
                if (! $this->lastLoginDate) {
                    return t('Never');
                }

                return $formatter->asDatetime($this->lastLoginDate, Formatter::FORMAT_WIDTH_SHORT, true);
            },
            t('Last login fail') => function () use ($formatter) {
                if (! $this->locked || ! $this->lastInvalidLoginDate) {
                    return false;
                }

                return $formatter->asDatetime($this->lastInvalidLoginDate, Formatter::FORMAT_WIDTH_SHORT, true);
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
     * @param  ElementCollection<int, User>  $elements
     * @return DeletionBlockerInterface[]
     */
    #[Override]
    public static function deletionBlockers(ElementCollection $elements, bool $hardDelete): array
    {
        return [
            new EntryAuthorsBlocker($elements, $hardDelete),
            ...parent::deletionBlockers($elements, $hardDelete),
        ];
    }

    #[Override]
    public function getGqlTypeName(): string
    {
        return self::GQL_TYPE_NAME;
    }

    // Events
    // -------------------------------------------------------------------------

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

            $isNew
                ? Users::sendActivationEmail($this)
                : Users::sendNewEmailVerifyEmail($this);

            // Put the original email back into place
            $this->email = $originalEmail;
        }
    }

    #[Override]
    public function beforeDelete(): bool
    {
        if (! parent::beforeDelete()) {
            return false;
        }

        $this->getAddressManager()->deleteNestedElements($this, $this->hardDelete);

        return true;
    }
}
