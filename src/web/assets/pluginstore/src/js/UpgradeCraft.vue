<template>
    <div id="upgrade-craft">
        <div id="upgrade-craft-compare" class="body">
            <table class="data fullwidth">
                <thead>
                <tr class="logos">
                    <th>
                        <img :src="craftData.craftLogo" width="70" height="70" />
                    </th>
                    <th scope="col">
                        <h1 class="logo">Personal</h1>
                        <p>{{ "For sites built by and for the developer." }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Client</h1>
                        <p>{{ "For sites built for clients with only one content manager." }}</p>
                    </th>
                    <th scope="col">
                        <h1 class="logo">Pro</h1>
                        <p>{{ "For everything else." }}</p>
                    </th>
                </tr>
                <tr class="license-statuses">
                    <td></td>
                    <td><craft-status-badge :edition="craftData.CraftPersonal" /></td>
                    <td><craft-status-badge :edition="craftData.CraftClient" /></td>
                    <td><craft-status-badge :edition="craftData.CraftPro" /></td>
                </tr>
                <tr class="price">
                    <th scope="row" class="feature">{{ "One-Time Price" }}</th>
                    <td>{{ "Free" }}</td>
                    <td>{{ craftData.editions[1].formattedPrice }}</td>
                    <td>{{ craftData.editions[2].formattedPrice }}</td>
                </tr>
                <tr class="buybtns">
                    <td></td>
                    <td></td>
                    <td>
                        <div class="btngroup">
                            <a @click="buyCraft('client')" class="btn submit">Buy now</a>
                            <a @click="installCraft('client')" class="btn">Try for free</a>
                        </div>
                    </td>
                    <td>
                        <div class="btngroup">
                            <div  @click="buyCraft('pro')" class="btn submit">Buy now</div>
                            <div @click="installCraft('pro')" class="btn">Try for free</div>
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
                    <td>{{ "One “Client” account" }}</td>
                    <td>{{ "Unlimited" }}</td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User groups" }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "User permissions" }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Public user registration" }}</th>
                    <td></td>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                </tr>

                <tr>
                    <th class="group" colspan="4">{{ "System Branding" }}</th>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom login screen logo" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom HTML email template" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
                    <td><span data-icon="check"></span></td>
                </tr>
                <tr>
                    <th class="feature" scope="row">{{ "Custom email message wording" }}</th>
                    <td></td>
                    <td><span data-icon="check"></span></td>
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

            buyCraft(edition) {
                console.log('buy craft '+edition);
            },

            installCraft(edition) {
                console.log('install craft '+edition);
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