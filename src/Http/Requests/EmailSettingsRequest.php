<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailSettingsRequest extends FormRequest
{
    /** @return array<string, list<string|object>> */
    public function rules(): array
    {
        $validMailers = array_keys(config('mail.mailers', []));

        $rules = [
            'fromEmail' => [new EnvValueRule(['required', 'string', 'email'])],
            'fromName' => [new EnvValueRule(['required', 'string'])],
            'replyToEmail' => [new EnvValueRule(['nullable', 'string', 'email'])],
            'mailer' => [new EnvValueRule(['nullable', 'string', Rule::in($validMailers)])],
            'template' => ['nullable', 'string'],
        ];

        foreach (Sites::getAllSites() as $site) {
            $prefix = "siteOverrides.{$site->uid}";

            $rules["{$prefix}.fromEmail"] = [new EnvValueRule(['nullable', 'string', 'email'])];
            $rules["{$prefix}.fromName"] = ['nullable', 'string'];
            $rules["{$prefix}.replyToEmail"] = [new EnvValueRule(['nullable', 'string', 'email'])];
            $rules["{$prefix}.template"] = ['nullable', 'string'];
        }

        return $rules;
    }
}
