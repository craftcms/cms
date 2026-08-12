<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Validation;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\LayoutElements\Users\FullNameField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Validation\Rules\UsernameRule;
use CraftCms\Cms\User\Validation\Rules\UserPasswordRule;
use CraftCms\Cms\Validation\Rules\UniqueCaseInsensitiveRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

/**
 * @extends ElementRules<User>
 *
 * @property User $subject
 */
class UserRules extends ElementRules
{
    /**
     * @since 4.4.8
     */
    public const string SCENARIO_ACTIVATION = 'activation';

    public const string SCENARIO_REGISTRATION = 'registration';

    public const string SCENARIO_PASSWORD = 'password';

    private const array TRIMMABLE_ATTRIBUTES = [
        'email',
        'unverifiedEmail',
        'fullName',
        'firstName',
        'lastName',
        'username',
    ];

    #[Override]
    public function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $attributesToTrim = is_null($this->validationAttributes)
            ? self::TRIMMABLE_ATTRIBUTES
            : array_intersect(self::TRIMMABLE_ATTRIBUTES, $this->validationAttributes);

        foreach ($attributesToTrim as $attribute) {
            $value = $this->subject->{$attribute};

            if (is_string($value)) {
                $this->subject->{$attribute} = trim($value);
            }
        }
    }

    /** @return array<string, array<mixed>> */
    #[Override]
    public function rules(): array
    {
        $rules = parent::rules();

        $treatAsActive = $this->subject->getIsCredentialed() || $this->inScenarios(
            self::SCENARIO_REGISTRATION,
            self::SCENARIO_ACTIVATION,
        );

        $unique = fn (string $column) => new UniqueCaseInsensitiveRule(Table::USERS, $column)
            ->where(fn (Builder $query) => $query
                ->where('active', true)
                ->orWhere('pending', true)
            )
            ->ignore($this->subject->id);

        $noProtocol = function ($attribute, $value, $fail) {
            if (str_contains($value, '://')) {
                $fail(t('Invalid value “{value}”.'));
            }
        };

        $rules = array_merge($rules, [
            'lastLoginDate' => ['nullable', 'date'],
            'lastInvalidLoginDate' => ['nullable', 'date'],
            'lockoutDate' => ['nullable', 'date'],
            'lastPasswordChangeDate' => ['nullable', 'date'],
            'invalidLoginCount' => ['nullable', 'integer'],
            'photoId' => ['nullable', 'integer'],
            'affiliatedSiteId' => ['nullable', 'integer'],
            'email' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => ! $this->subject->getIsDraft()),
                'email',
            ],
            'unverifiedEmail' => [
                'nullable',
                'string',
                'max:255',
                'email',
            ],
            'fullName' => ['nullable', 'string', 'max:255', $noProtocol],
            'firstName' => ['nullable', 'string', 'max:255', $noProtocol],
            'lastName' => ['nullable', 'string', 'max:255', $noProtocol],
            'username' => ['nullable', 'string', 'max:255', $noProtocol, new UsernameRule],
            'password' => ['nullable', 'string', 'max:255'],
            'lastLoginAttemptIp' => ['nullable', 'string', 'max:45'],
        ]);

        $requiredNameField = (fn (bool $requiredWhenFirstAndLastNameFields) => Cms::config()->showFirstAndLastNameFields === $requiredWhenFirstAndLastNameFields
            && ($this->subject
                ->getFieldLayout()
                ->getFirstVisibleElementByType(FullNameField::class, $this->subject)
                ->required ?? false));

        if ($this->inScenarios(self::SCENARIO_LIVE)) {
            $rules['firstName'][] = Rule::requiredIf($requiredNameField(true));
            $rules['lastName'][] = Rule::requiredIf($requiredNameField(true));
            $rules['fullName'][] = Rule::requiredIf($requiredNameField(false));
        }

        if (Cms::isInstalled()) {
            if ($treatAsActive) {
                $rules['email'][] = $unique('email');
            }

            if (! Cms::config()->useEmailAsUsername) {
                array_unshift($rules['username'], Rule::requiredIf($treatAsActive));

                if ($treatAsActive) {
                    $rules['username'][] = $unique('username');
                }

                $rules['unverifiedEmail'][] = $unique('email');
            }
        }

        $currentPassword = null;

        if (isset($this->subject->id) && $this->subject->passwordResetRequired) {
            $currentPassword = DB::table(Table::USERS)
                ->where('id', $this->subject->id)
                ->value('password');
        }

        $rules['newPassword'] = [
            Rule::requiredIf(
                ! Cms::config()->deferPublicRegistrationPassword
                && $this->inScenarios(self::SCENARIO_PASSWORD, self::SCENARIO_REGISTRATION)
            ),
            new UserPasswordRule(
                forceDifferent: $this->subject->passwordResetRequired,
                currentPassword: $currentPassword,
            ),
        ];

        return $rules;
    }

    #[Override]
    protected function validationRules(): array
    {
        $rules = parent::validationRules();

        if ($this->inScenarios(self::SCENARIO_PASSWORD)) {
            return Arr::only($rules, ['newPassword']);
        }

        if ($this->inScenarios(self::SCENARIO_REGISTRATION)) {
            return Arr::only($rules, ['username', 'email', 'newPassword']);
        }

        if ($this->inScenarios(self::SCENARIO_ACTIVATION)) {
            return Arr::only($rules, ['username', 'email']);
        }

        return $rules;
    }
}
