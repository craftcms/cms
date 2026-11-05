<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

/**
 * Opens a legacy slideout over a button that counts its clicks, optionally with a CSS transition on its shade and
 * container. The shade is shared by every open panel, so it's kept for checking after the slideout lets go of it.
 */
function openSlideout(?string $transition = null): AwaitableWebpage
{
    $page = visitCp('dashboard');

    $page->script(sprintf(<<<'JS'
        (() => {
            const $button = window.jQuery('<button id="underlying-button" type="button">Underlying button</button>').appendTo(document.body);
            window.underlyingClicks = 0;
            $button.on('click', () => window.underlyingClicks++);

            window.slideoutCloseEvents = 0;
            window.testSlideout = new window.Craft.Slideout('<button type="button">Slideout button</button>', {triggerElement: $button});
            window.testSlideout.on('close', () => window.slideoutCloseEvents++);
            window.testShade = window.testSlideout.$shade[0];

            const transition = %s;
            if (transition) {
                window.testShade.style.transition = transition;
                window.testSlideout.$container.css('transition', transition);
            }
        })()
        JS, json_encode($transition)));

    return $page;
}

const SLIDEOUT_STATE = <<<'JS'
    ({
        closeEvents: window.slideoutCloseEvents,
        isOpen: window.testSlideout.isOpen,
        panelVisible: window.testSlideout.$container[0].checkVisibility({opacityProperty: true, visibilityProperty: true}),
        shadeVisible: window.testShade.checkVisibility({opacityProperty: true, visibilityProperty: true}),
    })
    JS;

const CLOSED_SLIDEOUT = ['closeEvents' => 1, 'isOpen' => false, 'panelVisible' => false, 'shadeVisible' => false];

it('completes the close lifecycle once', function () {
    $page = openSlideout();
    $page->script('window.testSlideout.close()');

    $page->assertScript(SLIDEOUT_STATE, CLOSED_SLIDEOUT)
        ->wait(0.1)
        ->assertScript('window.slideoutCloseEvents', 1);
});

it('finishes closing when a transition emits no event', function () {
    $page = openSlideout('transform 100ms linear');
    $page->script('window.testSlideout.close()');

    $page->assertScript(SLIDEOUT_STATE, CLOSED_SLIDEOUT)
        ->click('#underlying-button')
        ->assertScript('window.underlyingClicks', 1);
});

it('cancels a pending close when the slideout reopens', function () {
    $page = openSlideout('transform 250ms linear');
    $page->script('(() => { window.testSlideout.close(); window.testSlideout.open(); })()');

    $page->wait(0.4)
        ->assertScript(SLIDEOUT_STATE, ['closeEvents' => 0, 'isOpen' => true, 'panelVisible' => true, 'shadeVisible' => true])
        ->assertEnabled('role=button[name="Slideout button"]');
});
