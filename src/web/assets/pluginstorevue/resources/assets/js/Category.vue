<template>
    <div v-if="category">
        <plugin-grid :plugins="plugins" :plugin-url-prefix="'/categories/' + categoryId + '/'"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './PluginGrid';
    import { mapGetters } from 'vuex'

    export default {
        name: 'category',

        components: {
            PluginGrid,
        },

        data () {
            return {
                plugins: [],
                categoryId: null,
            }
        },
        computed: {
            ...mapGetters({
               categories: 'allCategories',
            }),
            category() {
                let category = this.categories.find(c => {
                    const categoryId = this.$route.params.id;
                    if(c.id == categoryId) {
                        return true;
                    }
                })

                if(category) {
                    this.$root.updateTitle(category.title);
                }

                return category;
            }
        },

        created: function() {
            this.$root.showCrumbs = true;

            this.categoryId = this.$route.params.id;

            this.$http.get('https://craftid.dev/api/categories/' + this.$route.params.id).then(function(data) {
                this.plugins = data.body.plugins;

                this.$emit('categoryLoaded', this.category);
            });
        },
    }
</script>