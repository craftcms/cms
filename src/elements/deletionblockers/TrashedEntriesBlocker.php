<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

use Craft;
use craft\elements\Entry;
use craft\helpers\Cp;

/**
 * TrashedEntriesBlocker reports the entries that a deletion will move to the trash.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class TrashedEntriesBlocker extends BaseResolvableDeletionBlocker
{
    /**
     * @inheritdoc
     */
    public bool $expandDetails = false;

    private int $count;

    /**
     * Constructor
     *
     * @param array $criteria Entry query criteria that select the entries, such as `['sectionId' => 1]`
     */
    public function __construct(
        protected readonly array $criteria,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function init(): void
    {
        // Match what the deletion trashes: canonical entries and unpublished drafts
        $this->count = Craft::configure(Entry::find(), $this->criteria)
            ->drafts(null)
            ->draftOf(false)
            ->andWhere(['elements.canonicalId' => null])
            ->site('*')
            ->unique()
            ->status(null)
            ->count();

        parent::init();
    }

    public function getKey(): string
    {
        return 'entries';
    }

    public function getSummary(): string
    {
        return Craft::t('app', '{num, number} {num, plural, =1{entry} other{entries}} will be moved to the trash.', [
            'num' => $this->count,
        ]);
    }

    public function getResolutionLabel(): string
    {
        return Craft::t('app', 'Move {num, plural, =1{entry} other{entries}} to the trash', [
            'num' => $this->count,
        ]);
    }

    public function getDetails(): ?string
    {
        return Cp::elementIndexHtml(Entry::class, [
            'context' => 'pane',
            'sources' => false,
            'jsSettings' => [
                'criteria' => [
                    ...$this->criteria,
                    'drafts' => null,
                    'draftOf' => false,
                    'savedDraftsOnly' => false,
                    'status' => null,
                ],
            ],
        ]);
    }

    protected function getResolvedMessage(): string
    {
        return Craft::t('app', 'The {num, plural, =1{entry} other{entries}} will be moved to the trash.', [
            'num' => $this->count,
        ]);
    }

    protected function isDestructive(): bool
    {
        return true;
    }

    protected function defineItems(): array
    {
        if ($this->count === 0) {
            return [];
        }

        return [
            [
                'label' => Entry::pluralDisplayName(),
                'count' => $this->count,
                'description' => $this->getSummary(),
            ],
        ];
    }
}
