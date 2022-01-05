module.exports = function(config) {
  config.set({

    browsers: ['Firefox'],
    frameworks: ['browserify', 'jasmine'],
    preprocessors: {
      'dist/garnish.js': ['browserify']
    },

    files: [
      'bower_components/jquery/dist/jquery.js',
      'bower_components/velocity/velocity.js',
      'bower_components/element-resize-detector/dist/element-resize-detector.js',
      'dist/garnish.js',
      'test/**/*.js'
    ],

    browserify: {
      debug: true,
      transform: [['browserify-istanbul', {
        instrumenterConfig: {
          embed: true
        }
      }]]
    },

    reporters: ['progress', 'coverage'],

    coverageReporter: {
      dir: 'coverage/',
      reporters: [
        { type: 'html', subdir: 'report-html' },
        { type: 'lcovonly', subdir: '.', file: 'lcov.info' }
      ]
    }
  })
}
