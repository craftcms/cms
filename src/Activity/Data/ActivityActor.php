<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use CraftCms\Cms\Activity\Enums\ActivityActorType;
use CraftCms\Cms\User\Elements\User;
use InvalidArgumentException;

readonly class ActivityActor
{
    public function __construct(
        public ActivityActorType $type,
        public ?int $id,
        public string $label,
    ) {
        if ($this->type === ActivityActorType::User && $this->id === null) {
            throw new InvalidArgumentException('User activity actors require an ID.');
        }

        if ($this->type !== ActivityActorType::User && $this->id !== null) {
            throw new InvalidArgumentException('Only user activity actors may have an ID.');
        }

        if ($this->label === '') {
            throw new InvalidArgumentException('Activity actor labels cannot be empty.');
        }
    }

    public static function user(User $user): self
    {
        if ($user->id === null) {
            throw new InvalidArgumentException('Activity actors must be saved users.');
        }

        return new self(ActivityActorType::User, $user->id, $user->name);
    }

    public static function system(): self
    {
        return new self(ActivityActorType::System, null, 'Craft CMS');
    }

    public static function anonymous(): self
    {
        return new self(ActivityActorType::Anonymous, null, 'Anonymous');
    }
}
