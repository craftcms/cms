import {Base, type GarnishEvent} from '@craftcms/garnish';
import {serializeFormInputs} from '@craftcms/ui';
import {resolveElement, type ElementArg} from '@/common/utils/dom';

/** Called with the previous serialized form data when the form changes. */
type FormObserverCallback = (formData: string | null) => void;

/**
 * FormObserver — a port of `Craft.FormObserver` onto `@craftcms/garnish` `Base`.
 * Watches a form (or any container of named inputs) and invokes a callback when
 * its serialized value actually changes, debounced and slowed while the user is
 * actively typing. Backs draft autosave in `Craft.ElementEditor`.
 *
 * jQuery-free: `serializeFormInputs` (from `@craftcms/ui`) replaces jQuery's
 * `.serialize()`, and change detection rides on native `MutationObserver` +
 * `Base.addListener`. The instance is created imperatively
 * (`new Craft.FormObserver(container, callback)`), so there's no element/tag —
 * the class is exposed on `window.Craft` for the still-legacy callers.
 */
export class FormObserver extends Base {
    container: HTMLElement | null = null;

    #callback: FormObserverCallback | null = null;
    #pauseLevel = 0;
    #timeout: ReturnType<typeof setTimeout> | null = null;
    #recentKeypress = false;
    #formData: string | null = null;
    #mutationObserver: MutationObserver | null = null;

    get isActive(): boolean {
        return this.#pauseLevel === 0;
    }

    constructor(container?: ElementArg, callback?: FormObserverCallback) {
        super();
        if (new.target === FormObserver) {
            this.init(container ?? null, callback ?? (() => {}));
        }
    }

    init(container: ElementArg, callback: FormObserverCallback): void {
        this.container = resolveElement(container);
        if (!this.container) {
            return;
        }
        this.#callback = callback;
        this.#serialize();

        this.addListener(
            this.container,
            'change,input,keypress,keyup',
            (ev: GarnishEvent) => {
                if (this.isActive) {
                    // slow down when actively typing
                    if (['keypress', 'keyup'].includes(ev.type)) {
                        this.#recentKeypress = true;
                    }
                    this.#checkFormAfterDelay();
                }
            }
        );

        this.#mutationObserver = new MutationObserver((records) => {
            for (const record of records) {
                if (this.isActive && this.#formChanged(record)) {
                    this.#checkFormAfterDelay();
                }

                for (const node of record.addedNodes) {
                    if (node instanceof Element) {
                        this.#initSelectizeInputs(node);
                    }
                }

                if (
                    record.attributeName === 'class' &&
                    record.target instanceof Element &&
                    record.target.classList.contains('selectized')
                ) {
                    this.#initSelectizeInput(record.target);
                }
            }
        });

        this.#mutationObserver.observe(this.container, {
            childList: true,
            subtree: true,
            characterData: true,
            attributeFilter: ['name', 'value', 'disabled', 'class'],
        });

        this.#initSelectizeInputs(this.container);
    }

    #formChanged(record: MutationRecord): boolean {
        const target = record.target;
        switch (record.type) {
            case 'childList':
                return (
                    // was this for the text node of a <textarea>?
                    (target.nodeName === 'TEXTAREA' &&
                        (target as Element).hasAttribute('name')) ||
                    // maybe a `[name]` node was added/removed
                    this.#hasNamedNodes(record.addedNodes) ||
                    this.#hasNamedNodes(record.removedNodes)
                );
            case 'attributes': {
                const el = target as HTMLInputElement;
                switch (record.attributeName) {
                    case 'name':
                        // only matters if the element isn't disabled
                        return !el.disabled;
                    case 'value':
                        // only matters if the element has a name attribute and isn't disabled
                        return el.hasAttribute('name') && !el.disabled;
                    case 'disabled':
                        // only matters if the element has a name attribute
                        return el.hasAttribute('name');
                }
                // falls through — matches the legacy switch (e.g. a `class` change)
            }
            // oxlint-disable-next-line no-fallthrough
            case 'characterData':
                // maybe a <textarea> change
                return (
                    target.parentNode instanceof Element &&
                    target.parentNode.hasAttribute('name')
                );
            default:
                return false;
        }
    }

    #initSelectizeInputs(container: Element): void {
        // we're now using selectize select_on_focus plugin which clears the dropdown's value on dropdown open;
        // that triggers a change event which triggers saving a draft and causes conditional fields/tabs to misbehave;
        // because of that, we are now emitting selectize dropdown open and close events;
        // we pause listening for changes on dropdown open (it happens before the focus event, so before the value is cleared)
        // and we resume on dropdown close to register the change in value (if one actually occurred);
        if (container.classList.contains('selectized')) {
            this.#initSelectizeInput(container);
        } else {
            for (const input of Array.from(
                container.querySelectorAll('.selectized')
            )) {
                this.#initSelectizeInput(input);
            }
        }
    }

    #initSelectizeInput(input: Element): void {
        // just in case the element was detached and re-inserted into the DOM
        this.removeAllListeners(input);
        this.addListener(input, 'selectizedropdownopen', () => {
            this.pause();
        });
        this.addListener(input, 'selectizedropdownclose', () => {
            setTimeout(() => {
                this.resume();
            }, 100);
        });
    }

    #hasNamedNodes(nodes: NodeList): boolean {
        for (const node of Array.from(nodes)) {
            if (
                node instanceof Element &&
                (node.hasAttribute('name') ||
                    node.querySelectorAll('[name]').length)
            ) {
                return true;
            }
        }
        return false;
    }

    #checkFormAfterDelay(): void {
        if (this.#timeout) {
            clearTimeout(this.#timeout);
        }
        this.#timeout = setTimeout(
            () => {
                this.checkForm();
            },
            this.#recentKeypress ? 1000 : 100
        );
    }

    checkForm(): void {
        if (this.#timeout) {
            clearTimeout(this.#timeout);
        }
        this.#recentKeypress = false;
        // `#serialize()` updates `#formData` to the new value and returns it, so
        // this compares the previous serialization against the fresh one and hands
        // the callback the new data — matching the legacy read-then-serialize order.
        if (this.#formData !== this.#serialize()) {
            this.#callback?.(this.#formData);
        }
    }

    #serialize(): string {
        this.#formData = this.container
            ? serializeFormInputs(this.container)
            : '';
        return this.#formData;
    }

    pause(): void {
        this.#pauseLevel++;
    }

    resume(): void {
        if (this.#pauseLevel === 0) {
            throw 'Craft.FormObserver::resume() should only be called after pause().';
        }

        // Only actually resume operation if this has been called the same
        // number of times that pause() was called
        this.#pauseLevel--;

        if (this.isActive) {
            this.checkForm();
        }
    }

    override destroy(): void {
        if (this.#timeout) {
            clearTimeout(this.#timeout);
        }
        this.#mutationObserver?.disconnect();
        this.#mutationObserver = null;
        super.destroy();
    }
}
