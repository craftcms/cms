/* jshint esversion: 8 */
/* globals module, require, __dirname */
const {getConfig} = require('@craftcms/webpack');

/**
 * Generates highlight.css from TanStack Highlight’s GitHub Light theme.
 */
class HighlightThemePlugin {
  apply(compiler) {
    const {Compilation, sources} = compiler.webpack;
    compiler.hooks.thisCompilation.tap(
      'HighlightThemePlugin',
      (compilation) => {
        compilation.hooks.processAssets.tapPromise(
          {
            name: 'HighlightThemePlugin',
            stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
          },
          async () => {
            const {createThemeCss} = await import('@tanstack/highlight/theme');
            const {githubLightTheme} =
              await import('@tanstack/highlight/themes/github-light');
            compilation.emitAsset(
              'highlight.css',
              new sources.RawSource(createThemeCss({light: githubLightTheme}))
            );
          }
        );
      }
    );
  }
}

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {highlight: './highlight.js'},
    plugins: [new HighlightThemePlugin()],
  },
});
