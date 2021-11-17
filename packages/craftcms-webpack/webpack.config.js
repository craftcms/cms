/*jshint esversion: 6 */
/* globals __dirname, module, require, process */
'use strict';
const webpack = require('webpack');
const path = require('path');
const fs = require('fs');

// Constants
const ASSETS_PATH = path.join(__dirname, '../../src/web/assets');

// Setup configs
let configs = [];

// Import asset configs
let assetWebPackConfigFiles = fs.readdirSync(ASSETS_PATH).filter(f => {
    let dirPath = path.join(ASSETS_PATH, f);
    let filePath = path.join(dirPath, 'webpack.config.js');
    return fs.statSync(dirPath).isDirectory() && fs.existsSync(filePath);
}).map(p => path.join(ASSETS_PATH, p, 'webpack.config'));

assetWebPackConfigFiles.forEach(asset => {
    let assetConfig = require(asset);
    configs.push(assetConfig);
});

module.exports = configs;
