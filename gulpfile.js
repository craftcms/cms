// TODO: following deps are still manual:
// - datepicker-i18n
// - fabricjs
// - jquery-touch-events
// - jquery-ui
// - prismjs (custom css added)
// - qunit

var es = require('event-stream');
var gulp = require('gulp');
var concat = require('gulp-concat');
var jsonMinify = require('gulp-json-minify');
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');
var gulpif = require('gulp-if');
var sass = require('gulp-sass');

var libPath = 'lib/';

var jsDeps = [
    { srcGlob: 'node_modules/blueimp-file-upload/js/jquery.fileupload.js', dest: libPath+'fileupload' },
    { srcGlob: 'node_modules/d3/build/d3.js', dest: libPath+'d3' },
    { srcGlob: 'node_modules/element-resize-detector/dist/element-resize-detector.js', dest: libPath+'element-resize-detector' },
    { srcGlob: 'node_modules/fabric/dist/fabric.js', dest: libPath+'fabric' },
    { srcGlob: 'node_modules/garnishjs/dist/garnish.js', dest: libPath+'garnishjs' },
    { srcGlob: 'node_modules/inputmask/dist/jquery.inputmask.bundle.js', dest: libPath+'inputmask' },
    { srcGlob: 'node_modules/jquery/dist/jquery.js', dest: libPath+'jquery' },
    { srcGlob: 'node_modules/jquery.payment/lib/jquery.payment.js', dest: libPath+'jquery.payment' },
    { srcGlob: 'node_modules/picturefill/dist/picturefill.js', dest: libPath+'picturefill' },
    { srcGlob: 'node_modules/punycode/punycode.js', dest: libPath+'punycode' },
    { srcGlob: 'node_modules/selectize/dist/js/standalone/selectize.js', dest: libPath+'selectize' },
    { srcGlob: 'node_modules/timepicker/jquery.timepicker.js', dest: libPath+'timepicker' },
    { srcGlob: 'node_modules/velocity-animate/velocity.js', dest: libPath+'velocity' },
    { srcGlob: 'node_modules/xregexp/xregexp-all.js', dest: libPath+'xregexp' },
    { srcGlob: 'node_modules/yii2-pjax/jquery.pjax.js', dest: libPath+'yii2-pjax' },
];

var d3LocaleData = [
    { srcGlob: 'node_modules/d3-format/locale/*.json', dest: libPath+'d3-format' },
    { srcGlob: 'node_modules/d3-time-format/locale/*.json', dest: libPath+'d3-time-format' }
];

var staticDeps = [
    { srcGlob: 'node_modules/axios/dist/axios.min.js', dest: libPath+'axios' },
    { srcGlob: 'node_modules/selectize/dist/css/selectize.css', dest: libPath+'selectize' }
];

var graphiqlJs = [
    'node_modules/whatwg-fetch/fetch.js',
    'node_modules/react/umd/react.production.min.js',
    'node_modules/react-dom/umd/react-dom.production.min.js',
    'node_modules/graphiql/graphiql.js',
    'src/web/assets/graphiql/src/graphiql-init.js',
];

var graphiqlCss = [
    'node_modules/graphiql/graphiql.css',
    'src/web/assets/graphiql/src/graphiql.scss',
];

var graphiqlDist = 'src/web/assets/graphiql/dist';

var vueJs = [
    'node_modules/vue/dist/vue.min.js',
    'node_modules/vue-router/dist/vue-router.min.js',
    'node_modules/vuex/dist/vuex.min.js',
    'node_modules/vue-autosuggest/dist/vue-autosuggest.js',
];

gulp.task('graphiql-js', function() {
    return gulp.src(graphiqlJs)
        .pipe(gulpif(/(fetch\.js|graphiql-init\.js)$/, uglify()))
        .pipe(concat('graphiql.js'))
        .pipe(gulp.dest(graphiqlDist));
});

gulp.task('graphiql-css', function() {
    return gulp.src(graphiqlCss)
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('graphiql.css'))
        .pipe(gulp.dest(graphiqlDist));
});

gulp.task('graphiql', ['graphiql-js', 'graphiql-css']);

gulp.task('vue', function() {
    return gulp.src(vueJs)
        .pipe(concat('vue.js'))
        .pipe(gulp.dest(libPath+'vue'))
});

gulp.task('static-deps', function() {
    var streams = [];
    staticDeps.forEach(function(dep) {
        streams.push(
            gulp.src(dep.srcGlob)
                .pipe(gulp.dest(dep.dest))
        );
    });
    return es.merge(streams);
});

gulp.task('deps', ['graphiql', 'vue', 'static-deps'], function() {
    var streams = [];

    // Minify & move the JS deps
    jsDeps.forEach(function(dep) {
        streams.push(
            gulp.src(dep.srcGlob)
                //.pipe(gulp.dest(dest))
                .pipe(sourcemaps.init())
                .pipe(uglify())
                //.pipe(rename({ suffix: '.min' }))
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

gulp.task('sass', function() {
    return gulp.src('node_modules/craftcms-sass/src/_mixins.scss')
        .pipe(gulp.dest('lib/craftcms-sass'));
});
