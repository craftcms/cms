<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withSession;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->entryTypes = app(EntryTypes::class);

    EntryType::factory()->create();
});

it('requires authentication', function () {
    Auth::logout();

    get(action([EntryTypesController::class, 'index']))->assertRedirect();
    get(action([EntryTypesController::class, 'create']))->assertRedirect();
    get(action([EntryTypesController::class, 'edit'], [EntryType::first()->id]))->assertRedirect();
    postJson(action([EntryTypesController::class, 'renderOverrideSettings']))->assertUnauthorized();
