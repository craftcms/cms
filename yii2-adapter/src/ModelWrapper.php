<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter;

use craft\base\Model;
use CraftCms\Cms\Support\Utils;
use Illuminate\Support\Traits\ForwardsCalls;

class ModelWrapper extends Model
{
    use ForwardsCalls;

    public function __construct(
        private readonly object $object,
    ) {
        $config = Utils::getPublicProperties($this->object);
        parent::__construct($config);
    }

    public function __get($name)
    {
        return $this->object->$name;
    }

    public function __set($name, $value): void
    {
        $this->object->$name = $value;
    }

    public function __isset($name): bool
    {
        return isset($this->object->$name);
    }

    public function __call($name, $params)
    {
        return $this->forwardDecoratedCallTo($this->object, $name, $params);
    }
}
