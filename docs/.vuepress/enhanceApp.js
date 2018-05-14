import VueAnalytics from 'vue-analytics'

export default ({ Vue, options, router, siteData }) => {
    Vue.use(VueAnalytics, {
        // $vue: this,
        id: 'UA-39036834-7',
        router
    })
}
