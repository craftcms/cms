<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Entries;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Entries;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Support\Html;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

use function CraftCms\Cms\t;

readonly class MoveEntryToSectionController
{
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function __construct(
        private Request $request,
        private Sections $sections,
        private Entries $entries,
    ) {}

    public function showModal(): JsonResponse
    {
        [
            'entryIds' => $entryIds,
            'siteId' => $siteId,
            'currentSectionUid' => $currentSectionUid,
        ] = $this->request->validate([
            'entryIds' => ['required', 'array'],
            'entryIds.*' => ['integer'],
            'siteId' => ['required', 'integer', Rule::exists(Table::SITES, 'id')],
            'currentSectionUid' => ['required', 'string'],
        ]);

        // get entry types by entry IDs
        $entryTypes = DB::table(Table::ENTRYTYPES, 'et')
            ->distinct()
            ->leftJoin(new Alias(Table::ENTRIES, 'e'), 'e.typeId', 'et.id')
            ->whereIn('e.id', $entryIds)
            ->pluck('et.id')
            ->all();

        // filter all sections to those that have all the entry types we just got
        $compatibleSections = $this->sections->getAvailableEntryMoveTargetSections(
            entryTypeIds: $entryTypes,
            siteId: $siteId,
            currentSectionUid: $currentSectionUid,
        );

        if (empty($compatibleSections)) {
            $listHtml = Html::tag(
                name: 'p',
                content: t('Couldn’t find any sections that all selected elements could be moved to.'),
                attributes: ['class' => 'zilch']
            );
        } else {
            $listHtml = '';
            foreach ($compatibleSections as $section) {
                $listHtml .= app(ElementHtml::class)->chipHtml($section, [
                    'selectable' => true,
                    'class' => 'fullwidth',
                ]);
            }
        }

        return new JsonResponse(['listHtml' => $listHtml]);
    }

    public function move(): Response
    {
        [
            'sectionId' => $sectionId,
            'entryIds' => $entryIds,
        ] = $this->request->validate([
            'entryIds' => ['required', 'array', 'min:1'],
            'entryIds.*' => ['integer'],
            'sectionId' => ['required', 'integer', Rule::exists(Table::SECTIONS, 'id')],
        ]);

        $section = $this->sections->getSectionById($sectionId);

        abort_if(is_null($section), 400, 'Cannot find the section to move the entries to.');

        $this->requirePermission("saveEntries:$section->uid");

        /** @var Collection<int, Entry> $entries */
        $entries = Entry::find()
            ->id($entryIds)
            ->status(null)
            ->drafts(null)
            ->site('*')
            ->unique()
            ->get();

        abort_if($entries->isEmpty(), 400, 'Cannot find the entries to move to the new section.');

        foreach ($entries as $entry) {
            abort_if(! $this->request->craftUser()?->can('moveToSection', [$entry, $section]), 403, 'User is not authorized to perform this action.');
        }

        $sectionEntryTypeIds = array_map(fn ($entryType) => $entryType->id, $section->getEntryTypes());
        $entryTypeIds = $entries->pluck('typeId')->unique()->all();

        abort_if(
            ! empty(array_diff($entryTypeIds, $sectionEntryTypeIds)),
            400,
            'Not all entries have a type supported by the target section.',
        );

        $errors = [];
        foreach ($entries as $entry) {
            try {
                $this->entries->moveEntryToSection($entry, $section);
            } catch (Throwable $e) {
                Log::error('Could not delete move entry to a different section: '.$e->getMessage(), [__METHOD__]);
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            return $this->asSuccess(t(
                'Entries have been moved to the “{name}” section.',
                ['name' => $section->name]
            ));
        }

        if (count($errors) === count($entries)) {
            return $this->asFailure(t(
                'Couldn’t move entries to the “{name}” section.',
                ['name' => $section->name]
            ));
        }

        return $this->asSuccess(t(
            'Some entries have been moved to the “{name}” section.',
            ['name' => $section->name]
        ));
    }
}
