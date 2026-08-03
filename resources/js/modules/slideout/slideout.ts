/**
 * Slideout — a slide-in panel: builds a container around passed-in contents
 * and manages its shade, open/close transitions, dismissal, focus, and
 * stacking against other open panels. See the module README for usage.
 *
 * Two structural constraints shape this class:
 *
 * - **Legacy subclasses exist.** `Craft.AuthMethodSetup.Slideout` (and, one
 *   level down, `Craft.ElementEditorSlideout`) subclass the `Craft.Slideout`
 *   global via `Garnish.Base.extend()`, calling `this.base(contents,
 *   settings)` from their own `init`. So {@link init} is a real public
 *   method, invoked from the constructor only for the leaf class (the
 *   `new.target === Slideout` guard below), and `index.ts` assigns the
 *   global as `compatify(Slideout)` so `.extend()`/`this.base()` dispatch
 *   into it.
 * - **The `$`-prefixed members are public API.** External code reads them
 *   directly (`ElementEditor.js`'s `this.$container.data('slideout')`,
 *   `CP.js`'s `$modal.find('.slideout').data('slideout')`,
 *   `CpScreenSlideout`'s chrome), so the names stay. Most are still jQuery
 *   collections; {@link Slideout.$liveRegion} is a plain element, matching the
 *   ported Garnish `Modal` — `Craft.cp.announce()` normalizes either form.
 *   `declare const $` / `declare const Craft` are page globals.
 */

import {
    Base,
    Garnish,
    initGarnish,
    ESC_KEY,
    bod,
    addModalAttributes,
    hideModalBackgroundLayers,
    resetModalBackgroundLayerVisibility,
    prefersReducedMotion,
    type GarnishBaseSettings,
} from '@craftcms/garnish';
import {containerSlideouts} from './support';

declare const Craft: any;
declare const $: any;

// Populate `Garnish.uiLayerManager` (idempotent) so it's available the first
// time a slideout opens, regardless of whether anything else has imported
// `@craftcms/garnish/compat` (which also calls this) yet.
initGarnish();

/**
 * The UI-layer manager slideouts should coordinate with. The legacy garnish
 * bundle instantiates its OWN `Garnish.UiLayerManager` singleton, and every
 * legacy layer consumer (modals, menus, HUDs) registers with it — if slideouts
 * used the modern singleton on those pages, Escape handling and layer stacking
 * would run in two disconnected managers (e.g. one Escape press closing both a
 * menu and the slideout behind it). Prefer the page's legacy manager when
 * present; fall back to the modern one for legacy-free surfaces.
 *
 * Exported so `cp-screen-slideout.ts` can reuse this same lookup for its own
 * `registerShortcut`/`addLayer`/`removeLayer` calls instead of reimplementing it.
 */
export function uiLayerManager(): any {
    return (window as any).Garnish?.uiLayerManager ?? Garnish.uiLayerManager!;
}

/**
 * Same split-brain concern as {@link uiLayerManager}: the modern
 * `hideModalBackgroundLayers`/`resetModalBackgroundLayerVisibility` utils keep
 * their own hidden-layer bookkeeping, separate from the legacy bundle's. Use
 * the page's legacy implementations when present so a slideout's hide/reset
 * pairs with the modals/HUDs it stacks against.
 */
function hideBackgroundLayers(): void {
    const legacy = (window as any).Garnish?.hideModalBackgroundLayers;
    (legacy ?? hideModalBackgroundLayers)();
}

function resetBackgroundLayerVisibility(): void {
    const legacy = (window as any).Garnish?.resetModalBackgroundLayerVisibility;
    (legacy ?? resetModalBackgroundLayerVisibility)();
}

/**
 * A single status live region shared by every slideout — it moves (not
 * clones) into whichever container most recently initialized, since screen
 * readers only announce one status region at a time.
 *
 * Built with the DOM API rather than `$('<span …>')` so that merely importing
 * this module doesn't need jQuery: `cp.ts` pulls it in on every Inertia CP
 * page, including ones that never load the legacy bundle, and a bare `$` at
 * module scope would throw there and abort the whole CP bootstrap. Same
 * construction as the modern Garnish `Modal` port's live region.
 */
const sharedLiveRegion = document.createElement('span');
sharedLiveRegion.className = 'visually-hidden';
sharedLiveRegion.setAttribute('role', 'status');

/**
 * Settings accepted by {@link Slideout}. Pass a `Partial<SlideoutSettings>` to
 * the constructor; unset keys fall back to {@link Slideout.defaults}.
 */
export interface SlideoutSettings extends GarnishBaseSettings {
    /** Tag name for the generated container element. Default `'div'`. */
    containerElement: string;
    /** Attributes applied to the container element via jQuery's `$(tag, attrs)`. */
    containerAttributes: Record<string, unknown>;
    /** Whether {@link Slideout.open} should be called immediately from `init`. Default `true`. */
    autoOpen: boolean;
    /** Whether the Escape key should close the slideout. Default `true`. */
    closeOnEsc: boolean;
    /** Whether clicking the shade should close the slideout. Default `true`. */
    closeOnShadeClick: boolean;
    /**
     * The element that should regain focus when the slideout closes. Falls back
     * to `document.activeElement` at {@link Slideout.open} time when unset.
     */
    triggerElement: unknown;
}

/**
 * A slideout panel. Builds a container element around passed-in contents,
 * manages its shade, open/close transitions, and stacking against any other
 * open slideouts (via the class statics below).
 *
 * @fires open - When the slideout has opened.
 * @fires beforeClose - Just before {@link close} starts tearing things down.
 * @fires close - After the close transition finishes.
 *
 * @example
 * ```ts
 * import {Slideout} from '@/modules/slideout';
 *
 * const slideout = new Slideout('<p>Hello</p>', {closeOnEsc: true});
 * slideout.on('close', () => console.log('closed'));
 * ```
 */
export class Slideout extends Base<SlideoutSettings> {
    /** Default {@link SlideoutSettings}, merged with the per-instance overrides. */
    static defaults: SlideoutSettings = {
        containerElement: 'div',
        containerAttributes: {},
        autoOpen: true,
        closeOnEsc: true,
        closeOnShadeClick: true,
        triggerElement: null,
    };

    /** Container-id → instance map (also reachable as `Craft.Slideout.instances`), populated by {@link init}. */
    static instances: Record<string, Slideout> = {};

    /** Currently-open slideouts, most-recently-opened first (also reachable as `Craft.Slideout.openPanels`). */
    static openPanels: Slideout[] = [];

    /**
     * The CSS logical-inline-position property a panel's offscreen/onscreen
     * position is animated on — the opposite side of `Craft.slideoutPosition`,
     * because of the direction panels slide in from.
     */
    static positionProp(): string {
        return `inset-inline-${
            Craft.slideoutPosition === 'start' ? 'end' : 'start'
        }`;
    }

    /** The number of currently-open panels. */
    static totalPanels(): number {
        return Slideout.openPanels.length;
    }

    /** Register a newly-opened panel and reposition the stack. */
    static addPanel(panel: Slideout): void {
        Slideout.openPanels.unshift(panel);
        if (panel.useMobileStyles) {
            panel.$container.css('top', 0);
        } else {
            Slideout.updateStyles();
        }
    }

    /**
     * Unregister a closing panel and reposition the remaining stack. Must
     * mutate {@link openPanels} in place — `Craft.Slideout.openPanels` shares
     * the array reference (see `index.ts`), so a reassignment would strand it.
     */
    static removePanel(panel: Slideout): void {
        const index = Slideout.openPanels.indexOf(panel);
        if (index !== -1) {
            Slideout.openPanels.splice(index, 1);
        }
        if (panel.useMobileStyles) {
            panel.$container.css('top', '100vh');
        } else {
            panel.$container.css(Slideout.positionProp(), '100vw');
            Slideout.updateStyles();
        }
    }

    /** Reposition every open panel, and toggle the body's `no-scroll` class. */
    static updateStyles(): void {
        const totalPanels = Slideout.totalPanels();
        Slideout.openPanels.forEach((panel, i) => {
            panel.$container.css(
                Slideout.positionProp(),
                `${45 * ((totalPanels - i) / totalPanels)}vw`
            );
        });

        document.body.classList.toggle('no-scroll', totalPanels !== 0);
    }

    // --- Instance state (jQuery-typed; see the class docblock for why) --------

    $outerContainer: any = null;
    $container: any = null;
    $shade: any = null;
    /** Plain element, not a jQuery collection — see the class docblock. */
    $liveRegion: HTMLElement | null = null;
    $triggerElement: any = null;
    isOpen = false;
    isOpening = false;
    useMobileStyles: boolean | null = null;

    /**
     * @param contents - Anything jQuery's `.append()` accepts (an element,
     *   jQuery collection, HTML string, or array of those) to place inside the
     *   generated container.
     * @param settings - Optional settings overrides (see {@link SlideoutSettings}).
     */
    constructor(contents?: unknown, settings?: Partial<SlideoutSettings>) {
        super();
        if (new.target === Slideout) {
            this.init(contents, settings);
        }
    }

    /**
     * Build the container/shade and (by default) open it. Invoked from the
     * constructor only for the leaf class; subclasses call it themselves —
     * `super.init()` from TypeScript, or `this.base()` from a legacy
     * `.extend()` body (see the class docblock).
     */
    init(contents?: unknown, settings?: Partial<SlideoutSettings>): void {
        this.setSettings(settings, Slideout.defaults);

        this.$outerContainer = $('<div/>', {
            class: 'slideout-container cp-legacy hidden',
        });
        this.$container = $(
            `<${this.settings!.containerElement}/>`,
            this.settings!.containerAttributes
        )
            .attr('data-slideout', '')
            .addClass('slideout cp-legacy')
            .append(contents)
            .data('slideout', this)
            .appendTo(this.$outerContainer);

        containerSlideouts.set(this.$container[0], this);

        if (this.$container.attr('id')) {
            Slideout.instances[this.$container.attr('id')] = this;
        }

        addModalAttributes(this.$outerContainer[0]);

        Craft.trapFocusWithin(this.$container);

        // `appendChild` moves the one shared node, same as `appendTo` did.
        this.$liveRegion = sharedLiveRegion;
        this.$container[0].appendChild(this.$liveRegion);

        if (this.settings!.autoOpen) {
            this.open();
        }
    }

    open(): void {
        if (this.isOpen) {
            return;
        }

        this.setTriggerElement(
            this.settings!.triggerElement || document.activeElement
        );
        this._cancelTransitionListeners();

        const activePreview =
            Craft.Preview.getActive() || Craft.LivePreview.getActive();
        this.useMobileStyles = activePreview || Craft.useMobileStyles();

        this.$outerContainer.removeClass('so-mobile so-lp');
        this.$container.removeClass('so-mobile so-lp');

        if (activePreview) {
            this.$outerContainer.addClass('so-lp');
            this.$container.addClass('so-lp');
        } else if (this.useMobileStyles) {
            this.$container.addClass('so-mobile');
        }

        if (activePreview || !this.useMobileStyles) {
            if (!this.$shade) {
                this.$shade = $('<div class="slideout-shade"/>');

                if (this.settings!.closeOnShadeClick) {
                    this.addListener(this.$shade, 'click', (ev: any) => {
                        ev.stopPropagation();
                        this.close();
                    });
                }
            }

            // Keep the shade + container at the end of <body> so they get the
            // highest sub-z-indexes.
            this.$shade.appendTo(bod).show();
        } else if (this.$shade) {
            this.$shade.remove();
            this.$shade = null;
        }

        this.$outerContainer.appendTo(bod).removeClass('hidden');

        if (activePreview) {
            // keep the width equal to the edit pane width
            this.updateWidthsForPreviewPane(activePreview);
            let containerWidth = activePreview.$editorContainer.width();
            const resizeObserver = new ResizeObserver(() => {
                if (
                    this.isOpen &&
                    containerWidth !==
                        (containerWidth =
                            activePreview.$editorContainer.width())
                ) {
                    this.updateWidthsForPreviewPane(activePreview);
                }
            });
            resizeObserver.observe(activePreview.$editorContainer[0]);
            activePreview.on('beforeClose', () => {
                resizeObserver.disconnect();
            });
        }

        this.isOpening = true;

        if (this.useMobileStyles) {
            this.$container.css({
                top: '100vh',
                [Slideout.positionProp()]: '',
            });
        } else {
            this.$container.css({
                top: '',
                [Slideout.positionProp()]: '100vw',
            });
        }

        this._afterTransition(this.$container, () => {
            this.isOpening = false;
            this.setFocusWithin();
        });

        if (this.$shade) {
            // Force a reflow so the `so-visible` class transitions instead of
            // applying instantly.
            void this.$shade[0].offsetWidth;
            this.$shade.addClass('so-visible');
        }

        // Force a reflow before the position/top change above transitions.
        void this.$container[0].offsetWidth;
        Slideout.addPanel(this);

        this.enable();
        uiLayerManager().addLayer(this.$outerContainer[0]);
        hideBackgroundLayers();

        if (this.settings!.closeOnEsc) {
            uiLayerManager().registerShortcut(ESC_KEY, () => {
                this.close();
            });
        }

        this.isOpen = true;
        this.trigger('open');
    }

    setFocusWithin(): void {
        Craft.setFocusWithin(this.$container);
    }

    updateWidthsForPreviewPane(activePreview: any): void {
        const width = activePreview.$editorContainer.width() - 1;
        if (this.$shade) {
            this.$shade.width(width);
        }
        this.$outerContainer.css('width', `calc(${width}px - var(--m) * 2)`);
    }

    setTriggerElement(trigger: unknown): void {
        this.$triggerElement = $(trigger);
    }

    close(): void {
        if (!this.isOpen) {
            return;
        }

        this.trigger('beforeClose');
        this.disable();
        this.isOpen = false;

        this._cancelTransitionListeners();

        if (this.$shade) {
            this.$shade.removeClass('so-visible');
            this._afterTransition(this.$shade, () => {
                this.$shade.hide();
            });
        }

        Slideout.removePanel(this);
        uiLayerManager().removeLayer();
        resetBackgroundLayerVisibility();
        this._afterTransition(this.$container, () => {
            this.$outerContainer.addClass('hidden');
            this.trigger('close');
        });

        if (this.$triggerElement?.length) {
            let focusTarget = $(this.$triggerElement)[0]; // Ensure we convert from jQuery to DOM element

            // Check if target is still visible
            if (!focusTarget.checkVisibility()) {
                // If it's a disclosure, get the disclosure trigger instead
                const disclosure = focusTarget.closest('.menu--disclosure');
                if (disclosure) {
                    const disclosureId = disclosure.getAttribute('id');
                    focusTarget = document.querySelector(
                        `[aria-controls="${disclosureId}"]`
                    );
                } else {
                    focusTarget = null;
                }
            }

            if (focusTarget) {
                setTimeout(() => {
                    focusTarget.focus();
                }, 150);
            }
        }
    }

    /**
     * Performs the callback after the CSS transition has ended, or immediately
     * if the user prefers reduced motion.
     */
    _afterTransition($target: any, callback: () => void): void {
        if (prefersReducedMotion()) {
            callback();
        } else {
            $target.one('transitionend.slideout', callback);
        }
    }

    _cancelTransitionListeners(): void {
        if (this.$shade) {
            this.$shade.off('transitionend.slideout');
        }

        this.$container.off('transitionend.slideout');
    }

    /**
     * Tear the slideout down: remove the shade/container from the DOM, release
     * the {@link containerSlideouts} WeakMap entry, unregister from
     * {@link Slideout.instances}, then run the `Base` teardown (emits
     * `destroy`, removes tracked listeners).
     */
    override destroy(): void {
        if (this.$shade) {
            this.$shade.remove();
            this.$shade = null;
        }

        this.$outerContainer.remove();

        if (this.$container) {
            containerSlideouts.delete(this.$container[0]);
        }

        this.$outerContainer = null;
        this.$container = null;

        // Must delete in place — `Craft.Slideout.instances` shares this object
        // reference (see `index.ts`), so a reassignment would strand it.
        for (const key of Object.keys(Slideout.instances)) {
            if (Slideout.instances[key] === this) {
                delete Slideout.instances[key];
            }
        }

        super.destroy();
    }
}

export default Slideout;
