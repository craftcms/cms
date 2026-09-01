/**
 * CpScreenSlideout — a {@link Slideout} that loads a CP screen from a
 * controller action, with header/tab/toolbar chrome, an optional details
 * sidebar, a Cancel/Save footer, and delta-aware form submission. See the
 * module README for usage.
 *
 * The same structural constraints as {@link Slideout} apply here:
 *
 * - **A legacy subclass exists.** `Craft.ElementEditorSlideout` subclasses
 *   the `Craft.CpScreenSlideout` global (= `compatify(CpScreenSlideout)`,
 *   see `index.ts`) via `Garnish.Base.extend()`, calling
 *   `this.base('elements/edit', settings)` from its own `init`. So
 *   {@link init} is a real public method, invoked from the constructor only
 *   for the leaf class (the `new.target === CpScreenSlideout` guard below —
 *   the bare `super()` doesn't double-init, since `Slideout`'s own guard is
 *   false here). Its signature (`action, settings`) intentionally differs
 *   from {@link Slideout.init}'s (`contents, settings`): it builds the
 *   screen chrome, then hands the assembled contents up via `super.init()`.
 * - **The `$`-prefixed members and jQuery data entries are public API**:
 *   `$header`, `$toolbar`, `$content`, `$sidebar`, `$footer`, `$saveBtn`,
 *   etc., plus `$container.data('cpScreen', this)` and the submit-flow data
 *   entries (`delta-names`, `initial-delta-values`, `initialSerializedValue`,
 *   `serializer`) that `Craft.ElementEditor` reads and writes.
 */

import {Garnish, ESC_KEY, S_KEY, isMobileBrowser} from '@craftcms/garnish';
import {installCpApp} from '@/bootstrap/cp-app';
import {resolveInertiaPage} from '@/bootstrap/inertia-pages';
import {createApp, type App} from 'vue';
import {Slideout, uiLayerManager, type SlideoutSettings} from './slideout';
import type {FormValues} from '@/modules/forms/types';
import type {AxiosRequestConfig} from 'axios';

declare const Craft: any;
declare const $: any;
declare const axios: any;

/**
 * Settings accepted by {@link CpScreenSlideout}, layered on top of
 * {@link SlideoutSettings}. `params`/`requestOptions` feed
 * {@link CpScreenSlideout.load}; `onLoad`/`onSubmit` are callbacks fired
 * alongside the equivalent events.
 */
export interface CpScreenSlideoutSettings extends SlideoutSettings {
  params: FormValues;
  requestOptions: AxiosRequestConfig;
  showHeader: boolean | null;
  closeOnSubmit: boolean;
  // Optional (despite `defaults` always providing fallbacks) so the
  // `if (this.settings!.onLoad)`/`onSubmit` guards below stay meaningful
  // checks rather than tautologies TS would flag as dead code.
  onLoad?: () => void;
  onSubmit?: (ev: any) => void;
}

/**
 * A CP screen slideout: fetches its content from a controller action and
 * renders it inside a {@link Slideout}, with header/toolbar/tabs, an
 * optional sidebar, a footer with cancel/save buttons, and delta-aware
 * submit handling.
 */
export class CpScreenSlideout extends Slideout {
  /**
   * Default {@link CpScreenSlideoutSettings}. `autoOpen`/`triggerElement`
   * restate {@link Slideout.defaults} values only because TypeScript's
   * static-side override check requires this field to be fully typed.
   */
  static override defaults: CpScreenSlideoutSettings = {
    containerElement: 'form',
    containerAttributes: {},
    autoOpen: true,
    triggerElement: null,
    params: {},
    requestOptions: {},
    showHeader: null,
    closeOnEsc: false,
    closeOnShadeClick: false,
    closeOnSubmit: true,
    onLoad: () => {},
    onSubmit: () => {},
  };

  // Narrow the inherited `settings` field to this class's richer settings
  // shape; assignment-compatible with `SlideoutSettings | null` since
  // `CpScreenSlideoutSettings extends SlideoutSettings`. A plain (not
  // `declare`d) override — `declare override` isn't a legal combination —
  // but harmless at runtime: this initializer merely re-asserts `null` a
  // moment after `Base`'s own field initializer already set it, strictly
  // before `init()` (which is what actually resolves `settings`) ever runs.
  override settings: CpScreenSlideoutSettings | null = null;

  action: string | null = null;
  namespace: string | null = null;
  inertiaApp: App | null = null;

  showingLoadSpinner = false;
  hasTabs = false;
  hasCpLink = false;
  hasSidebar = false;

  // --- jQuery public surface (see class docblock for why) -------------------
  $header: any = null;
  $toolbar: any = null;
  $tabContainer: any = null;
  $extraToolbarItems: any = null;
  $loadSpinner: any = null;
  $actionBtn: any = null;
  $editLink: any = null;
  $sidebarBtn: any = null;

  $body: any = null;
  $content: any = null;

  $sidebar: any = null;

  $footer: any = null;
  $noticeContainer: any = null;
  $cancelBtn: any = null;
  $saveBtn: any = null;

  tabManager: any = null;
  showingSidebar = false;

  cancelToken: any = null;
  ignoreFailedRequest = false;
  fieldsWithErrors: any[] = [];

  /** Whether the slideout is wide enough to show the sidebar alongside the content. */
  get showExpandedView(): boolean {
    return this.$container.width() > 700;
  }

  get sidebarIsOverlapping(): boolean {
    return this.showingSidebar && this.$sidebar.css('position') === 'absolute';
  }

  /**
   * @param action - The controller action or URL to fetch the screen's content from.
   * @param settings - Optional settings overrides (see {@link CpScreenSlideoutSettings}).
   */
  constructor(action?: string, settings?: Partial<CpScreenSlideoutSettings>) {
    super();
    if (new.target === CpScreenSlideout) {
      if (!action) {
        throw new Error('CpScreenSlideout requires an action.');
      }
      this.init(action, settings);
    }
  }

  /**
   * Build the header/toolbar/body/sidebar/footer markup, hand it off to
   * {@link Slideout.init} (via `super.init`), then wire up the CP-screen-
   * specific shortcuts/listeners and kick off the initial {@link load}.
   * Invoked from the constructor only for the leaf class; subclasses call it
   * themselves (`super.init()`, or `this.base()` from a legacy `.extend()`
   * body — see the class docblock).
   */
  override init(
    action: string,
    settings?: Partial<CpScreenSlideoutSettings>
  ): void {
    this.action = action;

    const mergedSettings: Partial<CpScreenSlideoutSettings> = Object.assign(
      {},
      CpScreenSlideout.defaults,
      settings
    );
    Object.assign(mergedSettings.containerAttributes!, {
      id: `cp-screen-${Math.floor(Math.random() * 100000000)}`,
      class: 'cp-screen',
    });
    const isForm = mergedSettings.containerElement === 'form';
    if (isForm) {
      Object.assign(mergedSettings.containerAttributes!, {
        action: '',
        method: 'post',
        novalidate: '',
      });
    }

    this.fieldsWithErrors = [];

    // Header
    this.$header = $('<header/>', {class: 'pane-header'});
    this.$toolbar = $('<div/>', {class: 'so-toolbar'}).appendTo(this.$header);
    this.$tabContainer = $('<div/>', {class: 'pane-tabs'}).appendTo(
      this.$toolbar
    );
    this.$loadSpinner = $('<div/>', {
      class: 'spinner',
      title: Craft.t('app', 'Loading'),
      'aria-label': Craft.t('app', 'Loading'),
    }).appendTo(this.$toolbar);
    this.$editLink = $('<a/>', {
      target: '_blank',
      class: 'btn header-btn hidden',
      title: Craft.t('app', 'Open in a new tab'),
      'aria-label': Craft.t('app', 'Open in a new tab'),
      'data-icon': 'external',
    }).appendTo(this.$toolbar);
    this.$sidebarBtn = $('<button/>', {
      type: 'button',
      class: 'btn header-btn hidden sidebar-btn',
      title: Craft.t('app', 'Show sidebar'),
      'aria-label': Craft.t('app', 'Show sidebar'),
      'data-icon': `sidebar-${Garnish.ltr ? 'right' : 'left'}`,
      'aria-expanded': 'false',
    }).appendTo(this.$toolbar);

    this.addListener(this.$sidebarBtn, 'click', (ev: any) => {
      ev.preventDefault();
      if (!this.showingSidebar) {
        this.showSidebar();
        if (this.showExpandedView) {
          Craft.setCookie('sidebar-slideout', 'expanded');
        }
      } else {
        this.hideSidebar();
        if (this.showExpandedView) {
          Craft.setCookie('sidebar-slideout', 'collapsed');
        }
      }
    });

    // Body
    this.$body = $('<div/>', {class: 'so-body'});

    // Content
    this.$content = $('<div/>', {class: 'so-content'}).appendTo(this.$body);

    // Sidebar
    this.$sidebar = $('<div/>', {
      class: 'so-sidebar details hidden',
    }).appendTo(this.$body);

    // Footer
    this.$footer = $('<div/>', {class: 'so-footer hidden'});
    this.$noticeContainer = $('<div/>', {class: 'so-notice'}).appendTo(
      this.$footer
    );
    $('<div/>', {class: 'flex-grow'}).appendTo(this.$footer);
    const $btnContainer = $('<div/>', {
      class: 'flex flex-nowrap',
    }).appendTo(this.$footer);
    this.$cancelBtn = $('<button/>', {
      type: 'button',
      class: 'btn',
      text: isForm ? Craft.t('app', 'Cancel') : Craft.t('app', 'Close'),
    }).appendTo($btnContainer);
    if (isForm) {
      this.$saveBtn = Craft.ui
        .createSubmitButton({
          label: Craft.t('app', 'Save'),
          spinner: true,
        })
        .appendTo($btnContainer);
    }

    const $contents = this.$header.add(this.$body).add(this.$footer);

    super.init($contents, mergedSettings);

    this.$container.data('cpScreen', this);
    this.on('beforeClose', () => {
      this.hideSidebarIfOverlapping();
    });

    // Register shortcuts & events
    uiLayerManager().registerShortcut(
      {
        keyCode: S_KEY,
        ctrl: true,
      },
      (ev: any) => {
        this.handleSubmit(ev);
      }
    );
    uiLayerManager().registerShortcut(ESC_KEY, () => {
      this.closeMeMaybe();
    });
    this.addListener(this.$cancelBtn, 'click', () => {
      this.closeMeMaybe();
    });
    this.addListener(this.$container, 'click', (ev: any) => {
      const $target = $(ev.target);

      if (
        this.showingSidebar &&
        !$target.closest(this.$sidebarBtn).length &&
        !$target.closest(this.$sidebar).length
      ) {
        this.hideSidebarIfOverlapping();
      }
    });
    this.addListener(this.$container, 'submit', 'handleSubmit');

    this.load();
  }

  /**
   * Fetch the screen's content from {@link action} and {@link update} the
   * DOM with it. Cancels any in-flight request first (via `axios`'s cancel
   * token); `ignoreFailedRequest` suppresses the error display the
   * cancelled request's rejection would otherwise produce.
   */
  load(data?: any, refreshInitialData?: boolean): Promise<void> {
    return new Promise((resolve, reject) => {
      this.trigger('beforeLoad');
      this.showLoadSpinner();

      if (this.cancelToken) {
        this.ignoreFailedRequest = true;
        this.cancelToken.cancel();
      }

      this.cancelToken = axios.CancelToken.source();

      const options = $.extend(
        {
          params: Object.assign({}, this.getParams(), this.settings!.params),
          cancelToken: this.cancelToken.token,
          headers: {
            'X-Craft-Container-Id': this.$container.attr('id'),
          },
        },
        this.settings!.requestOptions
      );
      const request = /^(?:https?:\/\/|\/)/.test(this.action!)
        ? axios.get(this.action!, {
            ...options,
            headers: {...Craft._actionHeaders(), ...options.headers},
          })
        : Craft.sendActionRequest('GET', this.action, options);

      request
        .then((response: any) => {
          this.update(response.data)
            .then(() => {
              if (refreshInitialData !== false) {
                this.$container.data('delta-names', response.data.deltaNames);
                this.$container.data(
                  'initial-delta-values',
                  response.data.initialDeltaValues
                );
                this.$container.data(
                  'initialSerializedValue',
                  this.$container.serialize()
                );
              }
              resolve();
            })
            .catch((e: any) => {
              reject(e);
            });
        })
        .catch((e: any) => {
          if (!this.ignoreFailedRequest) {
            Craft.cp.displayError();
            reject(e);
          }
          this.ignoreFailedRequest = false;
        })
        .finally(() => {
          this.hideLoadSpinner();
          this.cancelToken = null;
        });
    });
  }

  getParams(): FormValues {
    return {};
  }

  reload(): void {
    this.showLoadSpinner();
    this.load();
  }

  updateHeaderVisibility(): void {
    // Should the header be shown regardless of viewport size?
    const forceShow =
      this.settings!.showHeader ||
      this.hasTabs ||
      this.hasCpLink ||
      this.showingLoadSpinner;

    if (forceShow || this.hasSidebar) {
      this.$header.removeClass('hidden');
    } else {
      this.$header.addClass('hidden');
    }

    if (forceShow) {
      this.$header.addClass('so-visible');
    } else {
      this.$header.removeClass('so-visible');
    }
  }

  showLoadSpinner(): void {
    this.$loadSpinner.removeClass('hidden');
    this.showingLoadSpinner = true;
    this.updateHeaderVisibility();
  }

  hideLoadSpinner(): void {
    this.$loadSpinner.addClass('hidden');
    this.showingLoadSpinner = false;
    this.updateHeaderVisibility();
  }

  update(data: any): Promise<void> {
    return new Promise((resolve) => {
      this.namespace = data.namespace;

      if (data.bodyClass) {
        this.$body.addClass(data.bodyClass);
      }

      this.unmountInertiaApp();
      this.$content.html(data.content);
      if (this.$actionBtn) {
        this.$actionBtn.data('disclosureMenu')?.destroy();
        this.$actionBtn.remove();
      }

      if (data.submitButtonLabel) {
        this.$saveBtn.find('.label').text(data.submitButtonLabel);
      }

      this.updateTabs(data.tabs);
      this.updateExtraToolbarItems(data.extraToolbarItems);

      if (data.formAttributes) {
        Craft.setElementAttributes(this.$container, data.formAttributes);
      }

      if (data.editUrl) {
        this.$editLink.removeClass('hidden').attr('href', data.editUrl);
        this.hasCpLink = true;
      } else {
        this.$editLink.addClass('hidden');
        this.hasCpLink = false;
      }

      if (data.actionMenu) {
        const labelId = Craft.namespaceId('action-menu-label', this.namespace);
        const menuId = Craft.namespaceId('action-menu', this.namespace);
        $('<label/>', {
          id: labelId,
          class: 'visually-hidden',
          text: Craft.t('app', 'Actions'),
        }).insertBefore(this.$editLink);
        this.$actionBtn = $('<button/>', {
          class: 'btn action-btn header-btn',
          type: 'button',
          title: Craft.t('app', 'Actions'),
          'aria-controls': menuId,
          'aria-describedby': labelId,
          'data-disclosure-trigger': 'true',
        }).insertBefore(this.$editLink);
        $(data.actionMenu).insertBefore(this.$editLink);
        this.$actionBtn.disclosureMenu();
      } else {
        this.$actionBtn = null;
      }

      if (data.sidebar) {
        this.$sidebarBtn.removeClass('hidden');
        this.$sidebar.html(data.sidebar);

        // Open outbound links in new windows
        this.$sidebar.find('a').each(function (this: any) {
          if (this.hostname.length && $(this).attr('target') === undefined) {
            $(this).attr('target', '_blank');
          }
        });

        this.hasSidebar = true;

        if (
          this.showExpandedView &&
          (Craft.getCookie('sidebar-slideout') || 'expanded') === 'expanded'
        ) {
          this.showSidebar(false);
        } else {
          this.hideSidebar();
        }
      } else {
        this.hideSidebar();
        this.$sidebarBtn.addClass('hidden');
        this.$sidebar.addClass('hidden').html('');
        this.hasSidebar = false;
      }

      if (data.notice) {
        this.$noticeContainer.html(data.notice);
      } else {
        this.$noticeContainer.empty();
      }

      this.updateHeaderVisibility();
      this.$footer.removeClass('hidden');

      requestAnimationFrame(async () => {
        // Execute the response JS first so any Selectize inputs, etc.,
        // get instantiated before field toggles
        await Craft.appendHeadHtml(data.headHtml);
        await Craft.appendBodyHtml(data.bodyHtml);

        if (data.inertiaPage) {
          const inertiaPage = await resolveInertiaPage(data.inertiaPage);

          this.inertiaApp = createApp(inertiaPage, {
            ...data.inertiaProps,
            slideout: true,
          });
          installCpApp(this.inertiaApp);
          this.inertiaApp.mount(this.$content[0]);
        }

        Craft.initUiElements(this.$content);
        Craft.cp.elementThumbLoader.load($(this.$content));

        if (data.sidebar) {
          Craft.initUiElements(this.$sidebar);
          Craft.cp.elementThumbLoader.load(this.$sidebar);
        }

        if (!isMobileBrowser() && !this.isOpening) {
          this.setFocusWithin();
        }

        resolve();
        this.trigger('load');
        if (this.settings!.onLoad) {
          this.settings!.onLoad();
        }
      });
    });
  }

  override setFocusWithin(): void {
    Craft.setFocusWithin(this.$content);
  }

  updateTabs(tabs: any): void {
    if (this.tabManager) {
      this.tabManager.destroy();
      this.tabManager = null;
      this.$tabContainer.html('');
    }

    this.hasTabs = !!tabs;

    if (this.hasTabs) {
      const $tabContainer = $(tabs);
      this.$tabContainer.replaceWith($tabContainer);
      this.$tabContainer = $tabContainer;
      this.tabManager = new Craft.Tabs(this.$tabContainer);
      this.tabManager.on('deselectTab', (ev: any) => {
        $(ev.$tab.attr('href')).addClass('hidden');
      });
      this.tabManager.on('selectTab', (ev: any) => {
        $(ev.$tab.attr('href')).removeClass('hidden');
        $(window).trigger('resize');
        this.$body.trigger('scroll');
      });
    }
  }

  updateExtraToolbarItems(toolbar: any): void {
    if (this.$extraToolbarItems) {
      this.$extraToolbarItems.remove();
      this.$extraToolbarItems = null;
    }

    if (!toolbar) {
      return;
    }

    this.$extraToolbarItems = $(toolbar).insertAfter(this.$tabContainer);
  }

  showSidebar(focus = true): void {
    if (this.showingSidebar) {
      return;
    }

    this.$container.addClass('showing-sidebar');
    this.$body.scrollTop(0).addClass('no-scroll');

    this.$sidebar
      .off('transitionend.so')
      .css(this._closedSidebarStyles())
      .removeClass('hidden');

    // Hack to force CSS animations
    void this.$sidebar[0].offsetWidth;

    this.$sidebar.css(this._openedSidebarStyles());

    if (focus && !isMobileBrowser()) {
      this.$sidebar.one('transitionend.so', () => {
        Craft.setFocusWithin(this.$sidebar);
      });
    }

    if (!this.showExpandedView) {
      Craft.trapFocusWithin(this.$sidebar);
    }

    this.$sidebarBtn.addClass('active').attr({
      'aria-expanded': 'true',
    });

    $(window).trigger('resize');
    this.$sidebar.trigger('scroll');

    uiLayerManager().addLayer({
      bubble: true,
    });
    uiLayerManager().registerShortcut(ESC_KEY, (ev: any) => {
      // oxlint-disable-next-line @typescript-eslint/no-unused-expressions -- bubble the shortcut only when the sidebar didn't just close for overlapping it.
      this.hideSidebarIfOverlapping() || ev.bubbleShortcut();
    });

    this.showingSidebar = true;
  }

  hideSidebar(): void {
    if (!this.showingSidebar) {
      return;
    }

    this.$container.removeClass('showing-sidebar');
    this.$body.removeClass('no-scroll');

    // Do the same thing when there are no transitions
    if (!this.sidebarIsOverlapping) {
      this.$sidebar.addClass('hidden');
      this.$sidebarBtn.focus();
    }

    this.$sidebar
      .off('transitionend.so')
      .css(this._closedSidebarStyles())
      .one('transitionend.so', () => {
        this.$sidebar.addClass('hidden');
        this.$sidebarBtn.focus();
      });

    Craft.releaseFocusWithin(this.$sidebar);

    this.$sidebarBtn.removeClass('active').attr({
      'aria-expanded': 'false',
    });

    uiLayerManager().removeLayer();

    this.showingSidebar = false;
  }

  hideSidebarIfOverlapping(): boolean {
    if (this.sidebarIsOverlapping) {
      this.hideSidebar();
      return true;
    } else {
      return false;
    }
  }

  _openedSidebarStyles() {
    return {
      [Garnish.ltr ? 'right' : 'left']: '0',
    };
  }

  _closedSidebarStyles() {
    return {
      [Garnish.ltr ? 'right' : 'left']: '-350px',
    };
  }

  showSubmitSpinner(): void {
    this.$saveBtn.addClass('loading');
  }

  hideSubmitSpinner(): void {
    this.$saveBtn.removeClass('loading');
  }

  handleSubmit(ev: any): void {
    ev.preventDefault();
    // give other submit handlers a chance to modify things
    setTimeout(() => {
      if (!ev.cancel) {
        this.submit();
      }
    }, 1);
  }

  submit(): void {
    this.showSubmitSpinner();

    const data = Craft.findDeltaData(
      this.$container.data('initialSerializedValue'),
      this.$container.serialize(),
      this.$container.data('delta-names'),
      null,
      this.$container.data('initial-delta-values')
    );

    const action = this.$container.attr('action');
    const options = {
      data,
      headers: {
        'X-Craft-Namespace': this.namespace,
      },
    };
    const request = action
      ? axios.post(action, data, {
          headers: {...Craft._actionHeaders(), ...options.headers},
        })
      : Craft.sendActionRequest('POST', null, options);

    request
      .then((response: any) => {
        this.handleSubmitResponse(response);
      })
      .catch((e: any) => {
        this.handleSubmitError(e);
      })
      .finally(() => {
        this.hideSubmitSpinner();
      });
  }

  handleSubmitResponse(response: any): void {
    this.clearErrors();
    const data = response.data || {};
    if (data.message) {
      Craft.cp.displaySuccess(data.message, data.notificationSettings);
    }
    if (data.modelClass && data.modelId) {
      Craft.refreshComponentInstances(data.modelClass, data.modelId);
    }
    const ev = {
      response: response,
      data: (data.modelName && data[data.modelName]) || {},
    };
    this.trigger('submit', ev);
    if (this.settings!.onSubmit) {
      this.settings!.onSubmit(ev);
    }
    if (this.settings!.closeOnSubmit) {
      this.close();
    }
  }

  handleSubmitError(e: any): void {
    // Careful: `(!e.response.status) === 400` is always `false` (a boolean is
    // never `=== 400`), so the status clause never contributes — every axios
    // error with a response is treated as a validation failure. Kept for
    // compatibility; the `as any` only silences TS2367, which correctly
    // flags the comparison.
    if (!e.isAxiosError || !e.response) {
      Craft.cp.displayError();
      throw e;
    }

    const data = e.response.data || {};
    Craft.cp.displayError(data.message);
    if (data.errors) {
      this.showErrors(data.errors);
    }

    if (data.errorSummary) {
      this.showErrorSummary(
        data.errorSummary,
        Object.keys(data.errors || {}).length
      );
    }
  }

  showErrorSummary(errorSummary: any, errorCount = 0): void {
    // start by clearing any error summary that might be left
    Craft.ui.clearErrorSummary(this.$body);

    // if we have multiple tabs - split the error summary into them
    if (this.tabManager !== null) {
      const $tabs = this.tabManager.$tabs;
      const $tabsWithErrors = $tabs.filter('.error');
      const $content = this.$content;

      $tabs.each(function (i: number, tab: any) {
        const tabDataId = $(tab).data('id');
        const $tabContainer = $content.find('#' + tabDataId);
        if ($tabContainer.length > 0) {
          const tabUid = $tabContainer.data('layout-tab');
          const $tabErrorSummary = $(errorSummary);
          let tabErrorCount = $tabErrorSummary.find('ul.errors li').length;
          let headingText = '';

          // remove any errors that are not specifically for this tab
          // leave out errors that don't have a tab assignment (e.g. cross-validation errors)
          $tabErrorSummary.find('ul.errors li').each(function (
            j: number,
            error: any
          ) {
            const errorTabUid = $(error).find('a').data('layout-tab');
            if (errorTabUid !== undefined && errorTabUid !== tabUid) {
              $(error).remove();
              tabErrorCount--;
            }
          });

          if (tabErrorCount > 0) {
            headingText = Craft.t(
              'app',
              'Found {num, number} {num, plural, =1{error} other{errors}} in this tab.',
              {num: tabErrorCount}
            );

            // if there are errors in any other tabs - tell users about it.
            if ($tabsWithErrors.length - 1 > 0) {
              headingText +=
                '<span class="visually-hidden">' +
                Craft.t(
                  'app',
                  '{total, number} {total, plural, =1{error} other{errors}} found in {num, number} {num, plural, =1{tab} other{tabs}}.',
                  {
                    total: errorCount,
                    num: $tabsWithErrors.length,
                  }
                ) +
                '</span>';
            }
          } else {
            headingText = Craft.t('app', 'Found errors in other tabs.');
          }

          $tabErrorSummary.find('h2').html(headingText);

          $tabErrorSummary.prependTo($tabContainer);
          Craft.ui.setFocusOnErrorSummary($tabContainer); // this also makes the deep linking work
        }
      });
    } else {
      // if we only have one tab - just show the error summary as is
      $(errorSummary).prependTo(this.$content);
      Craft.ui.setFocusOnErrorSummary(this.$content);
    }
  }

  showErrors(errors: Record<string, string | string[]>): void {
    this.clearErrors();

    const tabMenu = this.tabManager?.menu || [];
    const tabErrorIndicator =
      '<span data-icon="alert">' +
      '<span class="visually-hidden">' +
      Craft.t('app', 'This tab contains errors') +
      '</span>\n' +
      '</span>';

    Object.entries(errors).forEach(([name, fieldErrors]) => {
      const $field = this.$container.find(`[data-error-key="${name}"]`);
      if ($field) {
        Craft.ui.addErrorsToField($field, fieldErrors);
        this.fieldsWithErrors.push($field);

        // find tabs that contain fields with errors
        const fieldTabAnchors = Craft.ui.findTabAnchorForField(
          $field,
          this.$container
        );

        // add error indicator to tabs
        if (fieldTabAnchors.length > 0) {
          // add error indicator to the tabs menuBtn
          if (this.tabManager.$menuBtn.hasClass('error') == false) {
            this.tabManager.$menuBtn.addClass('error');
            this.tabManager.$menuBtn.append('<span data-icon="alert"></span>');
          }

          for (let i = 0; i < fieldTabAnchors.length; i++) {
            const $fieldTabAnchor = $(fieldTabAnchors[i]);

            if ($fieldTabAnchor.hasClass('error') == false) {
              $fieldTabAnchor.addClass('error');
              $fieldTabAnchor.find('.tab-label').append(tabErrorIndicator);

              // also add the error indicator to the disclosure menu for the tabs
              if (tabMenu.length) {
                const $tabMenuItem = tabMenu.find(
                  '[data-id=' + $fieldTabAnchor.data('id') + ']'
                );
                if (
                  $tabMenuItem.length > 0 &&
                  $tabMenuItem.hasClass('error') == false
                ) {
                  $tabMenuItem.addClass('error');
                  $tabMenuItem.append(tabErrorIndicator);
                }
              }
            }
          }
        }
      }
    });
  }

  clearErrors(): void {
    this.fieldsWithErrors.forEach(($field: any) => {
      Craft.ui.clearErrorsFromField($field);
    });
  }

  isDirty(): boolean {
    const initialValue = this.$container.data('initialSerializedValue');
    if (initialValue === undefined) {
      return false;
    }

    const serializer =
      this.$container.data('serializer') || (() => this.$container.serialize());
    return initialValue !== serializer();
  }

  /**
   * A CP screen always checks for unsaved changes before a shade click can
   * dismiss it, whatever `closeOnShadeClick` says — which is how this has
   * always behaved, back when it bound its own listener to the shade.
   */
  override handleShadeClick(): void {
    this.closeMeMaybe();
  }

  closeMeMaybe(): void {
    if (!this.isOpen) {
      return;
    }

    if (
      !this.isDirty() ||
      confirm(
        Craft.t(
          'app',
          'Are you sure you want to close this screen? Any changes will be lost.'
        )
      )
    ) {
      this.close();
    }
  }

  override close(): void {
    this.unmountInertiaApp();

    if (this.showingSidebar) {
      this.hideSidebar();
    }

    super.close();

    if (this.cancelToken) {
      this.ignoreFailedRequest = true;
      this.cancelToken.cancel();
    }
  }

  private unmountInertiaApp(): void {
    if (!this.inertiaApp) {
      return;
    }

    this.inertiaApp.unmount();
    this.inertiaApp = null;
  }
}

export default CpScreenSlideout;
