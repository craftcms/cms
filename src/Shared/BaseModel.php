<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared;

use Carbon\CarbonInterface;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Traits\Macroable;

/**
 * @since 6.0.0
 */
class BaseModel extends Model
{
    use Macroable {
        __call as macroCall;
        __callStatic as macroCallStatic;
    }

    #[\Override]
    protected $guarded = [];

    public const ?string CREATED_AT = 'dateCreated';

    public const ?string UPDATED_AT = 'dateUpdated';

    public const ?string DELETED_AT = 'dateDeleted';

    /**
     * @param  QueryBuilder  $query
     * @return BaseBuilder<*>
     */
    #[\Override]
    public function newEloquentBuilder($query): BaseBuilder
    {
        return new BaseBuilder($query);
    }

    /**
     * Stores dates in UTC, including the automatic timestamps and soft-delete
     * dates, as Laravel would otherwise format them in PHP’s default time zone,
     * which Craft sets to the system time zone.
     */
    #[\Override]
    public function fromDateTime($value): mixed
    {
        return empty($value) ? $value : Query::prepareDateForDb($value);
    }

    /**
     * Reads stored dates as UTC, returning them in the system time zone.
     */
    #[\Override]
    protected function asDateTime($value): CarbonInterface
    {
        if (is_string($value) && ! is_numeric($value)) {
            $date = DateTimeHelper::toDateTime($value);

            if ($date !== false) {
                return Date::instance($date);
            }
        }

        return parent::asDateTime($value);
    }

    /**
     * @param  list<mixed>  $parameters
     */
    #[\Override]
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @param  list<mixed>  $parameters
     */
    #[\Override]
    public static function __callStatic($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return static::macroCallStatic($method, $parameters);
        }

        return parent::__callStatic($method, $parameters);
    }
}
