<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Events\EntryTypesResolving;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Section\Resources\SectionResource;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

it('lists the entry types available in each site, as new entries would get them', function () {
    Edition::set(Edition::Pro);

    $article = EntryType::factory()->create(['handle' => 'article']);
    $review = EntryType::factory()->create(['handle' => 'review']);
    $primarySite = Sites::getPrimarySite();
    $reviewsOnlySite = Sites::getSiteById(Site::factory()->create()->id);
    $closedSite = Sites::getSiteById(Site::factory()->create()->id);
    $section = Section::factory()->withEntryTypes($article, $review)->create();
    SectionSiteSettings::factory()->create(['sectionId' => $section->id, 'siteId' => $reviewsOnlySite->id]);
    SectionSiteSettings::factory()->create(['sectionId' => $section->id, 'siteId' => $closedSite->id]);
    $user = User::factory()->create();
    actingAs($user);
    Sections::refreshSections();

    $authorIds = [];
    Event::listen(function (EntryTypesResolving $event) use ($reviewsOnlySite, $closedSite, &$authorIds): void {
        $authorIds[] = $event->entry->getAuthorIds();
        $event->entryTypes = match ($event->entry->siteId) {
            $reviewsOnlySite->id => array_values(array_filter($event->entryTypes, fn ($type) => $type->handle === 'review')),
            $closedSite->id => [],
            default => $event->entryTypes,
        };
    });

    $resource = Json::decode(Json::encode(SectionResource::make(Sections::getSectionById($section->id))));
    expect($resource['sites'])->toBe([$primarySite->id, $reviewsOnlySite->id])
        ->and(array_column($resource['entryTypes'], 'handle'))->toBe(['article', 'review'])
        ->and($resource['entryTypeIdsBySite'])->toBe([
            $primarySite->id => [$article->id, $review->id],
            $reviewsOnlySite->id => [$review->id],
        ])
        ->and(array_unique($authorIds, SORT_REGULAR))->toBe([[$user->id]]);
});
