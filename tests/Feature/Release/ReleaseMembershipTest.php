<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Tests\TestClasses\Release\DraftableReleaseElement;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

it('attaches and detaches a saved draft of a non-entry element', function () {
    $actor = User::findOne();
    actingAs($actor);

    $model = Element::factory()->create(['type' => DraftableReleaseElement::class]);
    $canonical = DraftableReleaseElement::find()->id($model->id)->one();
    $draft = app(Drafts::class)->createDraft($canonical, $actor->id, 'Prepared content');
    expect(Elements::saveElement($draft))->toBeTrue();

    $releases = app(Releases::class);
    $release = $releases->create('Mixed content', null, $actor);
    $item = $releases->attach($release, $draft, $actor);

    expect($item->canonicalId)->toBe($canonical->id)
        ->and($item->draftId)->toBe($draft->draftId)
        ->and($releases->releaseForDraft($draft)?->id)->toBe($release->id);

    $releases->detach($release, $draft, $actor);

    expect($release->items()->count())->toBe(0)
        ->and(DraftableReleaseElement::find()->draftId($draft->draftId)->status(null)->one()?->id)->toBe($draft->id);
});
