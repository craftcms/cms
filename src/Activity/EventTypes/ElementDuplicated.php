<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity\EventTypes;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;

use function CraftCms\Cms\t;

class ElementDuplicated extends ActivityEventType
{
    protected const string LABEL = 'Duplicated';

    protected const string ICON = 'copy';

    private readonly ActivitySubject $source;

    public function __construct(
        ElementInterface $subject,
        ?Site $site,
        ElementInterface $source,
    ) {
        parent::__construct(subject: $subject, site: $site);

        $this->source = ActivitySubject::fromElement($source);
    }

    public function data(): array
    {
        return ['source' => [
            'type' => $this->source->type,
            'id' => $this->source->id,
            'label' => $this->source->label,
        ]];
    }

    public static function rules(): array
    {
        return [
            'source' => ['required', 'array:type,id,label'],
            'source.type' => ['required', 'string'],
            'source.id' => ['required', 'string'],
            'source.label' => ['required', 'string'],
        ];
    }

    public static function format(ActivityEvent $event): string
    {
        return t(
            'Duplicated from {source}.',
            ['source' => $event->data['source']['label']],
        );
    }
}
