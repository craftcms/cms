<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\User\Elements\User;
use DateTime;

class ElementActivity extends Component
{
    public User $user;

    public ElementInterface $element;

    public ElementActivityType $type;

    public DateTime $timestamp;
}
