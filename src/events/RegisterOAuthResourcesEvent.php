<?php

namespace craft\events;

use craft\base\Event;
use craft\oauth\Resource;

/** @since 5.12.0 */
class RegisterOAuthResourcesEvent extends Event
{
    /** @var array<string,Resource|array> Resource definitions keyed by resource ID. */
    public array $resources = [];
}
