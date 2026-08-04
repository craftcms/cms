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
use craft\helpers\Html;
use Illuminate\Support\Collection;

/**
 * Class EntryAuthorsBlocker
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.10.0
 */
class EntryAuthorsBlocker extends BaseDeletionBlocker
{
    /**
     * @var Collection<int>
     */
    private Collection $entryIds;

    public function init()
    {
        $this->entryIds = Entry::find()
            ->authorId($this->elements->ids()->all())
            ->site('*')
            ->unique()
            ->status(null)
            ->collectIds();

        parent::init();
    }

    public function isActive(): bool
    {
        return $this->entryIds->isNotEmpty();
    }

    public function getSummary(): string
    {
        return Craft::t('app', '{numEntries, number} {numEntries, plural, =1{entry has} other{entries have}} the {numUsers, plural, =1{user} other{users}} assigned as an author.', [
            'numEntries' => $this->entryIds->count(),
            'numUsers' => $this->elements->count(),
        ]);
    }

    public function getDetails(): ?string
    {
        return Cp::elementIndexHtml(Entry::class, [
            'context' => 'pane',
            'defaultTableColumns' => [
                ['authors'],
                ['section'],
            ],
            'defaultSort' => ['section', 'asc'],
            'sources' => false,
            'jsSettings' => [
                'criteria' => [
                    'authorId' => $this->elements->ids()->all(),
                    'status' => null,
                ],
            ],
        ]);
    }

    public function getActions(): array
    {
        $numUsers = $this->elements->count();
        $numEntries = $this->entryIds->count();

        return [
            [
                'icon' => 'user-plus',
                'label' => Craft::t('app', 'Reassign {numEntries, plural, =1{entry} other{entries}}', [
                    'numEntries' => $numEntries,
                ]),
                'callback' => Html::jsWithVars(fn($userIds) => <<<JS
new Craft.CpModal('entries/reassign-modal', {
  params: {
    oldUserIds: $userIds,
  },
  onSubmit: (ev) => {
    resolve(ev.response.data.message);
  },
  onCancel: () => {
    reject();
  },
});
JS, [
                    $this->elements->ids()->all(),
                ]),
            ],
            [
                'icon' => 'user-minus',
                'label' => Craft::t('app', 'Remove {numUsers, plural, =1{author} other {authors}} from {numEntries, plural, =1{entry} other{entries}}', [
                    'numUsers' => $numUsers,
                    'numEntries' => $numEntries,
                ]),
                'callback' => Html::jsWithVars(fn($message) => "resolve($message);", [
                    Craft::t('app', 'The {numEntries, plural, =1{entry} other {entries}} will be updated once the {numUsers, plural, =1{user is} other{users are}} deleted.', [
                        'numEntries' => $numEntries,
                        'numUsers' => $numUsers,
                    ]),
                ]),
            ],
            [
                'icon' => 'trash',
                'label' => Craft::t('app', 'Delete {type}', [
                    'type' => $numEntries === 1 ? Entry::lowerDisplayName() : Entry::pluralLowerDisplayName(),
                ]),
                'destructive' => true,
                'callback' => Html::jsWithVars(fn($elementType, $entryIds, $message) => <<<JS
new Craft.ElementDeletionManager($elementType, $entryIds, {
  onSuccess: () => {
    resolve($message);
  },
  onCancel: () => {
    reject();
  },
});
JS, [
                    Entry::class,
                    $this->entryIds->all(),
                    Craft::t('app', '{type} deleted.', [
                        'type' => $this->entryIds->count() === 1 ? Entry::displayName() : Entry::pluralDisplayName(),
                    ]),
                ]),
            ],
        ];
    }
}
