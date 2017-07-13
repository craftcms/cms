<template>
    <div>
        <router-view></router-view>
        <plugin-grid :plugins="category.plugins" :plugin-url-prefix="'/categories/' + categoryId + '/'"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './PluginGrid';
    export default {
        name: 'category',

        components: {
            PluginGrid,
        },

        data () {
            return {
                category: [],
                categoryId: null,
            }
        },

        created: function() {
            this.categoryId = this.$route.params.id;


            this.$http.get('https://craftid.dev/api/categories/' + this.$route.params.id).then(function(data) {
                this.category = data.body;

                this.$emit('categoryLoaded', this.category);

                this.$emit('update-title', this.category.title);
            });
        },
    }
</script>