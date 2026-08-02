<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Http;

use craft\base\Widget;
use CraftCms\Cms\Dashboard\Models\Widget as WidgetModel;
use CraftCms\Cms\Dashboard\WidgetTypes;
use CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\User\Elements\User;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\HttpGateway;
use Override;

class DashboardWidgetCompatibilityTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        app()->bind(Gateway::class, HttpGateway::class);
        config()->set('inertia.ssr.enabled', false);
        $this->actingAs(User::find()->one());
        WidgetModel::query()->delete();
        app(WidgetTypes::class)->register(AdapterDashboardWidget::class);
    }

    public function test_it_projects_adapter_widget_settings_through_a_final_namespaced_legacy_island(): void
    {
        $response = $this->postJson(action([WidgetsController::class, 'store']), [
            'type' => AdapterDashboardWidget::class,
            'settings' => ['label' => 'Legacy'],
        ])->assertOk();
        $widget = WidgetModel::query()->firstOrFail();
        $namespace = "widget{$widget->id}-settings";

        self::assertSame(
            'yii2-adapter:legacy-settings',
            $response->json('info.settingsForm.elements.0.type'),
        );
        self::assertStringContainsString(
            "name=\"{$namespace}[label]\"",
            $response->json('info.settingsForm.elements.0.props.fragment.html'),
        );
        self::assertStringContainsString(
            '.adapter-widget { color: red; }',
            $response->json('info.settingsForm.elements.0.props.fragment.headHtml'),
        );
        self::assertSame($namespace, $response->json('info.settingsInputNamespace'));
    }
}

class AdapterDashboardWidget extends Widget
{
    public string $label = 'Legacy';

    public function getSettingsHtml(): string
    {
        HtmlStack::css('.adapter-widget { color: red; }');

        return '<input name="label" value="Legacy">';
    }

    public function getBodyHtml(): string
    {
        return '<p>Legacy widget</p>';
    }
}
