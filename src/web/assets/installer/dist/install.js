(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.Installer = Garnish.Base.extend(
        {
            $bg: null,
            $screens: null,
            $dots: null,
            $currentScreen: null,
            $currentDot: null,

            $accountSubmitBtn: null,
            $siteSubmitBtn: null,

            loading: false,

            /**
             * Constructor
             */
            init: function() {
                this.$bg = $('#bg');
                this.$screens = $('#screens').children();
                this.$dots = $();

                for (var i = 0; i < this.$screens.length; i++) {
                    this.$dots = this.$dots.add($('<div/>').appendTo($('#dots')));
                }

                this.addListener($('#beginbtn'), 'activate', 'showAccountScreen');
            },

            showAccountScreen: function(event) {
                new Garnish.Modal($('#install-modal').removeClass('hidden'), {
                    shadeClass: ''
                });
                this.showScreen(Craft.Installer.SCREEN_ACCOUNT);
                this.$accountSubmitBtn = $('#accountsubmit');
                this.addListener(this.$accountSubmitBtn, 'activate', 'validateAccount');
                this.addListener($('#accountform'), 'submit', 'validateAccount');
            },

            validateAccount: function(event) {
                event.preventDefault();

                var inputs = ['username', 'email', 'password'];
                this.validate('account', inputs, $.proxy(this, 'showSiteScreen'));
            },

            showSiteScreen: function() {
                this.showScreen(Craft.Installer.SCREEN_SITE);
                this.$siteSubmitBtn = $('#sitesubmit');
                this.addListener(this.$siteSubmitBtn, 'activate', 'validateSite');
                this.addListener($('#siteform'), 'submit', 'validateSite');
            },

            validateSite: function(event) {
                event.preventDefault();

                var inputs = ['systemName', 'siteUrl', 'siteLanguage'];
                this.validate('site', inputs, $.proxy(this, 'showInstallScreen'));
            },

            showInstallScreen: function() {
                this.showScreen(Craft.Installer.SCREEN_INSTALL);

                var inputs = ['username', 'email', 'password', 'systemName', 'siteUrl', 'siteLanguage'];
                var data = {};

                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i],
                        $input = $('#' + input);

                    data[input] = Garnish.getInputPostVal($input);
                }

                Craft.postActionRequest('install/install', data, $.proxy(this, 'allDone'), {
                    complete: $.noop
                });
            },

            allDone: function(response, textStatus) {
                $('#spinner').remove();
                var $h1 = this.$currentScreen.find('h1:first');

                if (textStatus === 'success' && response.success) {
                    $h1.text(Craft.t('app', 'Craft is installed! 🎉'));


                    setTimeout(function() {
                        window.location.href = Craft.getUrl('dashboard');
                    }, 1000);
                }
                else {
                    $h1.text('Install failed 😞');
                    $('<p/>', {text: 'Please check your logs for more info.'})
                        .insertAfter($h1);
                }
            },

            showScreen: function(i) {
                // Hide the current screen
                if (this.$currentScreen) {
                    this.$currentScreen.addClass('hidden');
                    this.$currentDot.removeClass('sel');
                }

                // Slide in the new screen
                this.$currentScreen = this.$screens.eq(i - 1)
                    .removeClass('hidden');
                this.$currentDot = this.$dots.eq(i - 1)
                    .addClass('sel');

                // Give focus to the first input
                if (i === 1) {
                    setTimeout($.proxy(this, 'focusFirstInput'), 100);
                } else {
                    this.focusFirstInput();
                }
            },

            validate: function(what, inputs, callback) {
                // Prevent double-clicks
                if (this.loading) {
                    return;
                }

                this.loading = true;

                // Clear any previous error lists
                $('#' + what + 'form').find('.errors').remove();

                var $submitBtn = this['$' + what + 'SubmitBtn'];
                $submitBtn.addClass('sel loading');

                var action = 'install/validate-' + what;

                var data = {};
                for (var i = 0; i < inputs.length; i++) {
                    var input = inputs[i],
                        $input = $('#' + input);
                    data[input] = Garnish.getInputPostVal($input);
                }

                Craft.postActionRequest(action, data, $.proxy(function(response, textStatus) {
                    this.loading = false;
                    $submitBtn.removeClass('sel loading');

                    if (textStatus === 'success') {
                        if (response.validates) {
                            callback();
                        }
                        else {
                            for (var input in response.errors) {
                                if (!response.errors.hasOwnProperty(input)) {
                                    continue;
                                }

                                var errors = response.errors[input],
                                    $input = $('#' + input),
                                    $field = $input.closest('.field'),
                                    $ul = $('<ul class="errors"/>').appendTo($field);

                                for (var i = 0; i < errors.length; i++) {
                                    var error = errors[i];
                                    $('<li>' + error + '</li>').appendTo($ul);
                                }

                                if (!$input.is(':focus')) {
                                    $input.addClass('error');
                                    ($.proxy(function($input) {
                                        this.addListener($input, 'focus', function() {
                                            $input.removeClass('error');
                                            this.removeListener($input, 'focus');
                                        });
                                    }, this))($input);
                                }
                            }

                            Garnish.shake(this.$currentScreen);
                        }
                    }

                }, this));
            },

            focusFirstInput: function() {
                this.$currentScreen.find('input[type=text]:first').focus();
            }

        }, {
            SCREEN_ACCOUNT: 1,
            SCREEN_SITE: 2,
            SCREEN_INSTALL: 3
        });

    Garnish.$win.on('load', function() {
        Craft.installer = new Craft.Installer();
    });
})(jQuery);
