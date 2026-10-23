<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    #[\Override]
    protected $fillable = ['name', 'email', 'company'];
}
