<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Cp\NavCustomizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\AnyOf;

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
    /** @return array<string, list<string|AnyOf>> */
    public function rules(): array
    {
        return [
            'nav' => ['present', 'array', 'max:500'],
            'nav.*' => ['array'],
            // Custom links sit at the top level or in a section.
            'nav.*.url' => self::linkRules(),
            'nav.*.children.*.url' => self::linkRules(),
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
            // No scheme, and not protocol-relative.
            'regex:#^(?!//)(?![a-z][a-z0-9+.-]*:)\S+$#i',
        ])];
    }
}
