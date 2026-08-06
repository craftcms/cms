<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;

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
