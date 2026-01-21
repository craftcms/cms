<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\Cms\User\Users;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class CreateEntryController
{
    use RespondsWithFlash;

    public function __construct(
        private Request $request,
        private Sections $sections,
        private Sites $sites,
        private Entries $entries,
    ) {}

    public function __invoke(Drafts $drafts, Users $users): Response
    {
        $section = $this->getSection();
        $site = $this->getSite($section);

        if ($site instanceof Response) {
            return $site;
        }

        $user = $this->request->user();

        // Create & populate the draft
        $entry = Craft::createObject(Entry::class);
        $entry->siteId = $site->id;
        $entry->sectionId = $section->id;
        $entry->setAuthorIds(
            $this->request->input('authorIds') ??
            $this->request->input('authorId') ??
            $user->id
        );
        $this->setTypeId($entry);
        $this->setStatus($entry, $section);

        // Structure parent
        if (
            $section->type === SectionType::Structure &&
            (int) $section->maxLevels !== 1
        ) {
            // Set the initially selected parent
            $entry->setParentId($this->request->input('parentId'));
        }

        // Make sure the user is allowed to create this entry
        $craftUser = $users->getUserById($user->id);
        abort_unless(Craft::$app->getElements()->canSave($entry, $craftUser), 403, 'User not authorized to create this entry.');

        $this->setTitleAndSlug($entry, $site);

        // Resume time
        DateTimeHelper::pause();

        $this->setDates($entry);

        // Custom fields
        foreach ($entry->getFieldLayout()->getCustomFields() as $field) {
            if (($value = $this->request->input($field->handle)) !== null) {
                $entry->setFieldValue($field->handle, $value);
            }
        }

        // Save it
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $success = $drafts->saveElementAsDraft($entry, $user->id, markAsSaved: false);

        // Resume time
        DateTimeHelper::resume();

        if (! $success) {
            return $this->asModelFailure($entry, mb_ucfirst(t('Couldn’t create {type}.', [
                'type' => Entry::lowerDisplayName(),
            ])), 'entry');
        }

        $this->setPositionInStructure($entry, $section, $site);

        $editUrl = $entry->getCpEditUrl();

        $response = $this->asModelSuccess($entry, t('{type} created.', [
            'type' => Entry::displayName(),
        ]), 'entry', array_filter([
            'cpEditUrl' => $this->request->isCpRequest() ? $editUrl : null,
        ]));

        if (! $this->request->wantsJson()) {
            $response->headers->set('Location', UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }

    private function getSection(): Section
    {
        $sectionHandle = $this->request->route('section');

        if (! $sectionHandle) {
            $sectionHandle = $this->request->validate([
                'section' => ['required', 'string'],
            ])['section'];
        }

        $section = $this->sections->getSectionByHandle($sectionHandle);

        abort_if(is_null($section), 400, "Invalid section handle: $sectionHandle");

        return $section;
    }

    private function getSite(Section $section): Site|Response
    {
        $siteId = $this->request->integer('siteId');

        if ($siteId) {
            $site = $this->sites->getSiteById($siteId);
            abort_if(is_null($site), 400, "Invalid site ID: $siteId");
        } else {
            $site = Cp::requestedSite();
            abort_if(is_null($site), 403, 'User not authorized to edit content in any sites.');
        }

        $editableSiteIds = $this->sites->getEditableSiteIdsForSection($section);

        abort_if($editableSiteIds->isEmpty(), 403, 'User not permitted to edit content in any sites supported by this section');

        if ($editableSiteIds->doesntContain($site->id)) {
            // If there’s more than one possibility and entries doesn’t propagate to all sites, let the user choose
            if ($editableSiteIds->count() > 1 && $section->propagationMethod !== PropagationMethod::All) {
                return response(Craft::$app->getView()->renderTemplate('_special/sitepicker.twig', [
                    'siteIds' => $editableSiteIds->all(),
                    'baseUrl' => sprintf('%s/new', $section->getCpIndexUri()),
                ]));
            }

            // Go with the first one
            return $this->sites->getSiteById($editableSiteIds->first());
        }

        return $site;
    }

    private function setTypeId(Entry $entry): void
    {
        if (! $typeHandle = $this->request->input('type')) {
            $entry->typeId = $this->request->input('typeId', $entry->getAvailableEntryTypes()[0]->id);

            return;
        }

        $type = collect($entry->getAvailableEntryTypes())->firstWhere('handle', $typeHandle);

        abort_if(is_null($type), 400, "Invalid entry type handle: $typeHandle");

        $entry->typeId = $type->id;
    }

    private function setStatus(Entry $entry, Section $section): void
    {
        if (($status = $this->request->input('status')) !== null) {
            $enabled = $status === 'enabled';
        } else {
            // Set the default status based on the section's settings
            $enabled = collect($section->getSiteSettings())
                ->firstWhere('siteId', $entry->siteId)
                ->enabledByDefault;
        }

        if ($this->sites->isMultiSite() && count($entry->getSupportedSites()) > 1) {
            $entry->enabled = true;
            $entry->setEnabledForSite($enabled);
        } else {
            $entry->enabled = $enabled;
            $entry->setEnabledForSite(true);
        }
    }

    private function setTitleAndSlug(Entry $entry, Site $site): void
    {
        // Title & slug
        $entry->title = $this->request->input('title');
        $entry->slug = $this->request->input('slug');

        if ($entry->title && ! $entry->slug) {
            $entry->slug = ElementHelper::generateSlug($entry->title, null, $site->getLanguage());
        }

        if (! $entry->slug) {
            $entry->slug = ElementHelper::tempSlug();
        }
    }

    private function setDates(Entry $entry): void
    {
        // Post & expiry dates
        if (($postDate = $this->request->input('postDate')) !== null) {
            $entry->postDate = DateTimeHelper::toDateTime($postDate);
        } else {
            $entry->postDate = DateTimeHelper::now();
        }

        if (($expiryDate = $this->request->input('expiryDate')) !== null) {
            $entry->expiryDate = DateTimeHelper::toDateTime($expiryDate);
        }
    }

    private function setPositionInStructure(Entry $entry, Section $section, Site $site): void
    {
        if ($section->type !== SectionType::Structure) {
            return;
        }

        if ($nextId = $this->request->input('before')) {
            $nextEntry = $this->entries->getEntryById($nextId, $site->id, [
                'structureId' => $section->structureId,
            ]);

            Structures::moveBefore($section->structureId, $entry, $nextEntry);

            return;
        }

        if ($prevId = $this->request->input('after')) {
            $prevEntry = $this->entries->getEntryById($prevId, $site->id, [
                'structureId' => $section->structureId,
            ]);

            Structures::moveAfter($section->structureId, $entry, $prevEntry);
        }
    }
}
