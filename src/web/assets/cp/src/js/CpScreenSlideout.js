/** global: Craft */
/** global: Garnish */
/**
 * CP Screen Slideout
 */
Craft.CpScreenSlideout = Craft.Slideout.extend({
    url: null,

    namespace: null,
    action: null,
    deltaNames: null,
    initialData: null,

    $header: null,
    $toolbar: null,
    $tabContainer: null,
    $sidebarBtn: null,
    $loadSpinner: null,

    $body: null,
    $content: null,

    $sidebar: null,

    $footer: null,
    $cancelBtn: null,
    $saveBtn: null,
    $saveSpinner: null,

    tabManager: null,
    showingSidebar: false,

    cancelToken: null,
    ignoreFailedRequest: false,
    initialDeltaValues: null,
    fieldsWithErrors: null,

    init: function(url, settings) {
        this.url = Craft.getCpUrl(url);
        this.setSettings(settings, Craft.CpScreenSlideout.defaults);

        this.fieldsWithErrors = [];

        // Header
        this.$header = $('<header/>', {class: 'pane-header'});
        this.$toolbar = $('<div/>', {class: 'so-toolbar'}).appendTo(this.$header);
        this.$tabContainer = $('<div/>', {class: 'pane-tabs'}).appendTo(this.$toolbar);
        this.$loadSpinner = $('<div/>', {
            class: 'spinner',
            title: Craft.t('app', 'Loading'),
            'aria-label': Craft.t('app', 'Loading'),
        }).appendTo(this.$toolbar);
        this.$sidebarBtn = $('<button/>', {
            type: 'button',
            class: 'btn hidden sidebar-btn',
            title: Craft.t('app', 'Show sidebar'),
            'aria-label': Craft.t('app', 'Show sidebar'),
            'data-icon': `sidebar-${Garnish.ltr ? 'right' : 'left'}`,
        }).appendTo(this.$toolbar);

        this.addListener(this.$sidebarBtn, 'click', ev => {
            ev.preventDefault();
            if (!this.showingSidebar) {
                this.showSidebar();
            } else {
                this.hideSidebar();
            }
        });

        // Body
        this.$body = $('<div/>', {class: 'so-body'});

        // Content
        this.$content = $('<div/>', {class: 'so-content'}).appendTo(this.$body);

        // Sidebar
        this.$sidebar = $('<div/>', {class: 'so-sidebar hidden'}).appendTo(this.$body);
        Craft.trapFocusWithin(this.$sidebar);

        // Footer
        this.$footer = $('<div/>', {class: 'so-footer hidden'});
        $('<div/>', {class: 'flex-grow'}).appendTo(this.$footer);
        this.$cancelBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            text: Craft.t('app', 'Cancel'),
        }).appendTo(this.$footer);
        this.$saveBtn = $('<button/>', {
            type: 'submit',
            class: 'btn submit',
            text: Craft.t('app', 'Save'),
        }).appendTo(this.$footer);
        this.$saveSpinner = $('<div/>', {class: 'spinner hidden'}).appendTo(this.$footer);

        let $contents = this.$header.add(this.$body).add(this.$footer);

        this.base($contents, {
            containerElement: 'form',
            containerAttributes: {
                action: '',
                method: 'post',
                novalidate: '',
                class: 'cp-screen',
            },
            closeOnEsc: false,
            closeOnShadeClick: false,
        });

        this.$container.data('cpScreen', this);
        this.on('beforeClose', () => {
            this.hideSidebar();
        });

        // Register shortcuts & events
        Garnish.shortcutManager.registerShortcut({
            keyCode: Garnish.S_KEY,
            ctrl: true,
        }, () => {
            this.submit();
        });
        Garnish.shortcutManager.registerShortcut(Garnish.ESC_KEY, () => {
            this.closeMeMaybe();
        });
        this.addListener(this.$cancelBtn, 'click', () => {
            this.closeMeMaybe();
        });
        this.addListener(this.$shade, 'click', () => {
            this.closeMeMaybe();
        });
        this.addListener(this.$container, 'click', ev => {
            const $target = $(event.target);

            if (
                this.showingSidebar &&
                !$target.closest(this.$sidebarBtn).length &&
                !$target.closest(this.$sidebar).length
            ) {
                this.hideSidebar();
            }
        });
        this.addListener(this.$container, 'submit', ev => {
            ev.preventDefault();
            this.submit();
        });

        this.load();
    },

    /**
     * @param {object} [data={}]
     * @param {boolean} [refreshInitialData=true]
     * @returns {Promise}
     */
    load: function(data, refreshInitialData) {
        return new Promise((resolve, reject) => {
            this.trigger('beforeLoad');
            this.showLoadSpinner();

            if (this.cancelToken) {
                this.ignoreFailedRequest = true;
                this.cancelToken.cancel();
            }

            this.cancelToken = axios.CancelToken.source();

            Craft.sendActionRequest('GET', this.url, {
                cancelToken: this.cancelToken.token,
            }).then(response => {
                if (this.initialDeltaValues === null) {
                    this.initialDeltaValues = response.data.initialDeltaValues;
                }
                this.updateForm(response.data, refreshInitialData);
                resolve();
            }).catch(e => {
                if (!this.ignoreFailedRequest) {
                    Craft.cp.displayError();
                    reject(e);
                }
                this.ignoreFailedRequest = false;
            }).finally(() => {
                this.hideLoadSpinner();
                this.cancelToken = null;
            });
        });
    },

    showHeader: function() {
        this.$header.removeClass('hidden');
    },

    hideHeader: function() {
        this.$header.addClass('hidden');
    },

    showLoadSpinner: function() {
        this.showHeader();
        this.$loadSpinner.removeClass('hidden');
    },

    hideLoadSpinner: function() {
        this.$loadSpinner.addClass('hidden');
    },

    /**
     * @param {object} data
     * @param {boolean} [refreshInitialData=true]
     */
    updateForm: function(data, refreshInitialData) {
        // Cleanup
        if (this.tabManager) {
            this.$tabContainer.html('');
            this.tabManager.destroy();
            this.tabManager = null;
        }
        refreshInitialData = refreshInitialData !== false;

        this.namespace = data.namespace;
        this.action = data.action;
        this.$content.html(data.content);

        let showHeader = false;

        if (data.tabs) {
            showHeader = true;
            this.$tabContainer.replaceWith(this.$tabContainer = $(data.tabs));
            this.tabManager = new Craft.Tabs(this.$tabContainer);
            this.tabManager.on('deselectTab', ev => {
                $(ev.$tab.attr('href')).addClass('hidden');
            });
            this.tabManager.on('selectTab', ev => {
                $(ev.$tab.attr('href')).removeClass('hidden');
                Garnish.$win.trigger('resize');
                this.$body.trigger('scroll');
            });
        }

        if (data.sidebar) {
            showHeader = true;
            this.$sidebarBtn.removeClass('hidden');
            this.$sidebar.html(data.sidebar);

            // Open outbound links in new windows
            this.$sidebar.find('a').each(function() {
                if (this.hostname.length && typeof $(this).attr('target') === 'undefined') {
                    $(this).attr('target', '_blank')
                }
            });
        } else if (this.$sidebarBtn) {
            this.$sidebarBtn.addClass('hidden');
            this.$sidebar.addClass('hidden');
        }

        if (showHeader) {
            this.showHeader();
        } else {
            this.hideHeader();
        }

        this.$footer.removeClass('hidden');

        if (refreshInitialData) {
            this.deltaNames = data.deltaNames;
        }

        Garnish.requestAnimationFrame(() => {
            Craft.appendHeadHtml(data.headHtml);
            Craft.appendBodyHtml(data.bodyHtml);

            Craft.initUiElements(this.$content);
            new Craft.ElementThumbLoader().load($(this.$content));

            if (data.sidebar) {
                Craft.initUiElements(this.$sidebar);
                new Craft.ElementThumbLoader().load(this.$sidebar);
            }

            if (refreshInitialData) {
                this.initialData = this.$container.serialize();
            }

            if (!Garnish.isMobileBrowser()) {
                Craft.setFocusWithin(this.$content);
            }

            this.trigger('load');
        });
    },

    showSidebar: function() {
        if (this.showingSidebar) {
            return;
        }

        this.$body.scrollTop(0).addClass('no-scroll');

        this.$sidebar
            .off('transitionend.so')
            .css(this._closedSidebarStyles())
            .removeClass('hidden');

        // Hack to force CSS animations
        this.$sidebar[0].offsetWidth;

        this.$sidebar.css(this._openedSidebarStyles());

        if (!Garnish.isMobileBrowser()) {
            this.$sidebar.one('transitionend.so', () => {
                Craft.setFocusWithin(this.$sidebar);
            });
        }

        this.$sidebarBtn
            .addClass('active')
            .attr({
                title: Craft.t('app', 'Hide sidebar'),
                'aria-label': Craft.t('app', 'Hide sidebar'),
            });

        Garnish.$win.trigger('resize');
        this.$sidebar.trigger('scroll');

        Garnish.shortcutManager.addLayer();
        Garnish.shortcutManager.registerShortcut(Garnish.ESC_KEY, () => {
            this.hideSidebar();
        });

        this.showingSidebar = true;
    },

    hideSidebar: function() {
        if (!this.showingSidebar) {
            return;
        }

        this.$body.removeClass('no-scroll');

        this.$sidebar
            .off('transitionend.so')
            .css(this._closedSidebarStyles())
            .one('transitionend.so', () => {
                this.$sidebar.addClass('hidden');
            });

        this.$sidebarBtn
            .removeClass('active')
            .attr({
                title: Craft.t('app', 'Show sidebar'),
                'aria-label': Craft.t('app', 'Show sidebar'),
            });

        Garnish.shortcutManager.removeLayer();

        this.showingSidebar = false;
    },

    _openedSidebarStyles: function() {
        return {
            [Garnish.ltr ? 'right' : 'left']: '0',
        };
    },

    _closedSidebarStyles: function() {
        return {
            [Garnish.ltr ? 'right' : 'left']: '-350px',
        };
    },

    submit: function() {
        this.$saveSpinner.removeClass('hidden');

        const data = Craft.findDeltaData(this.initialData, this.$container.serialize(), this.deltaNames, null, this.initialDeltaValues);

        Craft.sendActionRequest('post', this.action, {
            data: data,
            headers: {
                'X-Craft-Namespace': this.namespace,
            },
        }).then(response => {
            this.clearErrors();
            const data = response.data || {};
            if (data.message) {
                Craft.cp.displayNotice(data.message);
            }
            this.trigger('submit', {
                response: response,
                data: (data.modelName && data[data.modelName]) || {},
            });
            if (this.settings.closeOnSubmit) {
                this.close();
            }
        }).catch(error => {
            if (!error.isAxiosError || !error.response || !error.response.status === 400) {
                Craft.cp.displayError();
                throw error;
            }

            const data = error.response.data || {};
            Craft.cp.displayError(data.message);
            if (data.errors) {
                this.showErrors(data.errors);
            }
        }).finally(() => {
            this.$saveSpinner.addClass('hidden');
        });
    },

    /**
     * @param {string[]} errors
     */
    showErrors: function(errors) {
        this.clearErrors();

        Object.entries(errors).forEach(([name, fieldErrors]) => {
            const $field = this.$container.find(`[data-attribute="${name}"]`);
            if ($field) {
                Craft.ui.addErrorsToField($field, fieldErrors);
                this.fieldsWithErrors.push($field);
            }
        });
    },

    clearErrors: function() {
        this.fieldsWithErrors.forEach($field => {
            Craft.ui.clearErrorsFromField($field);
        });
    },

    isDirty: function() {
        return this.initialData !== null && this.$container.serialize() !== this.initialData;
    },

    closeMeMaybe: function() {
        if (!this.isOpen) {
            return;
        }

        if (!this.isDirty() || confirm(Craft.t('app', 'Are you sure you want to close this screen? Any changes will be lost.'))) {
            this.close();
        }
    },

    close: function() {
        this.base();

        if (this.cancelToken) {
            this.ignoreFailedRequest = true;
            this.cancelToken.cancel();
        }
    },
}, {
    defaults: {
        closeOnSubmit: true,
    },
});
