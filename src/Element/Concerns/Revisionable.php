<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\ElementCapability;
use CraftCms\Cms\Element\Enums\ElementPropertyType;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Gql\Attributes\Gql;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * Revisionable provides revision functionality for elements.
 *
 * This trait contains methods for managing element revisions, including tracking revision metadata
 * (creator, number, notes), determining if an element is a revision, and fetching the current revision.
 *
 * @internal
 */
trait Revisionable
{
    /**
     * @var int|null The ID of the revision’s row in the `revisions` table
     */
    #[ElementProperty(ElementPropertyType::Int, 'The revision ID (from the `revisions` table).', readable: true, writable: false, requires: ElementCapability::Revisions)]
    public ?int $revisionId = null;

    /**
     * @var int|null The creator’s ID
     */
    public ?int $revisionCreatorId = null;

    /**
     * @var ?int The revision number
     */
    public ?int $revisionNum = null;

    /**
     * @var string|null The revision notes
     */
    #[ElementProperty(ElementPropertyType::String, 'The revision notes (from the `revisions` table).', nullable: true, readable: true, requires: ElementCapability::Revisions)]
    #[Gql(write: false)]
    public ?string $revisionNotes = null;

    /**
     * @var User|null|false The creator
     */
    private User|false|null $revisionCreator = null;

    /**
     * @see getCurrentRevision()
     */
    protected ElementInterface|false|null $currentRevision = null;

    /**
     * Returns the revision’s creator.
     */
    public function getRevisionCreator(): ?User
    {
        if (! isset($this->revisionCreator)) {
            if (! $this->revisionCreatorId) {
                return null;
            }

            /** @var User|null $creator */
            $creator = User::find()
                ->id($this->revisionCreatorId)
                ->status(null)
                ->first();

            $this->revisionCreator = $creator ?? false;
        }

        return $this->revisionCreator ?: null;
    }

    /**
     * Sets the revision's creator.
     */
    public function setRevisionCreator(?User $creator = null): void
    {
        $this->revisionCreator = $creator ?? false;
    }

    public function setRevisionCreatorId(?int $creatorId): void
    {
        $this->revisionCreatorId = $creatorId;
    }

    public function setRevisionNotes(?string $notes): void
    {
        $this->revisionNotes = $notes;
    }

    public function getRevisionLabel(): string
    {
        return t('Revision {num}', [
            'num' => $this->revisionNum,
        ]);
    }

    /**
     * Makes sure the element has at least one revision before it’s saved, so its current state isn’t lost.
     */
    public function ensureInitialRevision(): void
    {
        if (! $this->shouldSaveRevision()) {
            return;
        }

        $hasRevisions = static::find()
            ->revisionOf($this)
            ->site('*')
            ->status(null)
            ->exists();

        if ($hasRevisions) {
            return;
        }

        /** @var static|null $current */
        $current = static::find()
            ->id($this->id)
            ->site('*')
            ->status(null)
            ->one();

        // May be null if the element is currently stored as an unpublished draft
        if ($current) {
            app(Revisions::class)->createRevision(
                $current,
                $current->initialRevisionCreatorId(),
                sprintf('Revision from %s', I18N::getFormatter()->asDatetime($current->dateUpdated, withTimeZone: true)),
            );
        }
    }

    /**
     * Saves a new revision of the element, if it gets revisions.
     */
    public function handleRevisionSave(): void
    {
        if ($this->shouldSaveRevision()) {
            app(Revisions::class)->createRevision($this, $this->revisionCreatorId, $this->revisionNotes);
        }
    }

    /**
     * Returns the user ID that an element’s first revision should be attributed to, when it’s created for an
     * element that was saved before it had any revisions. It’s called on the element as currently stored, not the one
     * being saved.
     *
     * Returning `null` attributes it to the current user.
     */
    protected function initialRevisionCreatorId(): ?int
    {
        return null;
    }

    /**
     * Returns whether a revision should be saved when the element is saved.
     */
    private function shouldSaveRevision(): bool
    {
        return
            $this->id &&
            ! $this->propagating &&
            ! $this->resaving &&
            $this->getIsCanonical() &&
            $this->hasRevisions() &&
            // Nested elements owned by a draft or revision shouldn't get their own revisions
            // (see https://github.com/craftcms/cms/issues/20151)
            ! ElementHelper::isDraftOrRevision($this);
    }

    public function handleRevisionDelete(): void
    {
        if (! $this->getIsRevision()) {
            return;
        }

        DB::table(Table::REVISIONS)->delete($this->revisionId);
    }

    #[ElementProperty(ElementPropertyType::Boolean, 'Returns whether this is a revision.', readable: true, writable: false, requires: ElementCapability::Revisions)]
    public function getIsRevision(): bool
    {
        return ! empty($this->revisionId);
    }

    public function getCpRevisionsUrl(): ?string
    {
        $cpEditUrl = $this->cpRevisionsUrl();

        if (! $cpEditUrl) {
            return null;
        }

        $params = [];

        if (Sites::isMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return Url::cpUrl($cpEditUrl, $params);
    }

    /**
     * Returns the element’s revisions index URL in the control panel.
     */
    protected function cpRevisionsUrl(): ?string
    {
        return null;
    }

    public static function supportsRevisions(): bool
    {
        return false;
    }

    public function hasRevisions(): bool
    {
        return false;
    }

    abstract public function getCanonical(bool $anySite = false): ElementInterface;

    public function getCurrentRevision(): ?ElementInterface
    {
        if (! $this->id) {
            return null;
        }

        if (! isset($this->currentRevision)) {
            $canonical = $this->getCanonical(true);

            $this->currentRevision = static::find()
                ->siteId($canonical->siteId)
                ->revisionOf($canonical->id)
                ->dateCreated($canonical->dateUpdated)
                ->status(null)
                ->orderByDesc('num')
                ->one() ?: false;
        }

        return $this->currentRevision ?: null;
    }
}
