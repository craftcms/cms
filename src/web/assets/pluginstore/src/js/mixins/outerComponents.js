export default {
    methods: {
        /**
         * Initializes components that live outside of the Vue app.
         */
        initializeOuterComponents() {
            // Header Title
            const $headerTitle = $('#header h1')

            $headerTitle.on('click', function() {
                this.$router.push({path: '/'})
            }.bind(this))

            // Cart button
            const $cartButton = $('#cart-button')

            $cartButton.on('click', function(e) {
                e.preventDefault()
                this.openModal('cart')
            }.bind(this))

            $cartButton.keydown(function(e) {
                switch (e.which) {
                    case 13: // Enter
                    case 32: // Space
                        e.preventDefault()
                        this.openModal('cart')
                        break

                }
            }.bind(this))

            this.$on('cartChange', function (cart) {
                let totalQty = 0

                if (cart) {
                    totalQty = cart.totalQty
                }

                $('.badge', $cartButton).html(totalQty)
            })

            // Plugin Store actions
            const $pluginStoreActions = $('#pluginstore-actions')
            const $pluginStoreActionsSpinner = $('#pluginstore-actions-spinner')

            // Show actions spinner when Plugin Store data has finished loading but Craft data has not.
            this.$on('dataLoaded', function() {
                if (this.pluginStoreDataLoaded && !this.craftDataLoaded) {
                    $pluginStoreActionsSpinner.removeClass('hidden')
                }
            }.bind(this))

            // Hide actions spinner when Plugin Store data and Craft data have finished loading.
            this.$on('allDataLoaded', function() {
                $pluginStoreActions.removeClass('hidden')
                $pluginStoreActionsSpinner.addClass('hidden')
            })

            // Craft ID
            const $craftId = $('#craftid-account')
            const $craftIdConnectForm = $('#craftid-connect-form')
            const $craftIdDisconnectForm = $('#craftid-disconnect-form')

            this.$on('craftIdChange', function() {
                if (this.craftId) {
                    $('.label', $craftId).html(this.craftId.username)

                    $craftId.removeClass('hidden')
                    $craftIdConnectForm.addClass('hidden')
                    $craftIdDisconnectForm.removeClass('hidden')
                } else {
                    $craftId.addClass('hidden')
                    $craftIdConnectForm.removeClass('hidden')
                    $craftIdDisconnectForm.addClass('hidden')
                }
            })

            // Cancel ajax requests when an outbound link gets clicked
            $('a[href]').on('click', function() {
                this.$store.dispatch('craft/cancelRequests')
                this.$store.dispatch('pluginStore/cancelRequests')
            }.bind(this))
        },
    }
}
