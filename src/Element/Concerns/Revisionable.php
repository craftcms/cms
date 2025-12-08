<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/** @phpstan-ignore trait.unused */
trait Revisionable
{
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
                ->one();

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

    public function handleRevisionDelete(): void
    {
        if (! $this->getIsRevision()) {
            return;
        }

        DB::table(Table::REVISIONS)->delete($this->revisionId);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsRevision(): bool
    {
        return ! empty($this->revisionId);
    }

    /**
     * {@inheritdoc}
     */
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

        return UrlHelper::cpUrl($cpEditUrl, $params);
    }

    /**
     * Returns the element’s revisions index URL in the control panel.
     */
    protected function cpRevisionsUrl(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRevisions(): bool
    {
        return false;
    }

    abstract public function getCanonical(bool $anySite = false): ElementInterface;

    /**
     * {@inheritdoc}
     */
    public function getCurrentRevision(): ?ElementInterface
    {
        if (! $this->id) {
            return null;
        }

        if (! isset($this->currentRevision)) {
            $canonical = $this->getCanonical(true);

            $query = static::find()
                ->siteId($canonical->siteId)
                ->revisionOf($canonical->id)
                ->dateCreated($canonical->dateUpdated)
                ->status(null);

            if ($query instanceof ElementQuery) {
                $this->currentRevision = $query
                    ->orderBy(['num' => SORT_DESC])
                    ->one() ?: false;
            } else {
                $this->currentRevision = $query
                    ->orderByDesc('num')
                    ->first() ?: false;
            }
        }

        return $this->currentRevision ?: null;
    }
}
