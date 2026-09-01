<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Arr;
use Illuminate\Foundation\Http\FormRequest;

class TableRequest extends FormRequest
{
    public function page(): int
    {
        return $this->integer(Cms::config()->getPageTriggerParam(), 1);
    }

    public function limit(): int
    {
        return $this->integer('per_page', 100);
    }

    public function search(): ?string
    {
        return $this->input('search');
    }

    /** @return list<array{field:string, direction:string}> */
    public function sort(): array
    {
        return ! empty($this->array('sort')) ? $this->array('sort') : [
            ['field' => 'name', 'direction' => 'asc'],
        ];
    }

    public function orderBy(): string
    {
        return match (Arr::get($this->sort(), '0.field')) {
            '__slot:handle', 'handle' => 'handle',
            'type' => 'type',
            default => 'name',
        };
    }

    public function sortDir(): int
    {
        return match (Arr::get($this->sort(), '0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };
    }
}
