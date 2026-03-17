<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        $validMailers = array_keys(config('mail.mailers', []));

        $rules = [
            'fromEmail' => ['required', 'string'],
            'fromName' => ['required', 'string'],
            'replyToEmail' => ['nullable', 'string'],
            'mailer' => ['nullable', 'string', Rule::in($validMailers)],
            'template' => ['nullable', 'string'],
        ];

        foreach (Sites::getAllSites() as $site) {
            $prefix = "siteOverrides.{$site->uid}";

            $rules["{$prefix}.fromEmail"] = ['nullable', 'string'];
            $rules["{$prefix}.fromName"] = ['nullable', 'string'];
            $rules["{$prefix}.replyToEmail"] = ['nullable', 'string'];
            $rules["{$prefix}.template"] = ['nullable', 'string'];
        }

        return $rules;
    }
}
