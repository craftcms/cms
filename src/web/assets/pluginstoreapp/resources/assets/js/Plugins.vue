<template>
    <div>
        <input class="text fullwidth" id="searchQuery" name="searchQuery" type="text" placeholder="Search plugins" v-model="searchQuery">
        <br />
        <br />
        <table class="data fullwidth">
            <thead>
            <tr>
                <th></th>
                <th>Name</th>
                <th>Developer</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="plugin in pluginsToRender">
                <td class="thin">
                    <div class="plugin-icon">
                        <img v-if="plugin.iconUrl" :src="plugin.iconUrl" height="32" />
                    </div>
                </td>
                <td>
                    <strong><router-link to="/plugins-details">{{ plugin.name }}</router-link></strong>
                    <div>{{ plugin.description }}</div>
                </td>
                <td>
                    <div v-if="plugin.developerUrl">
                        <a v-bind:href="plugin.developerUrl">{{ plugin.developerName }}</a>
                    </div>
                    <div v-else>
                        {{ plugin.developerName }}
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <div v-show="showSpinner">
            Loading…
        </div>
    </div>
</template>

<script>
    export default {
        name: 'plugins',
        data () {
            return {
                searchQuery: '',
                plugins: [],
                showSpinner: 1,
            }
        },
        computed: {
            pluginsToRender() {
                var searchQuery = this.searchQuery;
                return this.plugins.filter(function(plugin) {
                    var searchQueryRegExp = new RegExp(searchQuery, 'gi');

                    if(plugin.name.match(searchQueryRegExp)) {
                        return true;
                    }

                    if(plugin.description.match(searchQueryRegExp)) {
                        return true;
                    }

                    if(plugin.developerName.match(searchQueryRegExp)) {
                        return true;
                    }

                    if(plugin.developerUrl.match(searchQueryRegExp)) {
                        return true;
                    }
                });
            },
        },
        created: function() {
            this.$http.get('https://craftid.dev/api/plugins').then(function(data) {
                this.plugins = this.plugins.concat(data.body.data);
                this.showSpinner = 0;
            });
        }
    }
</script>

<style scoped>
    .plugin-icon {
        background: #eee;
        width: 32px;
        height: 32px;
        border-radius: 4px;
        overflow: hidden;
    }
</style>