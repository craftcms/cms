/*
var elixir = require('laravel-elixir');

require('laravel-elixir-vueify');
require('laravel-elixir-browserify-official');

elixir(function(mix) {
    mix.browserify('app.js');
});
*/

var elixir = require('laravel-elixir');
elixir.config.publicPath = "dist";

require('laravel-elixir-vue-2');
require('laravel-elixir-webpack-official');

elixir(function(mix) {
    mix.webpack('app.js'); // resources/assets/js/app.js
})