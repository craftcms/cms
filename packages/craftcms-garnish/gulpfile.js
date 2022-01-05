var gulp = require('gulp'),
    concat = require('gulp-concat'),
    insert = require('gulp-insert'),
    uglify = require('gulp-uglify'),
    watch = require('gulp-watch'),
    sourcemaps = require('gulp-sourcemaps'),
    notify = require('gulp-notify'),
    plumber = require('gulp-plumber'),
    util = require('gulp-util'),
    yargs = require('yargs'),
    jsdoc = require('gulp-jsdoc3');

var Server = require('karma').Server;

var defaultDest = './dist/';
var docsDest = './docs/';

var defaultVersion = '0.1';

var buildGlob = [
    'lib/*.js',
    'src/Garnish.js',
    'src/Base*.js',
    'src/*.js'
];

//error notification settings for plumber
var plumberErrorHandler = function(err) {

    notify.onError({
        title: "Garnish",
        message:  "Error: <%= error.message %>",
        sound:    "Beep"
    })(err);

    console.log( 'plumber error!' );

    this.emit('end');
};

gulp.task('build', buildTask);
gulp.task('watch', watchTask);
gulp.task('coverage', coverageTask);
gulp.task('test', ['unittest']);
gulp.task('unittest', unittestTask);
gulp.task('docs', docsTask);

gulp.task('default', ['build', 'watch']);

function buildTask()
{
    // Allow overriding the dest directory
    // > gulp build --dest=/path/to/dest
    // > gulp build -d=/path/to/dest
    var dest = yargs.argv.dest || yargs.argv.d || defaultDest;

    // Allow overriding the version
    // > gulp build --version=1.0.0
    // > gulp build -v=1.0.0
    var version = yargs.argv.version || yargs.argv.v || defaultVersion;

    var docBlock = "/**\n" +
        " * Garnish UI toolkit\n" +
        " *\n" +
        " * @copyright 2013 Pixel & Tonic, Inc.. All rights reserved.\n" +
        " * @author    Brandon Kelly <brandon@pixelandtonic.com>\n" +
        " * @version   " + version + "\n" +
        " * @license   MIT\n" +
        " */\n";

    var jqueryOpen = "(function($){\n" +
        "\n";

    var jqueryClose = "\n" +
        "})(jQuery);\n";

    return gulp.src(buildGlob, { base: dest })
        .pipe(plumber({ errorHandler: plumberErrorHandler }))
        .pipe(sourcemaps.init())
        .pipe(concat('garnish.js'))
        .pipe(insert.prepend(jqueryOpen))
        .pipe(insert.append(jqueryClose))
        .pipe(insert.prepend(docBlock))
        .pipe(gulp.dest(dest))
        .pipe(uglify())
        .pipe(concat('garnish.min.js'))
        .pipe(insert.prepend(docBlock))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(dest));
}

function watchTask()
{
    if (util.env.test) {
        return gulp.watch(['src/**', 'test/**'], ['build', 'unittest']);
    }

    return gulp.watch('src/**', ['build']);
}

function coverageTask(done)
{
    new Server({
        configFile: __dirname + '/karma.coverage.conf.js',
        singleRun: true
    }, done).start();
}

function unittestTask(done)
{
    new Server({
        configFile: __dirname + '/karma.conf.js',
        singleRun: true
    }, done).start();
}

function docsTask(cb)
{
    var dest = yargs.argv.dest || yargs.argv.d || docsDest;

    gulp
        .src(['src/*.js'], {read: false})
        .pipe(jsdoc(cb));
}
