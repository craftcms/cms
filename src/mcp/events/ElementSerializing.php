<?php

namespace craft\mcp\events;

use craft\base\ElementInterface;
use yii\base\Event;

class ElementSerializing extends Event
{
    public ElementInterface $element;
    public array $serializedData = [];
    public bool $public = false;
}
