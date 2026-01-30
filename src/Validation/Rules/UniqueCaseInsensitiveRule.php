<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\DatabaseRule;
use Illuminate\Validation\Rules\Unique;
use Tpetry\QueryExpressions\Function\String\Lower;

final class UniqueCaseInsensitiveRule extends Unique implements ValidationRule
{
    use DatabaseRule;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table($this->table)
            ->where(new Lower($this->column), mb_strtolower((string) $value));

        foreach ($this->wheres as $where) {
            $query->where($where['column'], $where['value']);
        }

        foreach ($this->queryCallbacks() as $callback) {
            $query->where($callback);
        }

        if (! is_null($this->ignore)) {
            $query->whereNot($this->idColumn, $this->ignore);
        }

        if ($query->exists()) {
            $fail(__('validation.unique'));
        }
    }
}
