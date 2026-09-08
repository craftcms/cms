<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

class ActivityTimelineRequest extends ActivityRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'all' => ['sometimes', 'boolean'],
        ];
    }
}
