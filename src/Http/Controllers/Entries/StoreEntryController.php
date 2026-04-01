<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\DateTimeHelper;
use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class StoreEntryController
{
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function __construct(
        private Request $request,
        private Elements $elements,
        private Entries $entries,
        private Sites $sites,
    ) {}

    public function __invoke(): Response
    {
        $entry = $this->getEntry();
        $entryVariable = $this->request->getSigned('entryVariable') ?? 'entry';

        $this->enforeSitePermission($entry->getSite());
        $this->enforceEditEntryPermissions($entry, $duplicate = $this->request->input('duplicate', false));

        // Keep track of whether the entry was disabled as a result of duplication
        $forceDisabled = false;

        if ($duplicate) {
            $response = $this->swapEntryWithDuplicate($entry, $forceDisabled);

            if (! is_null($response)) {
                return $response;
            }
        }

        $this->populateEntry($entry);

        if ($forceDisabled) {
            $entry->enabled = false;
        }

        // Save the entry (finally!)
        if ($entry->enabled && $entry->getEnabledForSite()) {
            $entry->setScenario(Element::SCENARIO_LIVE);
        }

        $isNotNew = (bool) $entry->id;
        if ($isNotNew) {
            $lockKey = "entry:$entry->id";
            $mutex = Cache::lock($lockKey, 15);
            if (! $mutex->get()) {
                throw new LockTimeoutException("Could not acquire a lock to save the entry: {$entry->id}.");
            }
        }

        try {
            $success = $this->elements->saveElement($entry);
        } catch (UnsupportedSiteException $e) {
            $entry->errors()->add('siteId', $e->getMessage());
            $success = false;
        } finally {
            if ($isNotNew) {
                $mutex->release();
            }
        }

        if (! $success) {
            return $this->asModelFailure(
                model: $entry,
                message: t('Couldn’t save entry.'),
                modelName: $entryVariable
            );
        }

        // See if the user happens to have a provisional entry. If so delete it.
        /** @var Entry|null $provisional */
        $provisional = Entry::find()
            ->provisionalDrafts()
            ->draftOf($entry->id)
            ->draftCreator($this->request->user()->id)
            ->siteId($entry->siteId)
            ->status(null)
            ->one();

        if ($provisional) {
            $this->elements->deleteElement($provisional, true);
        }

        $data = [];

        if ($this->request->acceptsJson()) {
            $data['id'] = $entry->id;
            $data['title'] = $entry->title;
            $data['slug'] = $entry->slug;

            if ($this->request->isCpRequest()) {
                $data['cpEditUrl'] = $entry->getCpEditUrl();
            }

            if (($author = $entry->getAuthor()) !== null) {
                $data['authorUsername'] = $author->username;
            }

            $data['dateCreated'] = DateTimeHelper::toIso8601($entry->dateCreated);
            $data['dateUpdated'] = DateTimeHelper::toIso8601($entry->dateUpdated);
            $data['postDate'] = ($entry->postDate ? DateTimeHelper::toIso8601($entry->postDate) : null);

            if ($this->request->isCpRequest()) {
                $data['elementHtml'] = app(ElementHtml::class)->elementChipHtml($entry);
            }
        }

        return $this->asModelSuccess(
            model: $entry,
            message: t('{type} saved.', ['type' => Entry::displayName()]),
            data: $data,
        );
    }

    private function getEntry(): Entry
    {
        $entryId = $this->request->input('canonicalId')
            ?? $this->request->input('sourceId')
            ?? $this->request->input('entryId');

        $siteId = $this->request->input('siteId');

        if (! $entryId) {
            $this->request->validate([
                'sectionId' => ['required'],
            ]);

            return new Entry(array_filter([
                'sectionId' => $this->request->input('sectionId'),
                'siteId' => $siteId,
            ]));
        }

        // Is this a provisional draft?
        if ($this->request->input('provisional')) {
            /** @var Entry|null $entry */
            $entry = Entry::find()
                ->provisionalDrafts()
                ->draftOf($entryId)
                ->draftCreator($this->request->user()->id)
                ->siteId($siteId)
                ->status(null)
                ->one();

            if ($entry) {
                return $entry;
            }
        }

        $entry = $this->entries->getEntryById($entryId, $siteId);

        abort_if(is_null($entry), 404, 'Entry not found.');

        return $entry;
    }

    private function swapEntryWithDuplicate(Entry $entry, bool &$forceDisabled): ?Response
    {
        try {
            $wasEnabled = $entry->enabled;
            $entry->draftId = null;
            $entry->isProvisionalDraft = false;
            $entry = $this->elements->duplicateElement($entry);
            if ($wasEnabled && ! $entry->enabled) {
                $forceDisabled = true;
            }
        } catch (InvalidElementException $e) {
            /** @var Entry $clone */
            $clone = $e->element;

            if ($this->request->acceptsJson()) {
                return $this->asModelFailure($clone);
            }

            // Send the original entry back to the template, with any validation errors on the clone
            $entry->errors()->merge($clone->errors());

            return $this->asModelFailure(
                model: $entry,
                message: t('Couldn’t duplicate {type}.', ['type' => Entry::lowerDisplayName()]),
                modelName: 'entry'
            );
        } catch (Throwable $e) {
            throw new Exception(t('An error occurred when duplicating the entry.'), 0, $e);
        }

        return null;
    }

    private function populateEntry(Entry $entry): void
    {
        // Set the entry attributes, defaulting to the existing values for whatever is missing from the post data
        $entry->setAttributesFromRequest(array_filter([
            'authorIds' => $this->request->input('authors') ?? $this->request->input('author') ?? $entry->getAuthorId() ?? $this->request->user()->id,
            'expiryDate' => $this->request->input('expiryDate'),
            'postDate' => $this->request->input('postDate'),
            'slug' => $this->request->input('slug'),
            'title' => $this->request->input('title'),
            'typeId' => $this->request->input('typeId'),
        ], fn ($value) => $value !== null));

        $enabledForSite = $this->enabledForSiteValue();
        if (is_array($enabledForSite)) {
            // Set the global status to true if it's enabled for *any* sites, or if already enabled.
            $entry->enabled = in_array(true, $enabledForSite) || $entry->enabled;
        } else {
            $entry->enabled = (bool) $this->request->input('enabled', $entry->enabled);
        }

        $entry->setEnabledForSite($enabledForSite ?? $entry->getEnabledForSite());

        if (! $entry->typeId) {
            // Default to the section's first entry type
            $entry->typeId = $entry->getAvailableEntryTypes()[0]->id;
        }

        // Authors
        if (empty($entry->getAuthorIds()) && ! $entry->id) {
            $entry->setAuthor($this->request->user());
        }

        // Parent
        if (($parentId = $this->request->input('parentId')) !== null) {
            $entry->setParentId($parentId);
        }

        // Prevent the last entry type's field layout from being used
        $entry->fieldLayoutId = null;

        $fieldsLocation = $this->request->input('fieldsLocation', 'fields');
        $entry->setFieldValuesFromRequest($fieldsLocation);

        // Is fresh?
        if ($this->request->input('isFresh')) {
            $entry->setIsFresh();
        }

        // Revision notes
        $entry->setRevisionNotes($this->request->input('notes'));
    }

    /**
     * Returns the posted `enabledForSite` value, taking the user’s permissions into account.
     *
     * @return bool|bool[]|null
     */
    private function enabledForSiteValue(): array|bool|null
    {
        $enabledForSite = $this->request->input('enabledForSite');

        if (! is_array($enabledForSite)) {
            return $enabledForSite;
        }

        // Make sure they are allowed to edit all of the posted site IDs
        $editableSiteIds = $this->sites->getEditableSiteIds()->all();

        if (array_diff(array_keys($enabledForSite), $editableSiteIds)) {
            abort(403, 'User not permitted to edit the statuses for all the submitted site IDs');
        }

        return $enabledForSite;
    }
}
