(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.CraftSupportWidget = Garnish.Base.extend(
        {
            widgetId: 0,
            envInfo: null,
            loading: false,

            $widget: null,
            $pane: null,
            $screens: null,
            $currentScreen: null,
            $nextScreen: null,

            screens: null,
            currentScreen: null,
            $helpBody: null,
            $feedbackBody: null,

            init: function(widgetId, envInfo) {
                this.widgetId = widgetId;
                this.envInfo = envInfo;

                Craft.CraftSupportWidget.widgets[this.widgetId] = this;

                this.$widget = $('#widget' + widgetId);
                this.$pane = this.$widget.find('> .front > .pane');
                this.$screens = this.$pane.find('> .body > .cs-screen');

                this.screens = {};
                this.$currentScreen = this.initScreen(Craft.CraftSupportWidget.SCREEN_HOME).$screen;
            },

            getScreen: function(screen) {
                return this.$screens.filter('.cs-screen-' + screen + ':first');
            },

            initScreen: function(screen) {
                // First time?
                if (typeof this.screens[screen] === 'undefined') {
                    this.screens[screen] = this.loadScreen(screen);
                } else {
                    this.screens[screen].reinit();
                }

                return this.screens[screen];
            },

            loadScreen: function(screen) {
                var $screen = this.getScreen(screen);

                switch (screen) {
                    case Craft.CraftSupportWidget.SCREEN_HOME:
                        return new HomeScreen(this, screen, $screen);
                    case Craft.CraftSupportWidget.SCREEN_HELP:
                        return new HelpScreen(this, screen, $screen);
                    case Craft.CraftSupportWidget.SCREEN_FEEDBACK:
                        return new FeedbackScreen(this, screen, $screen);
                    default:
                        throw 'Invalid screen: ' + screen;
                }
            },

            gotoScreen: function(screen) {
                // Are we right in the middle of a transition?
                if (this.$nextScreen) {
                    // Unfortunately velocity('finish') doesn't work fast enough
                    this.$currentScreen
                        .velocity('stop')
                        .css({opacity: 0, display: 'none'});
                    this.$nextScreen
                        .velocity('stop')
                        .css({opacity: 1});
                    this.$pane.velocity('stop');
                    this.handleScreenAnimationComplete();
                }

                // Init/prep the next screen
                this.$nextScreen = this.getScreen(screen)
                    .css({
                        display: 'block',
                        position: 'absolute',
                        left: '0px',
                        top: '0px',
                        width: this.$pane.width() + 'px'
                    });

                // Animate the new screen into view
                this.$pane.height(this.$pane.height());
                this.$currentScreen.velocity({opacity: 0}, {display: 'none'});
                this.$nextScreen.velocity({opacity: 1});
                this.$pane.velocity({height: this.$nextScreen.outerHeight()}, {
                    complete: $.proxy(this, 'handleScreenAnimationComplete')
                });

                this.currentScreen = this.initScreen(screen);
            },

            handleScreenAnimationComplete: function() {
                this.$pane.height('auto');
                this.$nextScreen.css({
                    position: 'relative',
                    width: 'auto'
                });
                this.$currentScreen = this.$nextScreen;
                this.$nextScreen = null;
            }
        },
        {
            widgets: {},
            SCREEN_HOME: 'home',
            SCREEN_HELP: 'help',
            SCREEN_FEEDBACK: 'feedback'
        });

    var BaseScreen = Garnish.Base.extend(
        {
            widget: null,
            screen: null,
            $screen: null,

            init: function(widget, screen, $screen) {
                this.widget = widget;
                this.screen = screen;
                this.$screen = $screen;

                this.afterInit();
            },

            afterInit: $.noop,
            reinit: $.noop
        });

    var HomeScreen = BaseScreen.extend(
        {
            afterInit: function() {
                var $options = this.$screen.children('.cs-opt');
                this.addListener($options, 'click', 'handleOptionClick');
            },

            handleOptionClick: function(ev) {
                var screen = $.attr(ev.currentTarget, 'data-screen');
                this.widget.gotoScreen(screen);
            }
        });

    var BaseSearchScreen = BaseScreen.extend(
        {
            $body: null,
            $formContainer: null,
            mode: null,
            bodyStartHeight: null,

            $searchResultsContainer: null,
            $searchResults: null,
            $searchForm: null,
            $searchParams: null,
            $searchSubmit: null,
            searchTimeout: null,
            showingResults: false,

            $supportForm: null,
            $supportMessage: null,
            $supportAttachment: null,
            $supportSubmit: null,
            $supportSpinner: null,
            $supportErrorList: null,
            $supportIframe: null,
            sendingSupportTicket: false,

            afterInit: function() {
                this.$body = this.$screen.find('.cs-body-text:first').trigger('focus');
                this.$formContainer = this.$screen.children('.cs-forms');

                // Search mode stuff
                this.$searchResultsContainer = this.$screen.children('.cs-search-results-container:first');
                this.$searchResults = this.$searchResultsContainer.find('.cs-search-results:first');
                this.$searchForm = this.$formContainer.children('.cs-search-form:first');
                this.$searchParams = this.$searchForm.children('.cs-search-params:first');
                this.$searchSubmit = this.$searchForm.children('.submit:first');
                this.addListener(this.$searchForm, 'submit', 'handleSearchFormSubmit');
                this.addListener(this.$searchForm.find('> p > a'), 'click', 'handleSupportLinkClick');

                // Support mode stuff
                this.$supportForm = this.$formContainer.children('.cs-support-form:first');
                this.$supportMessage = this.$supportForm.children('input.cs-support-message');
                var $more = this.$supportForm.children('.cs-support-more');
                this.$supportAttachment = $more.find('.cs-support-attachment:first');
                this.$supportSubmit = this.$supportForm.children('.submit:first');
                this.$supportSpinner = this.$supportForm.children('.spinner:first');
                this.$supportIframe = this.$screen.children('iframe');
                this.addListener(this.$supportForm, 'submit', 'handleSupportFormSubmit');

                this.bodyStartHeight = this.$body.height();
                this.addListener(this.$body, 'textchange', 'handleBodyTextChange');
                this.addListener(this.$body, 'keydown', 'handleBodyKeydown');
                this.prepForSearch(false);
            },

            handleSearchFormSubmit: function(ev) {
                if (!this.$body.val()) {
                    ev.preventDefault();
                }
            },

            handleBodyTextChange: function() {
                var text = this.$body.val();

                if (this.mode === BaseSearchScreen.MODE_SEARCH) {
                    this.clearSearchTimeout();
                    this.searchTimeout = setTimeout($.proxy(this, 'search'), 500);

                    if (text) {
                        this.$searchParams.html('');
                        var params = this.getFormParams(text);
                        for (var name in params) {
                            if (params.hasOwnProperty(name)) {
                                $('<input/>', {
                                    type: 'hidden',
                                    name: name,
                                    value: params[name]
                                }).appendTo(this.$searchParams);
                            }
                        }
                        this.$searchSubmit.removeClass('disabled');
                    } else {
                        this.$searchSubmit.addClass('disabled');
                    }
                } else {
                    if (text) {
                        this.$supportMessage.val(text);
                        this.$supportSubmit.removeClass('disabled');
                    } else {
                        this.$supportSubmit.addClass('disabled');
                    }
                }
            },

            handleBodyKeydown: function(ev) {
                switch (ev.keyCode) {
                    case Garnish.ESC_KEY:
                        if (this.mode === BaseSearchScreen.MODE_SEARCH) {
                            this.widget.gotoScreen(Craft.CraftSupportWidget.SCREEN_HOME);
                        } else if (!this.sendingSupportTicket) {
                            this.prepForSearch(true);
                        }
                        break;
                    case Garnish.RETURN_KEY:
                        if (Garnish.isCtrlKeyPressed(ev)) {
                            if (this.mode === BaseSearchScreen.MODE_SEARCH) {
                                this.$searchForm.trigger('submit');
                            } else {
                                this.$supportForm.trigger('submit');
                            }
                        }
                        break;
                }
            },

            handleSupportLinkClick: function() {
                this.prepForSupport(true);
            },

            clearSearchTimeout: function() {
                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = null;
                }
            },

            search: function() {
                this.clearSearchTimeout();

                var text = this.$body.val();

                if (text) {
                    var url = this.getSearchUrl(this.$body.val());
                    $.ajax({
                        url: url,
                        dataType: 'json',
                        success: $.proxy(this, 'handleSearchSuccess'),
                        error: $.proxy(this, 'hideSearchResults')
                    });
                } else {
                    this.hideSearchResults();
                }
            },

            handleSearchSuccess: function(response) {
                if (this.mode !== BaseSearchScreen.MODE_SEARCH) {
                    return;
                }

                var results = this.getSearchResults(response);

                if (results.length) {
                    var startResultsHeight;

                    if (!this.showingResults) {
                        this.$searchResultsContainer.removeClass('hidden');
                        startResultsHeight = 0;
                        this.showingResults = true;
                        this.$screen.addClass('with-results');
                    } else {
                        startResultsHeight = this.$searchResultsContainer.height();
                    }

                    this.$searchResults.html('');

                    var max = Math.min(results.length, 20);
                    for (var i = 0; i < max; i++) {
                        this.$searchResults.append($('<li>').append($('<a>', {
                            href: this.getSearchResultUrl(results[i]),
                            target: '_blank',
                            html: '<span class="status ' + this.getSearchResultStatus(results[i]) + '"></span>' + this.getSearchResultText(results[i])
                        })));
                    }

                    var endResultsHeight = this.$searchResultsContainer.height('auto').height();
                    this.$searchResultsContainer
                        .velocity('stop')
                        .height(startResultsHeight)
                        .velocity(
                            {height: endResultsHeight},
                            {
                                complete: $.proxy(function() {
                                    this.$searchResultsContainer.height('auto');
                                }, this)
                            });
                } else {
                    this.hideSearchResults();
                }
            },

            hideSearchResults: function() {
                if (this.mode !== BaseSearchScreen.MODE_SEARCH || !this.showingResults) {
                    return;
                }

                this.$searchResultsContainer
                    .velocity('stop')
                    .height(this.$searchResultsContainer.height())
                    .velocity(
                        {height: 0},
                        {
                            complete: $.proxy(function() {
                                this.$searchResultsContainer.addClass('hidden');
                            }, this)
                        });

                this.showingResults = false;
                this.$screen.removeClass('with-results');
            },

            handleSupportFormSubmit: function(ev) {
                if (!this.$body.val() || this.sendingSupportTicket) {
                    ev.preventDefault();
                    return;
                }

                this.sendingSupportTicket = true;
                this.$supportSubmit.addClass('active');
                this.$supportSpinner.removeClass('hidden');
            },

            reinit: function() {
                this.$body.trigger('focus');
            },

            prepForSearch: function(animate) {
                this.mode = BaseSearchScreen.MODE_SEARCH;

                this.$body
                    .velocity('stop')
                    .trigger('focus');

                if (this.$supportErrorList) {
                    this.$supportErrorList.remove();
                    this.$supportErrorList = null;
                }

                if (animate) {
                    this.$body.velocity({height: this.bodyStartHeight});
                } else {
                    this.$body.height(this.bodyStartHeight);
                }

                this.swapForms(this.$supportForm, this.$searchForm, animate);

                // In case there's already a search value
                this.handleBodyTextChange();
                this.search();
            },

            prepForSupport: function(animate) {
                this.clearSearchTimeout();
                this.hideSearchResults();

                this.mode = BaseSearchScreen.MODE_SUPPORT;

                this.$body
                    .velocity('stop')
                    .trigger('focus');

                if (animate) {
                    this.$body.velocity({height: this.bodyStartHeight * 2});
                } else {
                    this.$body.height(this.bodyStartHeight * 2);
                }

                this.swapForms(this.$searchForm, this.$supportForm, animate);

                // In case there's already a search value
                this.handleBodyTextChange();
            },

            swapForms: function($out, $in, animate) {
                if (animate) {
                    this.$formContainer.height(this.$formContainer.height());
                    var width = this.$formContainer.width();

                    $out
                        .velocity('stop')
                        .css({position: 'absolute', top: 0, left: 0, width: width})
                        .velocity(
                            {opacity: 0},
                            {
                                complete: function() {
                                    $out
                                        .addClass('hidden')
                                        .css({position: 'relative', width: 'auto'});
                                }
                            });

                    $in
                        .velocity('stop')
                        .removeClass('hidden')
                        .css({position: 'absolute', top: 0, left: 0, width: width})
                        .velocity(
                            {opacity: 1},
                            {
                                complete: function() {
                                    $in.css({position: 'relative', width: 'auto'});
                                }
                            });

                    this.$formContainer
                        .velocity('stop')
                        .velocity(
                            {height: $in.height()},
                            {
                                complete: $.proxy(function() {
                                    this.$formContainer.css({height: 'auto'});
                                }, this)
                            }
                        );
                } else {
                    $out.addClass('hidden');
                    $in.removeClass('hidden');
                }
            },

            parseSupportResponse: function(response) {
                this.sendingSupportTicket = false;
                this.$supportSubmit.removeClass('active');
                this.$supportSpinner.addClass('hidden');

                if (this.$supportErrorList) {
                    this.$supportErrorList.children().remove();
                }

                if (response.errors) {
                    if (!this.$supportErrorList) {
                        this.$supportErrorList = $('<ul class="errors"/>').insertAfter(this.$supportForm);
                    }

                    for (var attribute in response.errors) {
                        if (response.errors.hasOwnProperty(attribute)) {
                            for (var i = 0; i < response.errors[attribute].length; i++) {
                                var error = response.errors[attribute][i];
                                $('<li>' + error + '</li>').appendTo(this.$supportErrorList);
                            }
                        }
                    }
                }

                if (response.success) {
                    Craft.cp.displayNotice(Craft.t('app', 'Message sent successfully.'));
                    this.$body.val('');
                    this.$supportMessage.val('');
                    this.$supportAttachment.val('');
                }

                this.$supportIframe.html('');
            },

            getFormParams: function() {
                throw 'getFormParams() must be implemented';
            },
            getSearchUrl: function() {
                throw 'getSearchUrl() must be implemented';
            },
            getSearchResults: function() {
                throw 'getSearchResults() must be implemented';
            },
            getSearchResultUrl: function() {
                throw 'getSearchResultUrl() must be implemented';
            },
            getSearchResultStatus: function() {
                throw 'getSearchResultStatus() must be implemented';
            },
            getSearchResultText: function() {
                throw 'getSearchResultUrl() must be implemented';
            }
        }, {
            MODE_SEARCH: 'search',
            MODE_SUPPORT: 'support'
        });

    var HelpScreen = BaseSearchScreen.extend(
        {
            getFormParams: function(query) {
                return {title: query};
            },

            getSearchUrl: function(query) {
                return 'https://api.stackexchange.com/2.2/similar?site=craftcms&sort=relevance&order=desc&title=' + encodeURIComponent(query);
            },

            getSearchResults: function(response) {
                return response.items || [];
            },

            getSearchResultUrl: function(result) {
                return result.link;
            },

            getSearchResultStatus: function(result) {
                return result.is_answered ? 'green' : '';
            },

            getSearchResultText: function(result) {
                return result.title;
            }
        });

    var FeedbackScreen = BaseSearchScreen.extend(
        {
            getFormParams: function(query) {
                var body = "### Description\n\n\n\n" +
                    "### Steps to reproduce\n\n" +
                    "1.\n" +
                    "2.\n\n" +
                    "### Additional info\n";

                for (var name in this.widget.envInfo) {
                    if (this.widget.envInfo.hasOwnProperty(name)) {
                        body += "\n- " + name + ': ' + this.widget.envInfo[name];
                    }
                }

                return {title: query, body: body};
            },

            getSearchUrl: function(query) {
                return 'https://api.github.com/search/issues?q=type:issue+repo:craftcms/cms+' + encodeURIComponent(query);
            },

            getSearchResults: function(response) {
                return response.items || [];
            },

            getSearchResultUrl: function(result) {
                return result.html_url;
            },

            getSearchResultStatus: function(result) {
                return result.state === 'open' ? 'green' : 'red';
            },

            getSearchResultText: function(result) {
                return result.title;
            }
        });

})(jQuery);
