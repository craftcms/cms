<?php

namespace craft\events;

use craft\base\Event;

class RegisterJsImportEvent extends Event
{
    /**
     * @var array import entries to be added to the import map.
     */
    public array $imports = [];
}
