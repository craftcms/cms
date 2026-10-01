<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

/**
 * @since 6.0.0
 */
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
