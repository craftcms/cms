<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\Widget;

it('can instantiate with settings', function () {
    class TestWidget extends Widget
    {
        public string $foo;
    }

    $widget = Widget::fromConfig(new \CraftCms\Cms\Dashboard\Models\Widget([
        'userId' => 1,
        'colspan' => 4,
        'type' => TestWidget::class,
        'settings' => [
            'foo' => 'bar',
        ],
    ]));

    expect($widget->foo)->toBe('bar');
    expect($widget->colspan)->toBe(4);
    expect(isset($widget->userId))->toBeFalse();
});
