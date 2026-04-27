<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use Craft;
use craft\elements\actions\Delete as LegacyDelete;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Yii2Adapter\Tests\TestCase;

class ElementActionCompatibilityTest extends TestCase
{
    public function testLegacyActionClassNamesStillInstantiate(): void
    {
        $action = Craft::$app->getElements()->createAction(LegacyDelete::class);

        self::assertInstanceOf(LegacyDelete::class, $action);
        self::assertInstanceOf(Delete::class, $action);
        self::assertInstanceOf(ElementActionInterface::class, $action);
    }

    public function testLegacyElementActionSupportsYiiDefineRules(): void
    {
        $action = new class() extends \craft\base\ElementAction {
            public ?string $status = null;

            protected function defineRules(): array
            {
                return [
                    [['status'], 'required'],
                ];
            }
        };

        self::assertFalse($action->validate());
        self::assertTrue($action->hasErrors('status'));
        self::assertSame(['Status cannot be blank.'], $action->getErrors('status'));

        $action->status = 'enabled';

        self::assertTrue($action->validate());
        self::assertFalse($action->hasErrors('status'));
    }

    public function testLegacyDownloadActionsExposeSymfonyResponses(): void
    {
        $action = new class() extends \craft\base\ElementAction {
            public static function isDownload(): bool
            {
                return true;
            }
        };

        $response = Craft::$app->getResponse();
        $response->clear();
        $response->setStatusCode(200);
        $response->content = 'downloaded';
        $response->setDownloadHeaders('entries.txt');

        $downloadResponse = $action->getResponse();

        self::assertNotNull($downloadResponse);
        self::assertSame('downloaded', $downloadResponse->getContent());
        self::assertStringContainsString('entries.txt', (string)$downloadResponse->headers->get('content-disposition'));
    }
}
