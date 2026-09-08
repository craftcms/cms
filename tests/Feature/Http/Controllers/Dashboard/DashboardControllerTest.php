<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Models\Widget;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires login', function () {
    get(action([DashboardController::class, 'index']))
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('can be rendered', function () {
    actingAs(User::find()->one());

    get(action([DashboardController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('widgets', 4)
            ->where('widgets.0.name', 'Recent Entries')
            ->where('widgets.3.name', 'Feed'));
});

it('can render a Quick Post widget with empty settings', function () {
    $user = User::find()->one();
    actingAs($user);
    Widget::query()->create([
        'userId' => $user->id,
        'type' => QuickPost::class,
        'settings' => [],
        'sortOrder' => 1,
    ]);

    get(action([DashboardController::class, 'index']))
        ->assertOk();
});

it('preserves an intentionally empty dashboard', function () {
    actingAs($user = User::find()->one());
    UserModel::query()->whereKey($user->id)->update(['hasDashboard' => true]);

    get(route('craft.cp.dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('widgets', 0));

    expect(Widget::query()->where('userId', $user->id)->count())->toBe(0);
});

it('omits hidden widgets from the dashboard', function () {
    actingAs($user = User::find()->one());
    UserModel::query()->whereKey($user->id)->update(['hasDashboard' => true]);
    app(WidgetTypes::class)->register(HiddenDashboardWidget::class);
    Widget::query()->create([
        'userId' => $user->id,
        'type' => HiddenDashboardWidget::class,
        'settings' => [],
        'sortOrder' => 1,
    ]);

    get(route('craft.cp.dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('widgets', 0));
});

class HiddenDashboardWidget extends CraftCms\Cms\Dashboard\Widgets\Widget
{
    public function component(): ?string
    {
        return null;
    }

    public function props(): array
    {
        throw new LogicException('Hidden widgets must not render props.');
    }
}

it('renders a plugin component without a core widget mapping', function () {
    actingAs($user = User::find()->one());
    UserModel::query()->whereKey($user->id)->update(['hasDashboard' => true]);
    app(WidgetTypes::class)->register(DashboardVueWidget::class);

    Widget::query()->create([
        'userId' => $user->id,
        'type' => DashboardVueWidget::class,
        'settings' => [],
        'sortOrder' => 1,
    ]);

    get(route('craft.cp.dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('widgets.0.component', 'example:dashboard-widget')
            ->where('widgets.0.data.message', 'Plugin component'));
});

class DashboardVueWidget extends CraftCms\Cms\Dashboard\Widgets\Widget
{
    public function component(): string
    {
        return 'example:dashboard-widget';
    }

    public function props(): array
    {
        return ['message' => 'Plugin component'];
    }
}
