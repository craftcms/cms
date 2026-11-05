<?php

namespace craft\mcp\events;

use yii\base\Event;

class CollectingAdminInstructions extends Event
{
    public array $instructions = [];
}
