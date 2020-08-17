// TODO: following deps are still manual:
// - datepicker-i18n
// - fabricjs
// - prismjs (custom css added)
// - qunit

const autoprefixer = require('gulp-autoprefixer');
const babel = require('gulp-babel');
const concat = require('gulp-concat');
const es = require('event-stream');
const footer = require('gulp-footer');
const gulp = require('gulp');
const gulpif = require('gulp-if');
const header = require('gulp-header');
const jsonMinify = require('gulp-json-minify');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const uglify = require('gulp-uglify-es').default;
const webpack = require('webpack-stream');

const cpAssetsPath = 'src/web/assets';
const cpGlobalAssetPath = `${cpAssetsPath}/cp`;
const graphiqlAssetPath = `${cpAssetsPath}/graphiql`;
const psAssetPath = `${cpAssetsPath}/pluginstore`;
const atAssetPath = `${cpAssetsPath}/admintable`;

const cpSassGlob = [
    `${cpAssetsPath}/**/src/**/*.scss`,
    `!${graphiqlAssetPath}/**/*.scss`,
    `!${psAssetPath}/**/*.scss`,
    `!${atAssetPath}/**/*.scss`,
];

const cpGlobalJsGlob = [
    `${cpGlobalAssetPath}/src/js/Craft.js`,
    `${cpGlobalAssetPath}/src/js/Base*.js`,
    `${cpGlobalAssetPath}/src/js/*.js`,
    `!(${cpGlobalAssetPath}/src/js/Craft.js|${cpGlobalAssetPath}/src/js/Base*.js)`,
    `!${graphiqlAssetPath}/**/*.js`,
    `!${psAssetPath}/**/*.js`,
    `!${atAssetPath}/**/*.js`
];

const cpOtherJsGlob = [
    `${cpAssetsPath}/**/src/**/*.js`,
    `!${cpGlobalAssetPath}/**/*.js`,
    `!${graphiqlAssetPath}/**/*.js`,
    `!${psAssetPath}/**/*.js`,
    `!${atAssetPath}/**/*.js`,
    `!${cpAssetsPath}/tests/**/*.js`,
];

const libPath = 'lib';

const jsDeps = [
    {srcGlob: 'node_modules/@benmajor/jquery-touch-events/src/jquery.mobile-events.js', dest: `${libPath}/jquery-touch-events`},
    {srcGlob: 'node_modules/blueimp-file-upload/js/jquery.fileupload.js', dest: `${libPath}/fileupload`},
    {srcGlob: 'node_modules/d3/build/d3.js', dest: `${libPath}/d3`},
    {srcGlob: 'node_modules/element-resize-detector/dist/element-resize-detector.js', dest: `${libPath}/element-resize-detector`},
    {srcGlob: 'node_modules/fabric/dist/fabric.js', dest: `${libPath}/fabric`},
    {srcGlob: 'node_modules/garnishjs/dist/garnish.js', dest: `${libPath}/garnishjs`},
    {srcGlob: 'node_modules/inputmask/dist/jquery.inputmask.bundle.js', dest: `${libPath}/inputmask`},
    {srcGlob: 'node_modules/jquery/dist/jquery.js', dest: `${libPath}/jquery`},
    {srcGlob: 'node_modules/jquery.payment/lib/jquery.payment.js', dest: `${libPath}/jquery.payment`},
    {srcGlob: 'node_modules/iframe-resizer/js/iframeResizer.js', dest: `${libPath}/iframe-resizer`},
    {srcGlob: 'node_modules/iframe-resizer/js/iframeResizer.contentWindow.js', dest: `${libPath}/iframe-resizer-cw`},
    {srcGlob: 'node_modules/picturefill/dist/picturefill.js', dest: `${libPath}/picturefill`},
    {srcGlob: 'node_modules/punycode/punycode.js', dest: `${libPath}/punycode`},
    {srcGlob: 'node_modules/selectize/dist/js/standalone/selectize.js', dest: `${libPath}/selectize`},
    {srcGlob: 'node_modules/timepicker/jquery.timepicker.js', dest: `${libPath}/timepicker`},
    {srcGlob: 'node_modules/velocity-animate/velocity.js', dest: `${libPath}/velocity`},
    {srcGlob: 'node_modules/xregexp/xregexp-all.js', dest: `${libPath}/xregexp`},
    {srcGlob: 'node_modules/yii2-pjax/jquery.pjax.js', dest: `${libPath}/yii2-pjax`},
];

const jquiGlob = [
    'node_modules/jquery-ui/ui/version.js',
    'node_modules/jquery-ui/ui/widget.js',
    'node_modules/jquery-ui/ui/position.js',
    'node_modules/jquery-ui/ui/focusable.js',
    'node_modules/jquery-ui/ui/keycode.js',
    'node_modules/jquery-ui/ui/scroll-parent.js',
    'node_modules/jquery-ui/ui/widgets/datepicker.js',
    'node_modules/jquery-ui/ui/widgets/mouse.js',
];

const d3LocaleData = [
    {srcGlob: 'node_modules/d3-format/locale/*.json', dest: `${libPath}/d3-format`},
    {srcGlob: 'node_modules/d3-time-format/locale/*.json', dest: `${libPath}/d3-time-format`},
];

const staticDeps = [
    {srcGlob: ['node_modules/axios/dist/axios.min.js', 'node_modules/axios/dist/axios.min.map'], dest: `${libPath}/axios`},
    {srcGlob: 'node_modules/selectize/dist/css/selectize.css', dest: `${libPath}/selectize`},
];

const graphiqlJs = [
    `${graphiqlAssetPath}/src/graphiql-init.js`,
    `${graphiqlAssetPath}/src/CraftGraphiQL.js`,
];

const graphiqlCss = [
    'node_modules/graphiql/graphiql.css',
    `${graphiqlAssetPath}/src/graphiql.scss`,
];

const vueJs = [
    'node_modules/vue/dist/vue.min.js',
    'node_modules/vue-router/dist/vue-router.min.js',
    'node_modules/vuex/dist/vuex.min.js',
    'node_modules/vue-autosuggest/dist/vue-autosuggest.js',
];

gulp.task('cp-sass', function() {
    gulp.src(cpSassGlob)
        .pipe(sourcemaps.init())
        .pipe(sass({
            outputStyle: 'compact'
        }).on('error', sass.logError))
        .pipe(autoprefixer())
        .pipe(rename(function (path) {
            path.dirname = path.dirname.replace(/\bsrc\b/, 'dist');
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(function(file) {
            return file.base;
        }))
});

gulp.task('cp-global-js', function() {
    gulp.src(cpGlobalJsGlob)
        .pipe(sourcemaps.init())
        .pipe(concat('Craft.min.js'))
        .pipe(header('(function($){\n\n'))
        .pipe(footer('\n})(jQuery);\n'))
        .pipe(babel())
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(`${cpGlobalAssetPath}/dist/js`))
});

gulp.task('cp-other-js', function() {
    gulp.src(cpOtherJsGlob)
        .pipe(sourcemaps.init())
        .pipe(babel())
        .pipe(uglify())
        .pipe(rename(function (path) {
            path.dirname = path.dirname.replace(/\bsrc\b/, 'dist');
            path.extname = '.min.js';
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(function(file) {
            return file.base;
        }))
});

gulp.task('cp-js', ['cp-global-js', 'cp-other-js']);
gulp.task('cp', ['cp-sass', 'cp-js']);

gulp.task('watch', function() {
    gulp.watch(cpSassGlob, ['cp-sass']);
    gulp.watch(cpGlobalAssetPath, ['cp-global-js']);
    gulp.watch(cpOtherJsGlob, ['cp-other-js']);
});

gulp.task('jqui', function() {
    gulp.src(jquiGlob)
        .pipe(sourcemaps.init())
        .pipe(concat('jquery-ui.js'))
        .pipe(babel())
        .pipe(uglify())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(`${libPath}/jquery-ui`))
});

gulp.task('graphiql-js', function() {
    gulp.src(graphiqlJs)
        .pipe(webpack({
            entry: './src/web/assets/graphiql/src/graphiql-init.js',
            mode: 'production',
            module: {
                rules: [
                    {
                        test: /\.m?js$/,
                        exclude: /(node_modules|bower_components)/,
                        use: {
                            loader: 'babel-loader',
                            options: {
                                presets: ['@babel/preset-env']
                            }
                        }
                    }
                ]
            }
        }))
        .pipe(concat('graphiql.js'))
        .pipe(gulp.dest(`${graphiqlAssetPath}/dist`));
});

gulp.task('graphiql-css', function() {
    return gulp.src(graphiqlCss)
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('graphiql.css'))
        .pipe(gulp.dest(`${graphiqlAssetPath}/dist`));
});

gulp.task('graphiql', ['graphiql-js', 'graphiql-css']);

gulp.task('vue', function() {
    return gulp.src(vueJs)
        .pipe(concat('vue.js'))
        .pipe(gulp.dest(`${libPath}/vue`))
});

gulp.task('static-deps', function() {
    let streams = [];
    staticDeps.forEach(function(dep) {
        streams.push(
            gulp.src(dep.srcGlob)
                .pipe(gulp.dest(dep.dest))
        );
    });
    return es.merge(streams);
});

gulp.task('deps', ['jqui', 'graphiql', 'vue', 'static-deps'], function() {
    let streams = [];

    // Minify & move the JS deps
    jsDeps.forEach(function(dep) {
        streams.push(
            gulp.src(dep.srcGlob)
                .pipe(sourcemaps.init())
                .pipe(uglify())
                .pipe(sourcemaps.write('./'))
                .pipe(gulp.dest(dep.dest))
        );
    });

    // Minify & move the D3 locale JSON
    d3LocaleData.forEach(function(dep) {
        streams.push(
            gulp.src(dep.srcGlob)
                .pipe(jsonMinify())
                .pipe(gulp.dest(dep.dest))
        );
    });

    return es.merge(streams);
});
