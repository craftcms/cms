const path = require('path');
const fs = require('fs');
const tff2woff2 = require('ttf2woff2');

class Ttf2Woff2Plugin {
  constructor(options = {}) {
    this.options = {
      src: './src/fonts',
      dest: './dist/fonts',
      ...options,
    };

    this.src = path.resolve(__dirname, this.options.src);
    this.dest = path.resolve(__dirname, this.options.dest);
  }

  apply(compiler) {
    compiler.hooks.done.tap('Ttf2Woff2Plugin', () => {
      const files = fs.readdirSync(this.src);

      files.forEach((file) => {
        if (path.extname(file) === '.ttf') {
          const input = fs.readFileSync(path.resolve(this.src, file));
          fs.writeFileSync(
            path.resolve(this.dest, file.replace('.ttf', '.woff2')),
            tff2woff2(input)
          );
        }
      });
    });
  }
}

module.exports = Ttf2Woff2Plugin;
