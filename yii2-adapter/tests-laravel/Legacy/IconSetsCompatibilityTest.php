<?php

declare(strict_types=1);

use craft\events\RegisterIconSetsEvent;
use craft\icons\IconSet as LegacyIconSet;
use craft\services\Icons;
use CraftCms\Cms\Cp\IconSets;
use yii\base\Event as YiiEvent;

beforeEach(function() {
    app()->forgetInstance(IconSets::class);
});

afterEach(function() {
    YiiEvent::off(Icons::class, Icons::EVENT_REGISTER_ICON_SETS);
    app()->forgetInstance(IconSets::class);
});

it('registers icon sets from legacy EVENT_REGISTER_ICON_SETS handlers', function() {
    YiiEvent::on(Icons::class, Icons::EVENT_REGISTER_ICON_SETS, function(RegisterIconSetsEvent $event) {
        $event->iconSets[] = new class([ 'handle' => 'my-icons', 'name' => 'My Icons', ]) extends LegacyIconSet {
            public function getIcons(): array
            {
                return ['dot'];
            }

            public function getSvg(string $icon): ?string
            {
                return $icon === 'dot' ? '<svg viewBox="0 0 1 1"><circle r="1"/></svg>' : null;
            }
        };
    });

    expect(Craft::$app->getIcons()->getIconSetNames())->toBe([
        Icons::FONT_AWESOME => 'Font Awesome',
        'my-icons' => 'My Icons',
    ])
        ->and(app(IconSets::class)->getIconSetByHandle('my-icons')?->getIcons())->toBe(['dot'])
        ->and(Icons::parse('my-icons:dot'))->toBe(['my-icons', 'dot']);
});
