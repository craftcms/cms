<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\elements\User;
use CraftCms\Cms\Database\Table;
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
     * @var int The revision number
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
}
