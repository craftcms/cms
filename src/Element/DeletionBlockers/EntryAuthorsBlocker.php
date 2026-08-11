<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\DeletionBlockers;

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

class EntryAuthorsBlocker extends BaseDeletionBlocker
{
    /** @var Collection<int, int> */
    private readonly Collection $entryIds;

    /**
     * @param  ElementCollection<int, covariant ElementInterface>  $elements
     * @param  array<string, mixed>  $config
     */
    public function __construct(ElementCollection $elements, bool $hardDelete, array $config = [])
    {
        parent::__construct($elements, $hardDelete, $config);

        $this->entryIds = Entry::find()
            ->authorId($this->elements->ids()->all())
            ->site('*')
            ->unique()
            ->status(null)
            ->collectIds();
    }

    public function isActive(): bool
    {
        return $this->entryIds->isNotEmpty();
    }

    public function getSummary(): string
    {
        return t('{numEntries, number} {numEntries, plural, =1{entry has} other{entries have}} the {numUsers, plural, =1{user} other{users}} assigned as an author.', [
            'numEntries' => $this->entryIds->count(),
            'numUsers' => $this->elements->count(),
        ]);
    }

    public function getDetails(): ?string
    {
        return app(ElementIndexHtml::class)->html(Entry::class, [
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
                'label' => t('Reassign {numEntries, plural, =1{entry} other{entries}}', [
                    'numEntries' => $numEntries,
                ]),
                'callback' => Html::jsWithVars(fn ($userIds) => <<<JS
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
})
JS, [
                    $this->elements->ids()->all(),
                ]),
            ],
            [
                'icon' => 'user-minus',
                'label' => t('Remove {numUsers, plural, =1{author} other {authors}} from {numEntries, plural, =1{entry} other{entries}}', [
                    'numUsers' => $numUsers,
                    'numEntries' => $numEntries,
                ]),
                'callback' => Html::jsWithVars(fn ($message) => "resolve($message);", [
                    t('The {numEntries, plural, =1{entry} other {entries}} will be updated once the {numUsers, plural, =1{user is} other{users are}} deleted.', [
                        'numEntries' => $numEntries,
                        'numUsers' => $numUsers,
                    ]),
                ]),
            ],
            [
                'icon' => 'trash',
                'label' => t('Delete {type}', [
                    'type' => $numEntries === 1 ? Entry::lowerDisplayName() : Entry::pluralLowerDisplayName(),
                ]),
                'destructive' => true,
                'callback' => Html::jsWithVars(fn ($elementType, $entryIds, $message) => <<<JS
new Craft.ElementDeletionManager($elementType, $entryIds, {
  onSuccess: () => {
    resolve($message)
  },
  onCancel: () => {
    reject();
  },
})
JS, [
                    Entry::class,
                    $this->entryIds->all(),
                    t('{type} deleted.', [
                        'type' => $this->entryIds->count() === 1 ? Entry::displayName() : Entry::pluralDisplayName(),
                    ]),
                ]),
            ],
        ];
    }
}
