<template>
    <div>
        <h2>{{ category.title }}</h2>
        <hr>
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
            this.$http.get('https://craftid.dev/api/categories/' + this.categoryId).then(function(data) {
                this.category = data.body;
                console.log('this.category', this.category);
            });
        },
    }
</script>