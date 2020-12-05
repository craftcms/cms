var fs = require('fs');
const ManifestPlugin = require('webpack-manifest-plugin')

let https = null

const httpsKey = process.env.DEV_SERVER_SSL_KEY ? fs.readFileSync(process.env.DEV_SERVER_SSL_KEY) : null
const httpsCert = process.env.DEV_SERVER_SSL_CERT ? fs.readFileSync(process.env.DEV_SERVER_SSL_CERT) : null

if (httpsKey || httpsCert) {
    https = {
        key: httpsKey,
        cert: httpsCert,
    }
}

module.exports = {
    filenameHashing: false,
    publicPath: process.env.NODE_ENV === 'development' ? process.env.DEV_PUBLIC_PATH : '/',
    configureWebpack: {
        externals: {
            'vue': 'Vue',
            'vue-router': 'VueRouter',
            'vuex': 'Vuex',
            'axios': 'axios'
        },
        plugins: [
            new ManifestPlugin({
                publicPath: '/'
            }),
        ]
    },
    devServer: {
        port: process.env.DEV_SERVER_PORT,
        headers: {"Access-Control-Allow-Origin": "*"},
        https,

        // Fix bug caused by webpack-dev-server 3.1.11.
        // https://github.com/vuejs/vue-cli/issues/3173#issuecomment-449573901
        disableHostCheck: true,
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
