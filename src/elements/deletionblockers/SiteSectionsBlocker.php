<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\elements\Entry;
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
        return Craft::t('app', 'Continue');
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
