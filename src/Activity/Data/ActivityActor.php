<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\Data;

use CraftCms\Cms\User\Contracts\CraftUser;
use InvalidArgumentException;

readonly class ActivityActor
{
    public const string TYPE_USER = 'user';

    public const string TYPE_SYSTEM = 'system';

    public const string TYPE_ANONYMOUS = 'anonymous';

    public function __construct(
        public string $type,
        public string $label,
        public ?int $id = null,
    ) {
        if ($this->type === self::TYPE_USER && $this->id === null) {
            throw new InvalidArgumentException('User activity actors require an ID.');
        }

        if ($this->label === '') {
            throw new InvalidArgumentException('Activity actor labels cannot be empty.');
        }
    }

    public static function user(CraftUser $user): self
    {
        $user = $user->asElement();

        if ($user->id === null) {
            throw new InvalidArgumentException('Activity actors must be saved users.');
        }

        return new self(self::TYPE_USER, $user->name ?: $user->username ?: $user->email ?: "User #{$user->id}", $user->id);
    }

    public static function system(): self
    {
        return new self(self::TYPE_SYSTEM, 'Craft CMS');
    }

    public static function anonymous(): self
    {
        return new self(self::TYPE_ANONYMOUS, 'Anonymous');
    }
}
