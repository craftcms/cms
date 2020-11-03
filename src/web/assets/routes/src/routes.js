(function($) {
    /** global: Craft */
    /** global: Garnish */
    var Routes = Garnish.Base.extend(
        {
            tokens: null,
            routes: null,
            $container: null,
            $addRouteBtn: null,
            sorter: null,

            init: function() {
                this.tokens = {};
                this.routes = [];
                this.$container = $('#routes');

                var $routes = this.getRoutes();

                for (var i = 0; i < $routes.length; i++) {
                    var route = new Route($routes[i]);
                    this.routes.push(route);
                }

                this.sorter = new Garnish.DragSort($routes, {
                    axis: Garnish.Y_AXIS,
                    onSortChange: $.proxy(this, 'updateRouteOrder')
                });

                this.$addRouteBtn = $('#add-route-btn');

                this.addListener(this.$addRouteBtn, 'click', 'addRoute');
            },

            getRoutes: function() {
                return this.$container.children();
            },

            updateRouteOrder: function() {
                var $routes = this.getRoutes(),
                    data = {};

                for (var i = 0; i < $routes.length; i++) {
                    data['routeUids[' + i + ']'] = $($routes[i]).attr('data-uid');
                }

                Craft.postActionRequest('routes/update-route-order', data, $.proxy(function(response, textStatus) {
                    if (textStatus === 'success') {
                        if (response.success) {
                            Craft.cp.displayNotice(Craft.t('app', 'New route order saved.'));
                        }
                        else {
                            Craft.cp.displayError(Craft.t('app', 'Couldn’t save new route order.'));
                        }
                    }
                }, this));
            },

            addRoute: function() {
                new RouteSettingsModal();
            }
        });


    var Route = Garnish.Base.extend(
        {
            $container: null,
            uid: null,
            siteUid: null,
            $siteLabel: null,
            $uri: null,
            $template: null,
            modal: null,

            init: function(container) {
                this.$container = $(container);
                this.uid = this.$container.data('uid');
                this.siteUid = this.$container.data('site-uid');
                this.$siteLabel = this.$container.find('.site:first');
                this.$uri = this.$container.find('.uri:first');
                this.$template = this.$container.find('.template:first');

                this.addListener(this.$container, 'click', 'edit');
            },

            edit: function() {
                if (!this.modal) {
                    this.modal = new RouteSettingsModal(this);
                }
                else {
                    this.modal.show();
                }
            },

            updateHtmlFromModal: function() {
                var i;

                if (Craft.isMultiSite) {
                    if (this.siteUid) {
                        for (i = 0; i < Craft.sites.length; i++) {
                            if (Craft.sites[i].uid == this.siteUid) {
                                this.$siteLabel.text(Craft.sites[i].name);
                                break;
                            }
                        }
                    }
                    else {
                        this.$siteLabel.text(Craft.t('app', 'Global'));
                    }
                }

                var uriHtml = '';

                for (i = 0; i < this.modal.uriInput.elements.length; i++) {
                    var $elem = this.modal.uriInput.elements[i];

                    if (this.modal.uriInput.isText($elem)) {
                        uriHtml += Craft.escapeHtml($elem.val());
                    }
                    else {
                        uriHtml += $elem.prop('outerHTML');
                    }
                }

                this.$uri.html(uriHtml);
                this.$template.text(this.modal.$templateInput.val());
            }
        });


    var RouteSettingsModal = Garnish.Modal.extend(
        {
            route: null,
            $heading: null,
            $uriInput: null,
            $uriErrors: null,
            uriElements: null,
            $templateInput: null,
            $saveBtn: null,
            $cancelBtn: null,
            $spinner: null,
            $deleteBtn: null,
            loading: false,

            init: function(route) {
                this.route = route;

                var tokenHtml = '<h4>' + Craft.t('app', 'Add a token') + '</h4>';

                for (var name in Craft.routes.tokens) {
                    if (!Craft.routes.tokens.hasOwnProperty(name)) {
                        continue;
                    }

                    var pattern = Craft.routes.tokens[name];
                    tokenHtml += '<div class="token" data-name="' + name + '" data-value="' + pattern + '"><span>' + name + '</span></div>';
                }

                var containerHtml =
                    '<form class="modal fitted route-settings" accept-charset="UTF-8">' +
                    '<div class="header">' +
                    '<h1></h1>' +
                    '</div>' + // .header
                    '<div class="body">' +
                    '<div class="uri-field field">' +
                    '<div class="heading">' +
                    '<label for="uri">' + Craft.t('app', 'If the URI looks like this') + ':</label>' +
                    '</div>' +  // .heading
                    '<div class="input">';

                if (Craft.isMultiSite) {
                    containerHtml +=
                        '<div class="flex">' +
                            '<div class="flex-grow">';
                }

                containerHtml += '<div id="uri" class="text uri ltr"></div>';

                var i;

                if (Craft.isMultiSite) {
                    containerHtml +=
                        '</div>' + // .flex-grow
                        '<div class="select">' +
                        '<select class="site">' +
                        '<option value="">' + Craft.t('app', 'Global') + '</option>';

                    for (i = 0; i < Craft.sites.length; i++) {
                        var siteInfo = Craft.sites[i];
                        containerHtml += '<option value="' + siteInfo.uid + '">' + Craft.escapeHtml(siteInfo.name) + '</option>';
                    }

                    containerHtml +=
                        '</select>' +
                        '</div>' + // .select
                        '</div>'; // .flex
                }

                containerHtml +=
                    '</div>' + // .input
                    '<div class="uri-tokens">' +
                    tokenHtml +
                    '</div>' + // .uri-tokens
                    '</div>' + // .uri-field
                    '<div class="template-field field">' +
                    '<div class="heading">' +
                    '<label for="template">' + Craft.t('app', 'Load this template') + ':</label>' +
                    '</div>' + // .heading
                    '<div class="input">' +
                    '<input id="template" type="text" class="text fullwidth template ltr">' +
                    '</div>' + // .input
                    '</div>' + // .template-field
                    '</div>' + // .body
                    '<div class="footer">' +
                    '<div class="buttons right last">' +
                    '<button type="button" class="btn cancel">' + Craft.t('app', 'Cancel') + '</button>' +
                    '<button type="submit" class="btn submit">' + Craft.t('app', 'Save') + '</button>' +
                    '<div class="spinner" style="display: none;"></div>' +
                    '</div>' +
                    '<a class="delete">' + Craft.t('app', 'Delete') + '</a>' +
                    '</div>' +
                    '</form>';

                var $container = $(containerHtml).appendTo(Garnish.$bod);

                // Find the other elements
                this.$heading = $container.find('h1:first');
                this.$siteInput = $container.find('.site:first');
                this.$uriInput = $container.find('.uri:first');
                this.$templateInput = $container.find('.template:first');
                this.$saveBtn = $container.find('.submit:first');
                this.$cancelBtn = $container.find('.cancel:first');
                this.$spinner = $container.find('.spinner:first');
                this.$deleteBtn = $container.find('.delete:first');

                // Hide the Delete button for new routes
                if (!this.route) {
                    this.$deleteBtn.hide();
                }

                // Initialize the uri input
                this.uriInput = new Garnish.MixedInput(this.$uriInput, {
                    dir: 'ltr'
                });

                // Set the heading
                if (this.route) {
                    this.$heading.html(Craft.t('app', 'Edit Route'));
                }
                else {
                    this.$heading.html(Craft.t('app', 'Create a new route'));
                }

                if (this.route) {
                    // Set the site
                    this.$siteInput.val(this.route.siteUid);

                    // Set the initial uri value
                    var uriNodes = this.route.$uri.prop('childNodes');

                    for (i = 0; i < uriNodes.length; i++) {
                        var node = uriNodes[i];

                        if (Garnish.isTextNode(node)) {
                            var text = this.uriInput.addTextElement();
                            text.setVal(node.nodeValue);
                        }
                        else {
                            this.addUriVar(node);
                        }
                    }

                    // Set the initial Template value
                    var templateVal = this.route.$template.text();
                    this.$templateInput.val(templateVal);
                }

                this.base($container);

                // We must add vars on mousedown, so that text elements don't have a chance
                // to lose focus, thus losing the carot position.
                var $uriVars = this.$container.find('.uri-tokens').children('div');

                this.addListener($uriVars, 'mousedown', function(event) {
                    this.addUriVar(event.currentTarget);
                });

                // Save/Cancel/Delete
                this.addListener(this.$container, 'submit', 'saveRoute');
                this.addListener(this.$cancelBtn, 'click', 'cancel');
                this.addListener(this.$deleteBtn, 'click', 'deleteRoute');
            },

            addUriVar: function(elem) {
                var $uriVar = $(elem).clone().attr('tabindex', '0');
                this.uriInput.addElement($uriVar);

                this.addListener($uriVar, 'keydown', function(event) {
                    switch (event.keyCode) {
                        case Garnish.LEFT_KEY: {
                            // Select the previous element
                            setTimeout($.proxy(function() {
                                this.uriInput.focusPreviousElement($uriVar);
                            }, this), 1);

                            break;
                        }
                        case Garnish.RIGHT_KEY: {
                            // Select the next element
                            setTimeout($.proxy(function() {
                                this.uriInput.focusNextElement($uriVar);
                            }, this), 1);

                            break;
                        }
                        case Garnish.DELETE_KEY: {
                            // Delete this element
                            setTimeout($.proxy(function() {
                                this.uriInput.removeElement($uriVar);
                            }, this), 1);

                            event.preventDefault();
                        }
                    }
                });
            },

            show: function() {
                if (this.route) {
                    this.$heading.html(Craft.t('app', 'Edit Route'));
                    this.$deleteBtn.show();
                }

                // Focus on the first element
                setTimeout(function() {
                    if (this.uriInput.elements.length) {
                        var $firstElem = this.uriInput.elements[0];
                        this.uriInput.setFocus($firstElem);
                        this.uriInput.setCarotPos($firstElem, 0);
                    } else {
                        this.$uriInput.trigger('focus');
                    }
                }.bind(this), 100);

                this.base();
            },

            saveRoute: function(event) {
                event.preventDefault();

                if (this.loading) {
                    return;
                }

                this.$container.find('.uri-field .input').removeClass('errors');
                if (this.$uriErrors) {
                    this.$uriErrors.remove();
                    this.$uriErrors = null;
                }

                var data = {
                    siteUid: this.$siteInput.val()
                };

                if (this.route) {
                    data.routeUid = this.route.uid;
                }

                var $elem, val;

                for (var i = 0; i < this.uriInput.elements.length; i++) {
                    $elem = this.uriInput.elements[i];

                    if (this.uriInput.isText($elem)) {
                        val = $elem.val();

                        if (i === 0) {
                            // Remove any leading slashes
                            val = Craft.ltrim(val, '/');

                            // Make sure the first element isn't using the action/CP trigger
                            if (Craft.startsWith(val, Craft.actionTrigger + '/')) {
                                this.addUriError(Craft.t('app', 'The URI can’t begin with the {setting} config setting.', {
                                    setting: 'actionTrigger'
                                }));
                                return;
                            } else if (Craft.cpTrigger && Craft.startsWith(val, Craft.cpTrigger + '/')) {
                                this.addUriError(Craft.t('app', 'The URI can’t begin with the {setting} config setting.', {
                                    setting: 'cpTrigger'
                                }));
                                return;
                            }
                        }

                        data['uriParts[' + i + ']'] = val;
                    }
                    else {
                        data['uriParts[' + i + '][0]'] = $elem.attr('data-name');
                        data['uriParts[' + i + '][1]'] = $elem.attr('data-value');
                    }
                }

                data.template = this.$templateInput.val();

                this.loading = true;
                this.$saveBtn.addClass('active');
                this.$spinner.show();

                Craft.postActionRequest('routes/save-route', data, $.proxy(function(response, textStatus) {
                    this.$saveBtn.removeClass('active');
                    this.$spinner.hide();
                    this.loading = false;

                    if (textStatus === 'success') {
                        if (response.success) {
                            // Is this a new route?
                            if (!this.route) {
                                var routeHtml =
                                    '<div class="route" data-uid="' + response.routeUid + '"' + (response.siteUid ? ' data-site-uid="' + response.siteUid + '"' : '') + '>' +
                                    '<div class="uri-container">';

                                if (Craft.isMultiSite) {
                                    routeHtml += '<span class="site"></span>';
                                }

                                routeHtml +=
                                    '<span class="uri" dir="ltr"></span>' +
                                    '</div>' +
                                    '<div class="template" dir="ltr"></div>' +
                                    '</div>';

                                var $route = $(routeHtml);

                                $route.appendTo('#routes');

                                this.route = new Route($route);
                                this.route.modal = this;

                                Craft.routes.sorter.addItems($route);

                                // Was this the first one?
                                if (Craft.routes.sorter.$items.length === 1) {
                                    $('#noroutes').addClass('hidden');
                                }
                            }

                            this.route.siteUid = response.siteUid;
                            this.route.updateHtmlFromModal();
                            this.hide();

                            Craft.cp.displayNotice(Craft.t('app', 'Route saved.'));
                        } else if (response.errors) {
                            if (response.errors.uri) {
                            }
                        } else {
                            Craft.cp.displayError(Craft.t('app', 'Couldn’t save route.'));
                        }
                    }
                }, this));
            },

            addUriError: function(error) {
                this.$container.find('.uri-field .input').addClass('errors');
                if (this.$uriErrors) {
                    Craft.ui.addErrorsToList(this.$uriErrors, [error]);
                } else {
                    this.$uriErrors = Craft.ui.createErrorList([error]);
                    this.$uriErrors.insertAfter(this.$container.find('.uri-field .input'));
                }
            },

            cancel: function() {
                this.hide();

                if (this.route) {
                    this.route.modal = null;
                }
            },

            deleteRoute: function() {
                if (confirm(Craft.t('app', ('Are you sure you want to delete this route?')))) {
                    Craft.postActionRequest('routes/delete-route', {routeUid: this.route.uid}, function(response, textStatus) {
                        if (textStatus === 'success') {
                            Craft.cp.displayNotice(Craft.t('app', 'Route deleted.'));
                        }
                    });

                    Craft.routes.sorter.removeItems(this.route.$container);
                    this.route.$container.remove();
                    this.hide();

                    // Was this the last one?
                    if (Craft.routes.sorter.$items.length === 0) {
                        $('#noroutes').removeClass('hidden');
                    }
                }
            }
        });


    Craft.routes = new Routes();
})(jQuery);
