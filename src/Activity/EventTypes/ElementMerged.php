<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use UnexpectedValueException;

use function CraftCms\Cms\t;

class ElementMerged extends ActivityEventType
{
    protected const string LABEL = 'Merged';

    protected const string ICON = 'code-merge';

    public function __construct(
        ActivitySubject $subject,
        private readonly string $role,
        private readonly ActivitySubject $other,
    ) {
        parent::__construct(subject: $subject);
    }

    public function data(): array
    {
        return [
            'role' => $this->role,
            'other' => [
                'type' => $this->other->type,
                'id' => $this->other->id,
                'label' => $this->other->label,
            ],
        ];
    }

    public static function format(ActivityEvent $event): string
    {
        $other = $event->data['other']['label'];

        return match ($event->data['role']) {
            'merged' => t('Merged into {other}.', compact('other')),
            'prevailing' => t('Merged {other} into this element.', compact('other')),
            default => throw new UnexpectedValueException('Unknown activity merge role.'),
        };
    }
}
