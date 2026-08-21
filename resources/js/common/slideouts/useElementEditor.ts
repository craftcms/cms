/**
 * Bridges `Craft.ElementEditor` onto a Vue slideout panel.
 *
 * Drafts, autosaving, provisional drafts, delta submission and tab error
 * indicators all live in the legacy editor. Reimplementing them in Vue before
 * the element-edit screen itself is ported would mean maintaining two of each,
 * so the panel does what `ElementEditorSlideout` does for the jQuery stack:
 * hands the editor the regions it needs — the form, the server-rendered
 * content and details columns, somewhere to park its spinner — and adapts
 * everything else through callbacks.
 *
 * The settings arrive as the `screen.elementEditorSettings` prop rather than
 * through `$(container).data()`. The jQuery path injects a script that looks
 * the container up by id, and that script runs while Vue still has the panel's
 * subtree detached from the document.
 */

import {ref, shallowRef, toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import type {SlideoutController} from './types';

/* eslint-disable @typescript-eslint/no-explicit-any -- the legacy CP globals
   are untyped, and typing them here would only describe one caller's slice. */

/** The parts of the shell the editor reads from and writes into. */
export interface ElementEditorRegions {
    /** The panel's form. `Craft.ElementEditor` throws on anything else. */
    form: MaybeRefOrGetter<HTMLFormElement | null>;
    content: MaybeRefOrGetter<HTMLElement | null>;
    details: MaybeRefOrGetter<HTMLElement | null>;
    /** Host for the save spinner and draft status icon. */
    toolbar: MaybeRefOrGetter<HTMLElement | null>;
    tabs: MaybeRefOrGetter<HTMLElement | null>;
}

export interface UseElementEditorOptions {
    /** `screen.elementEditorSettings`, absent on every other kind of screen. */
    settings: () => Record<string, any> | null | undefined;
    /** The panel's input namespace, which the editor sends with every save. */
    namespace: () => string | null | undefined;
    regions: ElementEditorRegions;
    slideout: SlideoutController | null;
    onSaved: (response: any) => void;
    /**
     * Called after each autosaved draft, while the panel stays open. Fires as
     * often as the editor decides to save — debounce anything expensive.
     */
    onDraftSaved: (response: any) => void;
    /**
     * Called on a failed save for whatever the shell owns — the error toast, and
     * rethrowing anything that isn't a validation failure. Field-level messages,
     * tab indicators and the server's error summary are drawn into the screen's
     * own HTML by the bridge.
     */
    onError: (error: any) => void;
}

export interface ElementEditorBridge {
    /** True once the editor owns the screen, so the shell defers saving to it. */
    active: Ref<boolean>;
    /** Revisions and other read-only screens: no save button, "Close" not "Cancel". */
    isStatic: Ref<boolean>;
    /**
     * True when edits are being persisted to a draft as they're made, so closing
     * the panel doesn't lose them and needn't be confirmed.
     */
    autosaves: Ref<boolean>;
    /** A label the editor wants on the cancel button, or `null` for the default. */
    cancelLabel: Ref<string | null>;
    saving: Ref<boolean>;
    /**
     * Build the editor. Call once the screen's server-rendered HTML is actually
     * in the document — the editor snapshots the form to detect changes, and a
     * snapshot of an empty form would read every field as an edit.
     */
    start: () => void;
    submit: (event: Event) => Promise<void>;
    destroy: () => void;
}

const craft = (): any => (window as any).Craft;
const jquery = (): any => (window as any).jQuery;

export function useElementEditor(
    options: UseElementEditorOptions
): ElementEditorBridge {
    const editor = shallowRef<any>(null);
    const active = ref(false);
    const isStatic = ref(false);
    const autosaves = ref(false);
    const cancelLabel = ref<string | null>(null);
    const saving = ref(false);

    let tabManager: any = null;
    /** Fields decorated by the last failed save, so the next one can undo it. */
    const fieldsWithErrors: any[] = [];

    const $ = (el: HTMLElement | null | undefined) => jquery()(el ?? []);

    function tabsRoot(): HTMLElement | null {
        return (
            toValue(options.regions.tabs)?.querySelector<HTMLElement>(
                '.pane-tabs'
            ) ?? null
        );
    }

    function destroyTabManager(): void {
        tabManager?.destroy();
        tabManager = null;
    }

    function initTabManager(): void {
        const root = tabsRoot();

        if (!root) {
            return;
        }

        tabManager = new (craft().Tabs)(root);

        // The panes are plain server HTML, so switching tabs means toggling their
        // `hidden` class by hand — the same wiring `CpScreenSlideout` does.
        tabManager.on('deselectTab', (ev: any) => {
            $(document.querySelector(ev.$tab.attr('href'))).addClass('hidden');
        });
        tabManager.on('selectTab', (ev: any) => {
            $(document.querySelector(ev.$tab.attr('href'))).removeClass(
                'hidden'
            );
            jquery()(window).trigger('resize');
        });
    }

    /**
     * Swap in freshly rendered tabs after an autosave, which is how tab-level
     * error indicators appear and clear.
     *
     * Safe to reach past Vue here: the initial tabs were appended imperatively
     * by `HtmlFragmentRenderer` from a prop that doesn't change over the panel's
     * life, so nothing will re-patch what we replace.
     */
    function setTabs(html: string | null): void {
        destroyTabManager();

        const host = toValue(options.regions.tabs);

        if (!host) {
            return;
        }

        const current = tabsRoot();

        if (!html) {
            current?.remove();

            return;
        }

        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const next = template.content.firstElementChild;

        if (!next) {
            current?.remove();

            return;
        }

        if (current) {
            current.replaceWith(next);
        } else {
            host.appendChild(next);
        }

        initTabManager();
    }

    /**
     * Paint a failed save onto the server-rendered HTML: inline messages on the
     * offending fields, an alert on each tab that contains one, and the server's
     * own error summary split across those tabs.
     *
     * A port of `CpScreenSlideout`'s `showErrors()`/`showErrorSummary()`. The
     * markup it decorates is the same server HTML, so the same jQuery does the
     * job — the only difference is where the regions come from.
     */
    function showErrors(errors: Record<string, unknown>): void {
        const Craft = craft();
        const form = toValue(options.regions.form);

        if (!form) {
            return;
        }

        const indicator =
            '<span data-icon="alert"><span class="visually-hidden">' +
            Craft.t('app', 'This tab contains errors') +
            '</span></span>';
        const tabMenu = tabManager?.menu ?? [];

        Object.entries(errors).forEach(([name, fieldErrors]) => {
            const $field = $(form).find(`[data-error-key="${name}"]`);

            if (!$field.length) {
                return;
            }

            Craft.ui.addErrorsToField($field, fieldErrors);
            fieldsWithErrors.push($field);

            const anchors = Craft.ui.findTabAnchorForField($field, $(form));

            if (!anchors.length || !tabManager) {
                return;
            }

            if (!tabManager.$menuBtn.hasClass('error')) {
                tabManager.$menuBtn
                    .addClass('error')
                    .append('<span data-icon="alert"></span>');
            }

            for (let i = 0; i < anchors.length; i++) {
                const $anchor = jquery()(anchors[i]);

                if ($anchor.hasClass('error')) {
                    continue;
                }

                $anchor.addClass('error').find('.tab-label').append(indicator);

                const $item = tabMenu.length
                    ? tabMenu.find(`[data-id=${$anchor.data('id')}]`)
                    : null;

                if ($item?.length && !$item.hasClass('error')) {
                    $item.addClass('error').append(indicator);
                }
            }
        });
    }

    function showErrorSummary(html: string, errorCount: number): void {
        const Craft = craft();
        const $ = jquery();
        const content = toValue(options.regions.content);

        if (!content) {
            return;
        }

        const $content = $(content);
        Craft.ui.clearErrorSummary($content);

        // One tab: the summary applies to everything on screen, so show it whole.
        if (!tabManager) {
            $(html).prependTo($content);
            Craft.ui.setFocusOnErrorSummary($content);

            return;
        }

        const $tabsWithErrors = tabManager.$tabs.filter('.error');

        tabManager.$tabs.each((_: number, tab: HTMLElement) => {
            const $pane = $content.find(`#${$(tab).data('id')}`);

            if (!$pane.length) {
                return;
            }

            const paneUid = $pane.data('layout-tab');
            const $summary = $(html);
            let count = $summary.find('ul.errors li').length;

            // Errors with no tab of their own — cross-field validation — stay in
            // every summary; the rest only appear on the tab they belong to.
            $summary
                .find('ul.errors li')
                .each((_j: number, error: HTMLElement) => {
                    const uid = $(error).find('a').data('layout-tab');

                    if (typeof uid !== 'undefined' && uid !== paneUid) {
                        $(error).remove();
                        count--;
                    }
                });

            $summary.find('h2').html(
                count > 0
                    ? Craft.t(
                          'app',
                          'Found {num, number} {num, plural, =1{error} other{errors}} in this tab.',
                          {num: count}
                      ) +
                          ($tabsWithErrors.length > 1
                              ? `<span class="visually-hidden">${Craft.t(
                                    'app',
                                    '{total, number} {total, plural, =1{error} other{errors}} found in {num, number} {num, plural, =1{tab} other{tabs}}.',
                                    {
                                        total: errorCount,
                                        num: $tabsWithErrors.length,
                                    }
                                )}</span>`
                              : '')
                    : Craft.t('app', 'Found errors in other tabs.')
            );

            $summary.prependTo($pane);
            // Also what makes deep-linking to an error work.
            Craft.ui.setFocusOnErrorSummary($pane);
        });
    }

    function clearErrors(): void {
        while (fieldsWithErrors.length) {
            craft().ui.clearErrorsFromField(fieldsWithErrors.pop());
        }

        const content = toValue(options.regions.content);

        if (content) {
            craft().ui.clearErrorSummary(jquery()(content));
        }

        tabManager?.$tabs
            .removeClass('error')
            .find('.tab-label [data-icon="alert"]')
            .remove();
    }

    function handleSubmitError(error: any): void {
        clearErrors();
        options.onError(error);

        const data = error?.response?.data;

        if (!data) {
            return;
        }

        if (data.errors) {
            showErrors(data.errors);
        }

        if (data.errorSummary) {
            showErrorSummary(
                data.errorSummary,
                Object.keys(data.errors ?? {}).length
            );
        }
    }

    function start(): void {
        if (active.value) {
            return;
        }

        const settings = options.settings();
        const form = toValue(options.regions.form);
        const Craft = craft();

        if (!settings || !form || !Craft?.ElementEditor || !jquery()) {
            return;
        }

        initTabManager();

        // `Craft.ElementEditor` reads this to decide whether to bind its own
        // submit handler — the shell owns submitting here — and to drive the
        // panel when a draft is discarded or another tab saves the same element.
        $(form).data('slideout', {
            reload: () => options.slideout?.reload(),
            close: () => options.slideout?.close({force: true}),
            // Written to before a reload, to carry a changed element or draft id
            // across. Nothing to carry here: the panel refetches the URL it was
            // opened with, which addresses the canonical element.
            settings: {},
            // A stand-in for the legacy cancel button. Once a provisional draft
            // exists the editor renames it to "Close" — the changes are already
            // saved, so there's nothing left to cancel — by calling `.text()` on it.
            // Routing that through a ref keeps Vue the only thing writing to the
            // real button.
            $cancelBtn: {
                text: (label: string) => {
                    cancelLabel.value = label;
                },
            },
        });

        editor.value = new Craft.ElementEditor(form, {
            ...settings,
            namespace: options.namespace() ?? null,
            $contentContainer: $(toValue(options.regions.content)),
            $sidebar: $(toValue(options.regions.details)),
            $spinnerContainer: $(toValue(options.regions.toolbar)),
            $actionBtn: $(null),
            updateTabs: (tabs: string | null) => setTabs(tabs),
            getTabManager: () => tabManager,
            handleSubmitResponse: (response: any) => {
                clearErrors();
                options.onSaved(response);
            },
            handleSubmitError,
        });

        editor.value.on('beforeSubmit', () => (saving.value = true));
        editor.value.on('afterSubmit', () => (saving.value = false));
        editor.value.on('afterSaveDraft', (event: any) =>
            options.onDraftSaved(event?.response)
        );

        isStatic.value = Boolean(editor.value.settings.isStatic);
        autosaves.value = Boolean(
            editor.value.enableAutosave && editor.value.settings.canCreateDrafts
        );
        active.value = true;
    }

    async function submit(event: Event): Promise<void> {
        const instance = editor.value;

        if (!instance) {
            return;
        }

        // A save from anywhere other than the form itself hasn't been through the
        // editor's change tracking yet; saving the draft first keeps the tab error
        // indicators in sync with what's about to be submitted.
        if (event.type !== 'submit' && instance.settings.canCreateDrafts) {
            await instance.saveDraft();
        }

        saving.value = true;

        try {
            await instance.handleSubmit(event);
        } finally {
            saving.value = false;
        }
    }

    function destroy(): void {
        destroyTabManager();
        editor.value?.destroy();
        editor.value = null;
        active.value = false;
        autosaves.value = false;
        cancelLabel.value = null;
    }

    return {
        active,
        isStatic,
        autosaves,
        cancelLabel,
        saving,
        start,
        submit,
        destroy,
    };
}
