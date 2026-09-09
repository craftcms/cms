<?php

declare(strict_types=1);

use CraftCms\Yii2Adapter\Log\LogTarget;
use Illuminate\Support\Facades\Log;
use yii\log\Target;

it('dispatches legacy log messages to configured targets', function(): void {
    CapturingLegacyLogTarget::$exportedMessages = [];
    Craft::$app->getLog()->targets['custom-module'] = new CapturingLegacyLogTarget([
        'categories' => ['custom-module'],
        'logVars' => [],
    ]);

    Craft::info('included', 'custom-module');
    Craft::info('excluded', 'other-module');

    app()->terminate();

    expect(CapturingLegacyLogTarget::$exportedMessages)
        ->toHaveCount(1)
        ->and(CapturingLegacyLogTarget::$exportedMessages[0][0])->toBe('included')
        ->and(CapturingLegacyLogTarget::$exportedMessages[0][2])->toBe('custom-module');
});

it('forwards legacy log messages to Laravel without legacy default targets', function(): void {
    Log::spy();

    expect(Craft::$app->getLog()->targets)
        ->toHaveCount(1)
        ->and(array_values(Craft::$app->getLog()->targets)[0])->toBeInstanceOf(LogTarget::class);

    Craft::info('forwarded', 'custom-module');

    app()->terminate();

    Log::shouldHaveReceived('log')
        ->withArgs(fn(string $level, string $message, array $context): bool => $level === 'info'
            && $message === 'forwarded'
            && $context['category'] === 'custom-module')
        ->atLeast()
        ->once();
});

class CapturingLegacyLogTarget extends Target
{
    public static array $exportedMessages = [];

    public function export(): void
    {
        self::$exportedMessages = array_merge(self::$exportedMessages, $this->messages);
    }
}
