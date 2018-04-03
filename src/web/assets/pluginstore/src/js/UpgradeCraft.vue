<template>
    <div v-if="craftData" id="upgrade-craft">
        <div id="upgrade-craft-compare" class="body">
            <table class="data fullwidth">
                <thead>
                <tr class="logos">
                    <th>
                        <img :src="craftData.craftLogo" width="70" height="70" />
                    </th>
                    <th scope="col">
                        <h1 class="logo">Solo</h1>
                        <p>{{ "For personal projects." }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Pro</h1>
                        <p>{{ "For everything else." }}</p>
                    </th>
                </tr>
                <tr class="license-statuses">
                    <td></td>
                    <td><craft-status-badge :edition="craftData.CraftSolo" /></td>
                    <td><craft-status-badge :edition="craftData.CraftPro" /></td>
                </tr>
                <tr class="price">
                    <th scope="row" class="feature">{{ "Price" }}</th>
                    <td>{{ "Free" }}</td>
                    <td v-if="craftData.editions">{{ craftData.editions.pro.price|currency }}</td>
                </tr>
                <tr class="buybtns">
                    <td></td>
                    <td></td>
                    <td>
                        <div class="btngroup">
                            <div  @click="buyCraft('pro')" class="btn submit">Buy now</div>

                            <template v-if="craftData.CraftEdition === craftData.CraftPro && craftData.licensedEdition === craftData.CraftSolo">
                                <div @click="installCraft()" class="btn">Uninstall</div>
                            </template>

                            <template v-else>
                                <div @click="installCraft('pro')" class="btn">Try for free</div>
                            </template>
                        </div>
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <th class="group" colspan="4">{{ "User Accounts" }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Additional user accounts" }}</th>
                    <td>{{ "One Admin account" }}</td>
                    <td>{{ "Unlimited" }}</td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User groups" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User permissions" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Public user registration" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>

                <tr>
                    <th class="group" colspan="3">{{ "System Branding" }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom login screen logo" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom HTML email template" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom email message wording" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex'
    import CraftStatusBadge from './components/CraftStatusBadge';

    export default {

        components: {
            CraftStatusBadge
        },

        computed: {
            ...mapGetters({
                craftData: 'craftData',
            }),
        },

        methods: {
            ...mapActions({
                addToCart: 'addToCart',
                tryEdition: 'tryEdition',
                getCraftData: 'getCraftData',
            }),

            buyCraft(edition) {
                const item = {
                    type: 'cms-edition',
                    edition: edition,
                    licenseKey: window.cmsLicenseKey,
                    autoRenew: false,
                }

                this.addToCart([item])
            },

            installCraft(edition) {
                this.tryEdition(edition)
                    .then(() =>  {
                        this.getCraftData()
                            .then(() => {
                                this.$root.displayNotice("Craft CMS edition changed.");
                            })
                    })
                    .catch(() => {
                        this.$root.displayError("Couldn’t change Craft CMS edition.");
                    })
            },

        },

        created () {
            this.$root.crumbs = [
                {
                    label: this.$options.filters.t("Plugin Store", 'app'),
                    path: '/',
                }
            ];
            this.$root.pageTitle = 'Upgrade Craft CMS';
        },
    }
</script>
