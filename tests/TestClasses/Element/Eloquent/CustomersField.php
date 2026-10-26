<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Element\Eloquent;

use CraftCms\Cms\Element\Eloquent\EloquentElementsField;

class CustomersField extends EloquentElementsField
{
    public static function elementType(): string
    {
        return CustomerElement::class;
    }
}
