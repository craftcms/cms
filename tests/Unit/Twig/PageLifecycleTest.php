<?php

declare(strict_types=1);

use CraftCms\Cms\Twig\Events\BeginPage;
use CraftCms\Cms\Twig\Events\EndPage;
use CraftCms\Cms\Twig\PageLifecycle;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    app()->forgetScopedInstances();

    $this->lifecycle = app(PageLifecycle::class);
    $this->registry = app(HtmlStack::class);
});

describe('head, beginBody, endBody', function () {
    it('outputs the correct placeholder', function (string $method, string $placeholder) {
        ob_start();
        $this->lifecycle->$method();
        $output = ob_get_clean();

        expect($output)->toBe($placeholder);
    })->with([
        'head' => ['head', PageLifecycle::HEAD_PLACEHOLDER],
        'beginBody' => ['beginBody', PageLifecycle::BODY_BEGIN_PLACEHOLDER],
        'endBody' => ['endBody', PageLifecycle::BODY_END_PLACEHOLDER],
    ]);
});

describe('event dispatching', function () {
    it('fires BeginPage and EndPage events in order', function () {
        $events = [];

        Event::listen(BeginPage::class, function () use (&$events) {
            $events[] = 'BeginPage';
        });
        Event::listen(EndPage::class, function () use (&$events) {
            $events[] = 'EndPage';
        });

        $this->lifecycle->wrap(fn () => 'content');

        expect($events)->toBe(['BeginPage', 'EndPage']);
    });

    it('fires BeginPage before the render callback executes', function () {
        $beginPageFired = false;
        $beginPageFiredBeforeRender = false;

        Event::listen(BeginPage::class, function () use (&$beginPageFired) {
            $beginPageFired = true;
        });

        $this->lifecycle->wrap(function () use (&$beginPageFired, &$beginPageFiredBeforeRender) {
            $beginPageFiredBeforeRender = $beginPageFired;

            return 'content';
        });

        expect($beginPageFiredBeforeRender)->toBeTrue();
    });

    it('fires EndPage after the render callback executes', function () {
        $renderExecuted = false;
        $renderExecutedBeforeEndPage = false;

        Event::listen(EndPage::class, function () use (&$renderExecuted, &$renderExecutedBeforeEndPage) {
            $renderExecutedBeforeEndPage = $renderExecuted;
        });

        $this->lifecycle->wrap(function () use (&$renderExecuted) {
            $renderExecuted = true;

            return 'content';
        });

        expect($renderExecutedBeforeEndPage)->toBeTrue();
    });
});

describe('placeholder replacement', function () {
    it('replaces head placeholder with HtmlStack output when EndPage is not overridden', function () {
        $this->registry->css('body { color: red }');

        $output = $this->lifecycle->wrap(function () {
            echo PageLifecycle::HEAD_PLACEHOLDER;

            return '';
        });

        expect($output)->toContain('<style>body { color: red }</style>');
    });

    it('replaces body begin placeholder with HtmlStack output', function () {
        $this->registry->js('var x = 1', Position::BodyBegin);

        $output = $this->lifecycle->wrap(function () {
            echo PageLifecycle::BODY_BEGIN_PLACEHOLDER;

            return '';
        });

        expect($output)->toContain('var x = 1;');
    });

    it('replaces body end placeholder with HtmlStack output', function () {
        $this->registry->js('var y = 2');

        $output = $this->lifecycle->wrap(function () {
            echo PageLifecycle::BODY_END_PLACEHOLDER;

            return '';
        });

        expect($output)->toContain('var y = 2;');
    });

    it('replaces all three placeholders in a single page', function () {
        $this->registry->css('.head {}');
        $this->registry->script('beginScript()', Position::BodyBegin);
        $this->registry->js('var end = 1');

        $output = $this->lifecycle->wrap(function () {
            echo '<head>'.PageLifecycle::HEAD_PLACEHOLDER.'</head>';
            echo '<body>'.PageLifecycle::BODY_BEGIN_PLACEHOLDER;

            echo 'content';
            echo PageLifecycle::BODY_END_PLACEHOLDER.'</body>';

            return '';
        });

        expect($output)
            ->toContain('.head')
            ->toContain('beginScript()')
            ->toContain('var end = 1;')
            ->toContain('content');
    });

    it('uses EndPage event overrides when set', function (string $property, string $placeholder, string $html) {
        Event::listen(EndPage::class, function (EndPage $event) use ($property, $html) {
            $event->$property = $html;
        });

        $output = $this->lifecycle->wrap(function () use ($placeholder) {
            echo $placeholder;

            return '';
        });

        expect($output)->toContain($html);
    })->with([
        'head html' => ['headHtml', PageLifecycle::HEAD_PLACEHOLDER, '<meta name="custom" content="value">'],
        'body begin html' => ['bodyBeginHtml', PageLifecycle::BODY_BEGIN_PLACEHOLDER, '<div id="body-begin">'],
        'body end html' => ['bodyEndHtml', PageLifecycle::BODY_END_PLACEHOLDER, '<script>console.log("end")</script>'],
    ]);

    it('prefers EndPage overrides over HtmlStack output', function () {
        $this->registry->css('.from-registry {}');

        Event::listen(EndPage::class, function (EndPage $event) {
            $event->headHtml = '<meta name="from-event">';
        });

        $output = $this->lifecycle->wrap(function () {
            echo PageLifecycle::HEAD_PLACEHOLDER;

            return '';
        });

        expect($output)
            ->toContain('<meta name="from-event">')
            ->not->toContain('.from-registry');
    });

    it('falls back to HtmlStack when EndPage properties are null', function () {
        $this->registry->css('.fallback {}');

        $output = $this->lifecycle->wrap(function () {
            echo PageLifecycle::HEAD_PLACEHOLDER;

            return '';
        });

        expect($output)->toContain('.fallback');
    });

    it('produces empty strings when no assets registered and no EndPage overrides', function () {
        $output = $this->lifecycle->wrap(function () {
            echo '<head>'.PageLifecycle::HEAD_PLACEHOLDER.'</head>';
            echo '<body>'.PageLifecycle::BODY_BEGIN_PLACEHOLDER;
            echo PageLifecycle::BODY_END_PLACEHOLDER.'</body>';

            return '';
        });

        expect($output)->toBe('<head></head><body></body>');
    });
});

describe('output buffering', function () {
    it('captures echoed output from the render callback', function () {
        $output = $this->lifecycle->wrap(function () {
            echo 'echoed content';

            return '';
        });

        expect($output)->toContain('echoed content');
    });

    it('captures both echoed output and returned output', function () {
        $output = $this->lifecycle->wrap(function () {
            echo 'before ';

            return 'returned';
        });

        expect($output)->toContain('before ')
            ->toContain('returned');
    });

    it('does not leak output buffering on success', function () {
        $levelBefore = ob_get_level();

        $this->lifecycle->wrap(fn () => 'content');

        expect(ob_get_level())->toBe($levelBefore);
    });

    it('cleans up the output buffer on exception', function () {
        $levelBefore = ob_get_level();

        try {
            $this->lifecycle->wrap(function () {
                throw new RuntimeException('render failed');
            });
        } catch (RuntimeException) {
        }

        expect(ob_get_level())->toBe($levelBefore);
    });

    it('re-throws exceptions from the render callback', function () {
        expect(fn () => $this->lifecycle->wrap(function () {
            throw new RuntimeException('render failed');
        }))->toThrow(RuntimeException::class, 'render failed');
    });

    it('does not fire EndPage when the render callback throws', function () {
        $endPageFired = false;

        Event::listen(EndPage::class, function () use (&$endPageFired) {
            $endPageFired = true;
        });

        try {
            $this->lifecycle->wrap(function () {
                throw new RuntimeException('render failed');
            });
        } catch (RuntimeException) {
        }

        expect($endPageFired)->toBeFalse();
    });

    it('does not replace placeholders when the render callback throws', function () {
        $this->registry->css('.should-not-appear {}');

        try {
            $this->lifecycle->wrap(function () {
                echo PageLifecycle::HEAD_PLACEHOLDER;

                throw new RuntimeException('render failed');
            });
        } catch (RuntimeException) {
        }

        expect(ob_get_level())->toBeGreaterThanOrEqual(0);
    });
});

describe('scoped resolution', function () {
    it('is resolved as a scoped instance', function () {
        $first = app(PageLifecycle::class);
        $second = app(PageLifecycle::class);

        expect($first)->toBe($second);
    });
});
