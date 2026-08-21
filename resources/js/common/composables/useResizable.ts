import {
    computed,
    nextTick,
    onScopeDispose,
    ref,
    shallowRef,
    toValue,
    watch,
} from 'vue';
import type {ComputedRef, MaybeRefOrGetter, Ref} from 'vue';
import {useElementSize} from '@vueuse/core';
import {BaseDrag, X_AXIS} from '@craftcms/garnish';
import {useLocalStorage} from '@/common/composables/useStorage';

/**
 * Generic inline-axis resizing for a CP layout column — the details sidebar,
 * the secondary nav, or anything else that occupies a grid track.
 *
 * The drag itself is Garnish's `BaseDrag` (the same primitive `Modal` uses for
 * its resize handle), axis-locked to X and driven by a handle element the
 * consumer positions on one edge of the column. This composable owns the
 * arithmetic — measuring, clamping, direction, keyboard steps — and hands back
 * a width plus the bookkeeping needed to render an accessible handle.
 *
 * Width lives in one place: {@link UseResizableReturn.width}, in px, or `null`
 * meaning "no explicit width, let CSS decide". Pass `cssVariable` and bind
 * {@link UseResizableReturn.style} to whichever element owns the grid track;
 * `null` yields an empty style object, so the stylesheet's default survives.
 */

/** Which edge of the resized element the handle sits on. */
export type ResizeEdge = 'inline-start' | 'inline-end';

/** What drove a width change. */
export type ResizeSource = 'pointer' | 'keyboard' | 'reset' | 'programmatic';

/** Payload handed to every {@link UseResizableOptions} callback. */
export interface ResizeDetail {
    /** The clamped width now in effect, in px. */
    width: number;
    /** The width the interaction asked for, before clamping, in px. */
    requestedWidth: number;
    /**
     * `requestedWidth - width` — how far past a bound the interaction pushed.
     * Negative past the min, positive past the max, `0` within range. Watch this
     * from `onResize` to implement "drag far enough past the min and collapse".
     */
    overshoot: number;
    atMin: boolean;
    atMax: boolean;
    source: ResizeSource;
}

export interface UseResizableOptions {
    /** The element being resized. Measured on drag start and for `aria-valuenow`. */
    target: MaybeRefOrGetter<HTMLElement | null | undefined>;
    /**
     * Which edge the handle sits on, which is what decides the drag direction:
     * with `inline-start` the column grows as the pointer moves toward the start
     * of the line, with `inline-end` as it moves toward the end. Writing-mode
     * aware — the physical direction flips under RTL. Default `inline-start`.
     */
    edge?: MaybeRefOrGetter<ResizeEdge>;
    /** Smallest allowed width, in px. */
    minWidth: MaybeRefOrGetter<number>;
    /** Largest allowed width, in px. */
    maxWidth: MaybeRefOrGetter<number>;
    /**
     * Width that {@link UseResizableReturn.reset} returns to, in px. `null` (the
     * default) clears the explicit width and hands the column back to CSS.
     */
    defaultWidth?: MaybeRefOrGetter<number | null>;
    /** Arrow-key increment, in px. Default `16`. */
    step?: MaybeRefOrGetter<number>;
    /** Shift + arrow-key increment, in px. Default `64`. */
    largeStep?: MaybeRefOrGetter<number>;
    /** Persist the width under this `useLocalStorage` key. Omit to keep it per-page. */
    storageKey?: string;
    /** Custom property {@link UseResizableReturn.style} writes the width into. */
    cssVariable?: string;

    onResizeStart?: (detail: ResizeDetail) => void;
    /** Fires on every width change, including keyboard steps and resets. */
    onResize?: (detail: ResizeDetail) => void;
    onResizeEnd?: (detail: ResizeDetail) => void;
    /** Fires when the width first pins to the min — not repeatedly while it stays there. */
    onMinReached?: (detail: ResizeDetail) => void;
    /** Fires when the width first pins to the max. */
    onMaxReached?: (detail: ResizeDetail) => void;
    onReset?: (detail: ResizeDetail) => void;
}

export interface UseResizableReturn {
    /** Explicit width in px, or `null` when CSS is in charge. */
    width: Ref<number | null>;
    /** The width to report to assistive tech: the explicit width, else the measured one. */
    valueNow: ComputedRef<number>;
    minWidth: ComputedRef<number>;
    maxWidth: ComputedRef<number>;
    /** Whether a pointer drag is in progress. */
    isResizing: Ref<boolean>;
    atMin: ComputedRef<boolean>;
    atMax: ComputedRef<boolean>;
    /** `{[cssVariable]: '<width>px'}`, or `{}` when the width is CSS-driven. */
    style: ComputedRef<Record<string, string>>;
    /** Template ref callback for the handle element. */
    setHandle: (el: HTMLElement | null) => void;
    /** Set the width directly. `null` hands the column back to CSS. */
    setWidth: (
        width: number | null,
        options?: {source?: ResizeSource; clamp?: boolean}
    ) => void;
    /** Grow or shrink by `delta` px along the physical X axis (RTL-aware). */
    nudge: (deltaX: number) => void;
    /** Return to `defaultWidth`. Wired to the handle's double-click and Enter key. */
    reset: () => void;
    /** Keydown handler implementing the WAI-ARIA window splitter keys. */
    onKeydown: (ev: KeyboardEvent) => void;
}

/** Class put on `<html>` for the duration of a drag; see `cp.css`. */
const RESIZING_CLASS = 'is-resizing-inline';

export function useResizable(options: UseResizableOptions): UseResizableReturn {
    // Persisted widths survive Inertia visits; unpersisted ones live and die with
    // the layout. The cast reconciles `RemovableRef` with the plain `Ref` we hand
    // back — both accept `null`, and we never write `undefined`.
    const width = (
        options.storageKey
            ? useLocalStorage<number | null>(options.storageKey, null)
            : ref<number | null>(null)
    ) as Ref<number | null>;

    const isResizing = ref(false);
    const handle = shallowRef<HTMLElement | null>(null);

    const minWidth = computed(() => toValue(options.minWidth));
    const maxWidth = computed(() => toValue(options.maxWidth));

    // Keeps `aria-valuenow` honest while the width is CSS-driven, and after a
    // viewport change moves a percentage-based default track.
    const {width: measuredWidth} = useElementSize(() =>
        toValue(options.target)
    );

    // What's on screen wins over what's stored. A stylesheet is free to cap the
    // track below the requested width — a `min()` guarding against overflow, say
    // — and reporting the request instead of the result would lie to assistive
    // tech and make the first arrow key jump.
    const valueNow = computed(() =>
        Math.round(measuredWidth.value || (width.value ?? 0))
    );
    const atMin = computed(() => valueNow.value <= minWidth.value);
    const atMax = computed(() => valueNow.value >= maxWidth.value);

    const style = computed<Record<string, string>>(() => {
        if (!options.cssVariable || width.value === null) {
            return {};
        }
        return {[options.cssVariable]: `${width.value}px`};
    });

    // Edge-trigger state for onMinReached/onMaxReached, so a drag that sits
    // pinned at a bound reports once rather than every frame.
    let wasAtMin = false;
    let wasAtMax = false;
    let lastDetail: ResizeDetail | null = null;

    function measure(): number {
        const el = toValue(options.target);
        return el ? Math.round(el.getBoundingClientRect().width) : 0;
    }

    /** The rendered width, falling back to the stored one when unmeasurable. */
    function currentWidth(): number {
        return measure() || (width.value ?? 0);
    }

    /**
     * How a positive physical-X delta maps onto width. A handle on the column's
     * inline-start edge grows it as the pointer moves left in LTR, and RTL
     * mirrors the whole thing.
     */
    function growSign(): number {
        const edge = toValue(options.edge) ?? 'inline-start';
        const el = toValue(options.target);
        const rtl = el ? getComputedStyle(el).direction === 'rtl' : false;
        return (edge === 'inline-end' ? 1 : -1) * (rtl ? -1 : 1);
    }

    function buildDetail(
        requestedWidth: number,
        clamped: number,
        source: ResizeSource
    ): ResizeDetail {
        return {
            width: clamped,
            requestedWidth,
            overshoot: requestedWidth - clamped,
            atMin: clamped <= minWidth.value,
            atMax: clamped >= maxWidth.value,
            source,
        };
    }

    function applyWidth(requested: number, source: ResizeSource): ResizeDetail {
        const clamped = Math.min(
            Math.max(Math.round(requested), minWidth.value),
            maxWidth.value
        );
        width.value = clamped;

        const detail = buildDetail(Math.round(requested), clamped, source);
        lastDetail = detail;

        if (detail.atMin && !wasAtMin) {
            options.onMinReached?.(detail);
        }
        if (detail.atMax && !wasAtMax) {
            options.onMaxReached?.(detail);
        }
        wasAtMin = detail.atMin;
        wasAtMax = detail.atMax;

        options.onResize?.(detail);

        return detail;
    }

    function setWidth(
        next: number | null,
        {
            source = 'programmatic',
            clamp = true,
        }: {
            source?: ResizeSource;
            clamp?: boolean;
        } = {}
    ): void {
        if (next === null) {
            width.value = null;
            wasAtMin = false;
            wasAtMax = false;
            return;
        }

        if (!clamp) {
            // Escape hatch for consumers driving their own state off the bound
            // callbacks — collapsing to 0, say, which the min would otherwise block.
            width.value = Math.round(next);
            lastDetail = buildDetail(
                Math.round(next),
                Math.round(next),
                source
            );
            options.onResize?.(lastDetail);
            return;
        }

        applyWidth(next, source);
    }

    function nudge(deltaX: number): void {
        applyWidth(currentWidth() + growSign() * deltaX, 'keyboard');
    }

    function reset(): void {
        const defaultWidth = toValue(options.defaultWidth) ?? null;

        if (defaultWidth === null) {
            width.value = null;
            wasAtMin = false;
            wasAtMax = false;
            // The measurement is only meaningful once CSS has retaken the track.
            void nextTick(() => {
                const measured = measure();
                lastDetail = buildDetail(measured, measured, 'reset');
                options.onReset?.(lastDetail);
            });
            return;
        }

        const detail = applyWidth(defaultWidth, 'reset');
        options.onReset?.(detail);
    }

    function onKeydown(ev: KeyboardEvent): void {
        const step = ev.shiftKey
            ? (toValue(options.largeStep) ?? 64)
            : (toValue(options.step) ?? 16);

        switch (ev.key) {
            case 'ArrowLeft':
                nudge(-step);
                break;
            case 'ArrowRight':
                nudge(step);
                break;
            case 'Home':
                applyWidth(minWidth.value, 'keyboard');
                break;
            case 'End':
                applyWidth(maxWidth.value, 'keyboard');
                break;
            case 'Enter':
                // The keyboard counterpart to double-clicking the handle.
                reset();
                break;
            default:
                return;
        }

        ev.preventDefault();
    }

    // --- Pointer dragging -----------------------------------------------------

    let dragger: BaseDrag | null = null;
    let startWidth = 0;
    let startDist = 0;
    let sign = -1;

    function setHandle(el: HTMLElement | null): void {
        handle.value = el;
    }

    function endResize(): void {
        isResizing.value = false;
        document.documentElement.classList.remove(RESIZING_CLASS);
    }

    watch(handle, (el) => {
        dragger?.destroy();
        dragger = null;

        if (!el) {
            return;
        }

        dragger = new BaseDrag(el, {
            axis: X_AXIS,
            // The default selector list would swallow pointer-downs on the handle's
            // own children; the handle *is* the control here.
            ignoreHandleSelector: null,
            onBeforeDragStart: () => {
                // Sync, unlike onDragStart, so the width we measure is the one that
                // was on screen when the drag threshold was crossed. Remembering the
                // distance already travelled keeps the column from jumping by it.
                startWidth = measure();
                startDist = dragger?.mouseDistX ?? 0;
                sign = growSign();
                wasAtMin = false;
                wasAtMax = false;

                isResizing.value = true;
                document.documentElement.classList.add(RESIZING_CLASS);

                options.onResizeStart?.(
                    buildDetail(startWidth, startWidth, 'pointer')
                );
            },
            onDrag: () => {
                const dist = (dragger?.mouseDistX ?? 0) - startDist;
                applyWidth(startWidth + sign * dist, 'pointer');
            },
            onDragStop: () => {
                endResize();
                options.onResizeEnd?.(
                    lastDetail ??
                        buildDetail(currentWidth(), currentWidth(), 'pointer')
                );
            },
        });
    });

    onScopeDispose(() => {
        dragger?.destroy();
        dragger = null;
        endResize();
    });

    return {
        width,
        valueNow,
        minWidth,
        maxWidth,
        isResizing,
        atMin,
        atMax,
        style,
        setHandle,
        setWidth,
        nudge,
        reset,
        onKeydown,
    };
}
