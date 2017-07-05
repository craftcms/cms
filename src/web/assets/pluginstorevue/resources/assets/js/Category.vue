<template>
    <div>
        <plugin-grid :plugins="category.plugins"></plugin-grid>
    </div>

</template>

<script>
    import PluginGrid from './PluginGrid';
    export default {
        name: 'category',

        components: {
            PluginGrid,
        },

        props: ['categoryId'],

        data () {
            return {
                category: [],
            }
        },

        created: function() {

            this.$emit('categoryLoaded', this.category);

            this.$http.get('https://craftid.dev/api/categories/' + this.categoryId).then(function(data) {
                this.category = data.body;

                this.$emit('update-title', this.category.title);
            });
        },
    }
</script>