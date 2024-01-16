const path = require('path');
const fs = require('fs');
const tff2woff2 = require('ttf2woff2');

class Ttf2Woff2Plugin {
  static defaultOptions = {
    src: './src/fonts',
  };

  constructor(options = {}) {
    this.options = {
      ...Ttf2Woff2Plugin.defaultOptions,
      ...options,
    };

    this.src = path.resolve(__dirname, this.options.src);
  }

  apply(compiler) {
    compiler.hooks.beforeRun.tap('Ttf2Woff2Plugin', () => {
      const files = fs.readdirSync(this.src);

      files.forEach((file) => {
        if (path.extname(file) === '.ttf') {
          const input = fs.readFileSync(path.resolve(this.src, file));
          fs.writeFileSync(
            path.resolve(this.src, file.replace('.ttf', '.woff2')),
            tff2woff2(input)
          );
        }
      });
    });
  }
}

module.exports = Ttf2Woff2Plugin;
