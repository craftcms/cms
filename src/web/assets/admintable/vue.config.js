var fs = require('fs');
const ManifestPlugin = require('webpack-manifest-plugin')

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
        // https: {
        //     key: process.env.DEV_SERVER_SSL_KEY ? fs.readFileSync(process.env.DEV_SERVER_SSL_KEY) : null,
        //     cert: process.env.DEV_SERVER_SSL_CERT ? fs.readFileSync(process.env.DEV_SERVER_SSL_CERT) : null,
        // },

        // Fix bug caused by webpack-dev-server 3.1.11.
        // https://github.com/vuejs/vue-cli/issues/3173#issuecomment-449573901
        disableHostCheck: true,
    }
}
