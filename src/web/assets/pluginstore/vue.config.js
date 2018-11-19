var fs = require('fs');

module.exports = {
     filenameHashing: false,
     configureWebpack: {
         externals: {
             'vue': 'Vue',
             'vue-router': 'VueRouter',
             'vuex': 'Vuex',
             'axios': 'axios'
         }
     },
     devServer: {
         headers: { "Access-Control-Allow-Origin": "*" },
         https: {
             key: fs.readFileSync('../../../../../../ssl/pluginstore.dev.key'),
             cert: fs.readFileSync('../../../../../../ssl/pluginstore.dev.crt'),
         },
     }
 }
