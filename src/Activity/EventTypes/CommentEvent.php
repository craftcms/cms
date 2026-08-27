<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\User\Elements\User;

abstract class CommentEvent extends ActivityEventType
{
    /** @param list<array{id: int, username: string}> $mentions */
    public function __construct(
        ElementInterface|ActivitySubject $subject,
        User|ActivityActor $actor,
        Site $site,
        private readonly string $markdown,
        private readonly int $authorId,
        private readonly string $authorLabel,
        private readonly array $mentions,
    ) {
        parent::__construct($subject, $actor, $site);
    }

    public function data(): array
    {
        return [
            'markdown' => $this->markdown,
            'author' => ['id' => $this->authorId, 'label' => $this->authorLabel],
            'mentions' => $this->mentions,
        ];
    }
}
