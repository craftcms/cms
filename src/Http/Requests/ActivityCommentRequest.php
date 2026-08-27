<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

class ActivityCommentRequest extends ActivityRequest
{
    public const int MaxLength = 10_000;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $commentId = ['required', 'integer', 'min:1'];
        $markdown = ['required', 'string', 'max:'.self::MaxLength];

        return [
            ...parent::rules(),
            ...match ($this->method()) {
                'POST' => ['markdown' => $markdown],
                'PATCH' => ['commentId' => $commentId, 'markdown' => $markdown],
                'DELETE' => ['commentId' => $commentId],
                default => abort(405),
            },
        ];
    }
}
