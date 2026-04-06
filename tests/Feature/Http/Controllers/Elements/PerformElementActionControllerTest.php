<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\Events\RegisterActions;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->performElementAction = fn (array $payload = []) => postJson(
        action(PerformElementActionController::class),
        array_merge([
            'context' => 'index',
            'source' => '*',
            'viewState' => [
                'mode' => 'table',
                'static' => false,
            ],
        ], $payload),
    );
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action(PerformElementActionController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('validates required request params', function () {
    ($this->performElementAction)([
        'elementType' => Entry::class,
    ])->assertUnprocessable();
});

it('returns 400 for unsupported actions', function () {
    ($this->performElementAction)([
        'elementType' => Entry::class,
        'elementAction' => Delete::class,
        'elementIds' => [1],
        'source' => null,
    ])->assertStatus(400);
});

it('returns native Laravel download responses for download actions', function () {
    $entry = EntryModel::factory()->createElement();

    $action = new class extends ElementAction
    {
        public static function isDownload(): bool
        {
            return true;
        }

        public function performAction(ElementQueryInterface $query): bool
        {
            $this->setResponse(new Response(
                content: 'downloaded',
                status: 200,
                headers: [
                    'Content-Disposition' => 'attachment; filename=entries.txt',
                ],
            ));

            return true;
        }
    };

    Event::listen(function (RegisterActions $event) use ($action) {
        if ($event->elementType === Entry::class) {
            $event->actions[] = clone $action;
        }
    });

    $response = post(action(PerformElementActionController::class), [
        'context' => 'index',
        'source' => '*',
        'viewState' => [
            'mode' => 'table',
            'static' => false,
        ],
        'elementType' => Entry::class,
        'elementAction' => $action::class,
        'elementIds' => [$entry->id],
    ]);

    $response->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('entries.txt')
        ->and($response->getContent())->toBe('downloaded');
});

it('includes exporter metadata in the refreshed element response', function () {
    $entry = EntryModel::factory()->createElement();

    ($this->performElementAction)([
        'elementType' => Entry::class,
        'elementAction' => Delete::class,
        'elementIds' => [$entry->id],
    ])->assertOk()
        ->assertJsonPath('exporters.0.type', Raw::class)
        ->assertJsonPath('exporters.0.formattable', true);
});
