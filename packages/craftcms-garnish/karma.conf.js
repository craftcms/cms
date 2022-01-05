module.exports = function(config) {
  config.set({

    browsers: ['Chrome', 'Firefox'],
    frameworks: ['browserify', 'jasmine'],
    reporters: ['progress', 'kjhtml'],

    preprocessors: {
      'dist/*.js': ['browserify']
    },

    files: [
      'bower_components/jquery/dist/jquery.js',
      'bower_components/velocity/velocity.js',
      'bower_components/element-resize-detector/dist/element-resize-detector.js',
      'dist/garnish.js',
      'test/**/*.js'
    ],

    browserify: {
      debug: true
    },
  })
}
