<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

class ActivityMentionSuggestionsRequest extends ActivityRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'query' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
