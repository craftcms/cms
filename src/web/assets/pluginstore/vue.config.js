var fs = require('fs');

module.exports = {
    filenameHashing: false,
    baseUrl: process.env.NODE_ENV === 'development' ? process.env.DEV_BASE_URL : process.env.PROD_BASE_URL,
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
        port: process.env.DEV_SERVER_PORT,
        headers: {"Access-Control-Allow-Origin": "*"},
        https: {
            key: process.env.DEV_SERVER_SSL_KEY ? fs.readFileSync(process.env.DEV_SERVER_SSL_KEY) : null,
            cert: process.env.DEV_SERVER_SSL_CERT ? fs.readFileSync(process.env.DEV_SERVER_SSL_CERT) : null,
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
