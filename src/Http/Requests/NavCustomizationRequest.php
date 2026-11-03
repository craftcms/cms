<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use Closure;
use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Cp\NavCustomizer;
use CraftCms\Cms\User\Conditions\UserCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\AnyOf;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * A nav builder's edited tree.
 *
 * The tree itself is checked by {@see NavCustomizer::diff()}, which keeps
 * only what makes sense against the nav it's customizing.
 *
 * @since 6.0.0
 */
class NavCustomizationRequest extends FormRequest
{
    /** @return array<string, list<string|AnyOf|Closure>> */
    public function rules(): array
    {
        return [
            'nav' => ['present', 'array', 'max:500'],
            'nav.*' => ['array'],
            // Custom links sit at the top level or in a section.
            'nav.*.url' => self::linkRules(),
            'nav.*.children.*.url' => self::linkRules(),
            'nav.*.userCondition' => self::userConditionRules(),
            'nav.*.children.*.userCondition' => self::userConditionRules(),
        ];
    }

    /**
     * The whole tree. `validated()` would keep only the link URLs, the one
     * thing in it with rules of its own.
     *
     * @return list<array<string, mixed>>
     */
    public function nav(): array
    {
        return array_values($this->input('nav'));
    }

    /**
     * A web URL, a `mailto:` or `tel:` link, or a control panel path. A path
     * may leave out the CP trigger, so it works whatever the trigger is.
     *
     * @return list<string|AnyOf>
     */
    public static function linkRules(): array
    {
        return ['nullable', 'string', 'max:2048', Rule::anyOf([
            'url:http,https',
            'regex:/^mailto:\S+$/i',
            'regex:/^tel:\S+$/i',
            // No scheme, and not protocol-relative. Browsers read a backslash
            // as a slash, so `/\host` counts as protocol-relative too.
            'regex:#^(?![/\\\\]{2})(?![a-z][a-z0-9+.-]*:)\S+$#i',
        ])];
    }

    /**
     * A user condition's config. One without a class has no rules, so it's
     * the same as none.
     *
     * @return list<string|Closure>
     */
    public static function userConditionRules(): array
    {
        return ['nullable', 'array', function (string $attribute, mixed $config, Closure $fail): void {
            if (! is_array($config) || ! isset($config['class'])) {
                return;
            }

            try {
                $isUserCondition = app(Conditions::class)->createCondition($config) instanceof UserCondition;
            } catch (InvalidArgumentException) {
                $isUserCondition = false;
            }

            if (! $isUserCondition) {
                $fail(t('The condition isn’t valid.'));
            }
        }];
    }
}
