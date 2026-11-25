<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\elements\Entry;
use craft\helpers\Html;
use craft\models\Section;
use craft\models\Site;

/**
 * SiteSectionsBlocker lists the sections that are only enabled for a site, whose content the deletion transfers to
 * another site or deletes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class SiteSectionsBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * @var Section[]
     */
    private array $sections = [];

    /**
     * Constructor
     */
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
        return Craft::t('app', '{num, number} {num, plural, =1{section is} other{sections are}} only enabled for this site.', [
            'num' => count($this->getItems()),
        ]);
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Transfer or delete later');
    }

    public function getActions(): array
    {
        $this->getItems();
        $actions = [];

        // Each section can be deleted, after addressing its own blockers
        foreach ($this->sections as $section) {
            $actions[] = [
                'icon' => 'trash',
                'label' => Craft::t('app', 'Delete {name}', ['name' => $section->getUiLabel()]),
                'destructive' => true,
                'callback' => Html::jsWithVars(fn($id, $message) => <<<JS
(async () => {
  const result = await Craft.DeletionBlockersModal.confirm('section', $id);
  if (!result || (!result.confirmed && !confirm($message))) {
    reject();
    return;
  }
  await Craft.sendActionRequest('POST', 'sections/delete-section', {data: {id: $id}});
  await blocker.manager.refresh();
  reject();
})().catch((e) => {
  Craft.cp.displayError(e?.response?.data?.message);
  reject();
});
JS, [
                    $section->id,
                    Craft::t('app', 'Are you sure you want to delete “{name}”?', ['name' => $section->getUiLabel()]),
                ]),
            ];
        }

        return [...$actions, ...parent::getActions()];
    }

    protected function getResolvedMessage(): string
    {
        return Craft::t('app', 'You’ll choose whether to transfer or delete their content next.');
    }

    protected function defineItems(): array
    {
        $items = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            /** @var Section $section */
            $siteIds = array_keys($section->getSiteSettings());
            if ($siteIds !== [$this->site->id]) {
                continue;
            }

            $count = (int)Entry::find()
                ->sectionId($section->id)
                ->siteId($this->site->id)
                ->andWhere(['elements.canonicalId' => null])
                ->drafts(null)
                ->draftOf(false)
                ->status(null)
                ->count();

            $this->sections[] = $section;
            $items[] = [
                'label' => $section->getUiLabel(),
                'count' => $count,
                'description' => Craft::t('app', '{count, number} {count, plural, =1{entry} other{entries}}.', [
                    'count' => $count,
                ]),
            ];
        }

        return $items;
    }
}
