<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Validation;

use CraftCms\Cms\FieldLayout\LayoutElements\users\FullNameField;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Validation\ElementRules;
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
 * @property User $component
 */
final class UserRules extends ElementRules
{
    private const array TRIMMABLE_ATTRIBUTES = [
        'email',
        'unverifiedEmail',
        'fullName',
        'firstName',
        'lastName',
        'username',
    ];

    #[Override]
    public function prepareForValidation(?array $attributeNames = null): void
    {
        parent::prepareForValidation($attributeNames);

        $attributesToTrim = is_null($attributeNames)
            ? self::TRIMMABLE_ATTRIBUTES
            : array_intersect(self::TRIMMABLE_ATTRIBUTES, $attributeNames);

        foreach ($attributesToTrim as $attribute) {
            $value = $this->component->{$attribute};

            if (is_string($value)) {
                $this->component->{$attribute} = trim($value);
            }
        }
    }

    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $treatAsActive = $this->component->getIsCredentialed() || $this->component->inScenarios(
            User::SCENARIO_REGISTRATION,
            User::SCENARIO_ACTIVATION,
        );

        $unique = fn (string $column) => new UniqueCaseInsensitiveRule(Table::USERS, $column)
            ->where(fn (Builder $query) => $query
                ->where('active', true)
                ->orWhere('pending', true)
            )
            ->ignore($this->component->id);

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
                Rule::requiredIf(fn () => ! $this->component->getIsDraft()),
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
            && ($this->component
                ->getFieldLayout()
                ->getFirstVisibleElementByType(FullNameField::class, $this->component)
                ->required ?? false));

        if ($this->component->inScenarios(User::SCENARIO_LIVE)) {
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

        if (isset($this->component->id) && $this->component->passwordResetRequired) {
            $currentPassword = DB::table(Table::USERS)
                ->where('id', $this->component->id)
                ->value('password');
        }

        $rules['newPassword'] = [
            Rule::requiredIf(
                ! Cms::config()->deferPublicRegistrationPassword
                && $this->component->inScenarios(User::SCENARIO_PASSWORD, User::SCENARIO_REGISTRATION)
            ),
            new UserPasswordRule(
                forceDifferent: $this->component->passwordResetRequired,
                currentPassword: $currentPassword,
            ),
        ];

        return $rules;
    }
}
