<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\base\Widget;
use craft\controllers\DashboardController;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Dashboard;
use craft\test\TestCase;
use craft\web\TemplateResponseBehavior;
use craft\widgets\MissingWidget;
use craft\widgets\MyDrafts;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
 * Unit tests for DashboardController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.2
 */
class DashboardControllerTest extends TestCase
{
    private ?string $originalRequestMethod = null;

    protected function _before(): void
    {
        parent::_before();
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, [$this, 'registerWidgetTypes']);
    }

    protected function _after(): void
    {
        Event::off(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, [$this, 'registerWidgetTypes']);
        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }
        parent::_after();
    }

    public function registerWidgetTypes(RegisterComponentTypesEvent $event): void
    {
        $event->types[] = RestrictedWidget::class;
    }

    public function testCreateRestrictedWidgetAsAdmin(): void
    {
        $this->setUser(true);
        $info = $this->createWidget(RestrictedWidget::class)->data['info'];
        self::assertSame(RestrictedWidget::BODY, $info['bodyHtml']);
    }

    /**
     * @dataProvider createForbiddenWidgetDataProvider
     */
    public function testCreateForbiddenWidget(string $type): void
    {
        $this->setUser(false);
        $this->expectException(ForbiddenHttpException::class);
        $this->createWidget($type);
    }

    public static function createForbiddenWidgetDataProvider(): array
    {
        return [
            'not selectable' => [RestrictedWidget::class],
            'not registered' => [UnregisteredWidget::class],
        ];
    }

    public function testCreateSecondSingleInstanceWidget(): void
    {
        $this->setUser(false);
        $this->createWidget(MyDrafts::class);
        $this->expectException(ForbiddenHttpException::class);
        $this->createWidget(MyDrafts::class);
    }

    public function testSaveSettingsForRestrictedWidget(): void
    {
        $this->setUser(true);
        $widgetId = $this->createWidget(RestrictedWidget::class)->data['info']['id'];

        $this->setUser(false);
        $this->expectException(ForbiddenHttpException::class);
        $this->saveWidgetSettings($widgetId);
    }

    public function testIndexSkipsRestrictedWidgets(): void
    {
        $this->setUser(true);
        $dashboard = Craft::$app->getDashboard();
        $dashboard->saveWidget($dashboard->createWidget(RestrictedWidget::class));
        $dashboard->saveWidget($dashboard->createWidget(MyDrafts::class));

        $adminTypes = $this->indexWidgetTypes();
        self::assertContains(RestrictedWidget::class, $adminTypes);
        self::assertContains(MyDrafts::class, $adminTypes);

        $this->setUser(false);
        $userTypes = $this->indexWidgetTypes();
        self::assertNotContains(RestrictedWidget::class, $userTypes);
        self::assertContains(MyDrafts::class, $userTypes);
    }

    public function testIsWidgetTypeAvailable(): void
    {
        $this->setUser(false);
        $dashboard = Craft::$app->getDashboard();
        $dashboard->saveWidget($dashboard->createWidget(MyDrafts::class));

        // An existing single-instance widget isn’t selectable anymore, but is still available
        self::assertFalse(MyDrafts::isSelectable());
        self::assertTrue($dashboard->isWidgetTypeAvailable(MyDrafts::class));
        self::assertFalse(MyDrafts::isSelectable());

        self::assertFalse($dashboard->isWidgetTypeAvailable(RestrictedWidget::class));
        self::assertFalse($dashboard->isWidgetTypeAvailable(UnregisteredWidget::class));
        self::assertFalse($dashboard->isWidgetTypeAvailable(MissingWidget::class));

        $this->setUser(true);
        self::assertTrue($dashboard->isWidgetTypeAvailable(RestrictedWidget::class));
    }

    private function setUser(bool $admin): void
    {
        Craft::$app->getUser()->setIdentity(new User(['id' => 1, 'admin' => $admin]));
    }

    private function createWidget(string $type)
    {
        $this->prepareRequest(['type' => $type]);
        return $this->controller()->actionCreateWidget();
    }

    private function indexWidgetTypes(): array
    {
        Craft::$app->getRequest()->setIsCpRequest(true);
        $response = $this->controller()->actionIndex();
        /** @var TemplateResponseBehavior $behavior */
        $behavior = $response->getBehavior(TemplateResponseBehavior::NAME);
        return array_column($behavior->variables['widgets'], 'type');
    }

    private function saveWidgetSettings(int $widgetId)
    {
        $this->prepareRequest(['widgetId' => $widgetId]);
        return $this->controller()->actionSaveWidgetSettings();
    }

    private function prepareRequest(array $bodyParams): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setIsCpRequest(true);
        $request->getHeaders()->set('Accept', 'application/json');
        $request->setBodyParams($bodyParams);
    }

    private function controller(): DashboardController
    {
        return new DashboardController('dashboard', Craft::$app);
    }
}

class RestrictedWidget extends Widget
{
    public const BODY = 'restricted widget body';

    public static function isSelectable(): bool
    {
        return Craft::$app->getUser()->getIsAdmin();
    }

    public function getBodyHtml(): ?string
    {
        return self::BODY;
    }
}

class UnregisteredWidget extends Widget
{
    public function getBodyHtml(): ?string
    {
        return 'unregistered widget body';
    }
}
