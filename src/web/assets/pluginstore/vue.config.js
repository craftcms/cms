var fs = require('fs');

module.exports = {
     filenameHashing: false,
    // only set this when hmr is on?
    baseUrl: 'https://192.168.1.72:8080',
     configureWebpack: {
         externals: {
             'vue': 'Vue',
             'vue-router': 'VueRouter',
             'vuex': 'Vuex',
             'axios': 'axios'
         },
     },
     devServer: {
         hotOnly: true,
         proxy: 'https://192.168.1.72:8080',
         public: '192.168.1.72:8080',
         headers: { "Access-Control-Allow-Origin": "*" },
         https: {
             key: fs.readFileSync('../../../../../../ssl/pluginstore.dev.key'),
             cert: fs.readFileSync('../../../../../../ssl/pluginstore.dev.crt'),
         },
     },

    chainWebpack: config => {
         config.module
             .rule('images')
             .use('url-loader')
                .tap(options => {
                    options.limit = -1
                    options.fallback.options.name = 'images/[name].[ext]'
                    return options
                })
    }
 }
