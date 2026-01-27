<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Validation;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Shared\Rules\Trim;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Validation\Rules\UsernameRule;
use CraftCms\Cms\User\Validation\Rules\UserPasswordRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

/**
 * @extends ElementRules<User>
 */
final class UserRules extends ElementRules
{
    #[Override]
    public function scenarios(): array
    {
        return array_merge(parent::scenarios(), [
            User::SCENARIO_PASSWORD => ['newPassword'],
            User::SCENARIO_REGISTRATION => ['username', 'email', 'newPassword'],
            User::SCENARIO_ACTIVATION => ['username', 'email'],
        ]);
    }

    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $treatAsActive = $this->component->getIsCredentialed() || $this->inScenarios(
            User::SCENARIO_REGISTRATION,
            User::SCENARIO_ACTIVATION,
        );

        $trim = new Trim($this->component);

        $unique = fn (string $column) => Rule::unique(Table::USERS, $column)
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
                $trim,
            ],
            'unverifiedEmail' => [
                'nullable',
                'string',
                'max:255',
                'email',
                $trim,
            ],
            'fullName' => ['nullable', 'string', 'max:255', $trim, $noProtocol],
            'firstName' => ['nullable', 'string', 'max:255', $trim, $noProtocol],
            'lastName' => ['nullable', 'string', 'max:255', $trim, $noProtocol],
            'username' => ['nullable', 'string', 'max:255', $noProtocol, new UsernameRule, $trim],
            'password' => ['nullable', 'string', 'max:255'],
            'lastLoginAttemptIp' => ['nullable', 'string', 'max:45'],
        ]);

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
            new UserPasswordRule(
                forceDifferent: $this->component->passwordResetRequired,
                currentPassword: $currentPassword,
            ),
        ];

        return $rules;
    }
}
