<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

/**
 * Lists the sections that are only enabled for a site, whose content the deletion transfers to another site or
 * deletes.
 *
 * @since 6.0.0
 */
class SiteSectionsBlocker extends BaseResolvableDeletionBlocker
{
    /** @var list<Section> */
    private array $sections = [];

    /** @param array<string, mixed> $config */
    public function __construct(
        protected readonly Site $site,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getKey(): string
    {
        return 'sections';
    }

    public function getSummary(): string
    {
        return t('{num, number} {num, plural, =1{section is} other{sections are}} only enabled for this site.', [
            'num' => count($this->getItems()),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return t('Transfer or delete later');
    }

    public function getActions(): array
    {
        $this->getItems();

        // Each section gets a Delete button, which shows the section’s own blockers first
        $actions = array_map(fn (Section $section) => [
            'icon' => 'trash',
            'label' => t('Delete {name}', ['name' => $section->getUiLabel()]),
            'destructive' => true,
            'callback' => Html::jsWithVars(fn ($id, $url, $message) => <<<JS
blocker.manager.deleteSubject('section', $id, $url, $message).then(reject, reject);
JS, [
                $section->id,
                route('craft.cp.settings.sections.destroy', ['section' => $section->id]),
                t('Are you sure you want to delete “{name}” and all its entries?', ['name' => $section->getUiLabel()]),
            ]),
        ], $this->sections);

        return [...$actions, ...parent::getActions()];
    }

    protected function getResolvedMessage(): string
    {
        return t('You’ll choose whether to transfer or delete their content next.');
    }

    protected function defineItems(): array
    {
        $items = [];

        foreach (Sections::getAllSections() as $section) {
            if (array_keys($section->getSiteSettings()) !== [$this->site->id]) {
                continue;
            }

            $count = Entry::find()
                ->sectionId($section->id)
                ->siteId($this->site->id)
                ->drafts(null)
                ->draftOf(false)
                ->whereNull('elements.canonicalId')
                ->status(null)
                ->count();

            $this->sections[] = $section;
            $items[] = [
                'label' => $section->getUiLabel(),
                'count' => $count,
                'description' => t('{count, number} {count, plural, =1{entry} other{entries}}.', [
                    'count' => $count,
                ]),
            ];
        }

        return $items;
    }
}
