<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Requests;

use CraftCms\Cms\Support\Arr;

class ElementIndexScreenRequest extends TableRequest
{
    public function source(): ?string
    {
        $source = $this->input('source');

        return is_string($source) && $source !== '' ? $source : null;
    }

    public function siteId(): ?int
    {
        return $this->filled('site') ? $this->integer('site') : null;
    }

    public function status(): ?string
    {
        $status = $this->input('status');

        return is_string($status) && $status !== '' ? $status : null;
    }

    public function sortAttribute(): ?string
    {
        $field = Arr::get($this->array('sort'), '0.field');

        return is_string($field) && $field !== '' ? $field : null;
    }

    public function sortDirection(): string
    {
        return Arr::get($this->array('sort'), '0.direction') === 'desc' ? 'desc' : 'asc';
    }
}
