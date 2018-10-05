<?php
namespace craftunit\support\mockclasses\components;

use craft\base\Serializable as SerializableInterface;

class Serializable implements SerializableInterface
{
    public function serialize()
    {
        return 'Serialized data';
    }
}