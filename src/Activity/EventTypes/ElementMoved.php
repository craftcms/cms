<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use UnexpectedValueException;

use function CraftCms\Cms\t;

class ElementMoved extends ActivityEventType
{
    protected const string LABEL = 'Moved';

    protected const string ICON = 'arrows-up-down-left-right';

    /**
     * @param  array{structure: string, parent: array{type: string, id: string, label: string}|null, previousSibling: array{type: string, id: string, label: string}|null}  $origin
     * @param  array{structure: string, parent: array{type: string, id: string, label: string}|null, previousSibling: array{type: string, id: string, label: string}|null}  $destination
     */
    public function __construct(
        ElementInterface $subject,
        ?Site $site,
        private readonly array $origin,
        private readonly array $destination,
    ) {
        parent::__construct(subject: $subject, site: $site);
    }

    public function data(): array
    {
        return [
            'origin' => $this->origin,
            'destination' => $this->destination,
        ];
    }

    public static function rules(): array
    {
        return [
            'origin' => ['required', 'array:structure,parent,previousSibling'],
            'origin.structure' => ['required', 'uuid'],
            'origin.parent' => ['nullable', 'array:type,id,label'],
            'origin.previousSibling' => ['nullable', 'array:type,id,label'],
            'origin.parent.*' => ['string'],
            'origin.previousSibling.*' => ['string'],
            'destination' => ['required', 'array:structure,parent,previousSibling'],
            'destination.structure' => ['required', 'uuid'],
            'destination.parent' => ['nullable', 'array:type,id,label'],
            'destination.previousSibling' => ['nullable', 'array:type,id,label'],
            'destination.parent.*' => ['string'],
            'destination.previousSibling.*' => ['string'],
        ];
    }

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Moved from {origin} to {destination}.',
            [
                'origin' => self::positionDescription($event->data['origin']),
                'destination' => self::positionDescription($event->data['destination']),
            ],
        );
    }

    private static function positionDescription(mixed $position): string
    {
        if (! is_array($position)) {
            throw new UnexpectedValueException('Activity movement positions must be arrays.');
        }

        $parent = $position['parent']['label'] ?? null;
        $previousSibling = $position['previousSibling']['label'] ?? null;

        return match (true) {
            $parent !== null && $previousSibling !== null => t(
                'the position after {previousSibling} in {parent}',
                compact('parent', 'previousSibling'),
            ),
            $parent !== null => t(
                'the first position in {parent}',
                compact('parent'),
            ),
            $previousSibling !== null => t(
                'the position after {previousSibling} at the top level',
                compact('previousSibling'),
            ),
            default => t('the first position at the top level'),
        };
    }
}
