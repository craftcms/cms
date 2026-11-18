<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Activity;

use CraftCms\Cms\Activity\ActivityEventType;
use CraftCms\Cms\Activity\EventTypes\DraftApplied;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\t;

/**
 * A draft applied by publishing the content release it belongs to.
 *
 * Releases now record {@see DraftApplied} as part of a {@see ReleaseOperation}. This type remains so that
 * events recorded before that change still display.
 *
 * @since 6.0.0
 */
class ReleaseDraftPublished extends ActivityEventType
{
    protected const string LABEL = 'Published with release';

    protected const string ICON = 'check';

    /**
     * @param  array{id: int, name: string}  $release
     */
    public function __construct(
        ElementInterface $subject,
        ?Site $site,
        private readonly array $release,
    ) {
        parent::__construct(subject: $subject, site: $site);
    }

    public function data(): array
    {
        return ['release' => $this->release];
    }

    public static function format(ActivityEvent $event): Htmlable
    {
        $release = $event->data['release'];

        return new HtmlString(t('Published with the {release} release', [
            'release' => Html::a(e($release['name']), route('craft.cp.releases.show', $release['id'])),
        ]));
    }
}
