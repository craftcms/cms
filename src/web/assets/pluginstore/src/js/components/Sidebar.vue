<template>
    <div class="ps-sidebar">
        <plugin-search></plugin-search>

        <category-selector></category-selector>

        <ul class="categories">
            <li v-if="CraftEdition < CraftPro || licensedEdition < CraftPro">
                <router-link to="/upgrade-craft">
                    <img src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHdpZHRoPSIxMDBweCIgaGVpZ2h0PSIxMDBweCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+ICAgICAgICA8dGl0bGU+Y3JhZnQ8L3RpdGxlPiAgICA8ZGVzYz5DcmVhdGVkIHdpdGggU2tldGNoLjwvZGVzYz4gICAgPGRlZnM+PC9kZWZzPiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4gICAgICAgIDxnIGlkPSJjcmFmdCI+ICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbCIgZmlsbD0iI0RBNUE0NyIgY3g9IjUwIiBjeT0iNTAiIHI9IjUwIj48L2NpcmNsZT4gICAgICAgICAgICA8cGF0aCBkPSJNNjUuMTMxNDQwNCwzNC4yNjI5Njc5IEM2NS40MTUyMjQxLDM0LjQ3NTEzMDEgNjUuNjgyNzkxNywzNC42OTk0NTQ0IDY1Ljk0NDk1MzksMzQuOTI3ODMyOCBMNzAuMTgyNzkxNywzMS42MzA1MzU1IEw3MC4zMTUyMjQxLDMxLjQ2MDI2NTIgQzY5LjY2MDE5NjUsMzAuODAwOTk5IDY4Ljk1ODM2NzUsMzAuMTg5OTQ3IDY4LjIxNTIyNDEsMjkuNjMxODg2OSBDNTguNDg5NTQ4NSwyMi4zNTQ4NTk4IDQzLjc5MjI1MTIsMjUuNDAwODA1OCAzNS4zODgxOTcxLDM2LjQzNTk0MDkgQzI2Ljk4OTU0ODUsNDcuNDY5NzI0NyAyOC4wNjM4NzI4LDYyLjMxMDI2NTIgMzcuNzg4MTk3MSw2OS41ODk5OTUgQzQ1LjczMDA4OSw3NS41MzA1MzU1IDU2Ljk4Mjc5MTcsNzQuNTg3MjkyMyA2NS40MTkyNzgyLDY4LjAzNTk0MDkgTDY1LjQxMjUyMTQsNjguMDE5NzI0NyBMNjEuMzc3Mzg2Myw2NC44ODQ1ODk2IEM1NS4xMjQ2ODM2LDY4Ljg2ODM3MzMgNDcuMzY5Mjc4Miw2OS4xNTQ4NTk4IDQxLjc1ODQ2NzQsNjQuOTU3NTYyNSBDMzQuMjg1NDk0NCw1OS4zNjgzNzMzIDMzLjQ2MTE3MDEsNDcuOTY1NjcwNiAzOS45MTY1NzU1LDM5LjQ4OTk5NSBDNDYuMzY5Mjc4MiwzMS4wMTI5Njc5IDU3LjY1OTgxODcsMjguNjczNzc4OCA2NS4xMzAwODksMzQuMjYyOTY3OSBMNjUuMTMxNDQwNCwzNC4yNjI5Njc5IFoiIGlkPSJQYXRoIiBmaWxsPSIjRkZGRkZGIj48L3BhdGg+ICAgICAgICA8L2c+ICAgIDwvZz48L3N2Zz4=" />
                    {{ "Upgrade Craft CMS"|t('app') }}
                </router-link>
            </li>
            <li v-for="category in categories" :key="category.id">
                <router-link :to="'/categories/'+category.id">
                    <img :src="category.iconUrl" />
                    {{ category.title }}
                </router-link>
            </li>
        </ul>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import CategorySelector from './CategorySelector'
    import PluginSearch from './PluginSearch'

    export default {
        components: {
            CategorySelector,
            PluginSearch,
        },

        computed: {
            ...mapState({
                categories: state => state.pluginStore.categories,
                licensedEdition: state => state.craft.licensedEdition,
                CraftEdition: state => state.craft.CraftEdition,
                CraftPro: state => state.craft.CraftPro,
            }),
        },
    }
</script>

<style lang="scss" scoped>
    @import "../../sass/variables";

    ul.categories {
        @apply .hidden;
    }

    @media only screen and (min-width: $minHorizontalUiWidth) {
        ul.categories {
            @apply .block;
        }
    }
</style>
