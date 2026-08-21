import {defineConfig, loadEnv, lazyPlugins} from 'vite-plus';
import laravel from 'laravel-vite-plugin';
import inertia from '@inertiajs/vite';
import {exec} from 'child_process';
import fs from 'fs';
import path from 'path';
import {promisify} from 'util';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import {wayfinder} from '@laravel/vite-plugin-wayfinder';

const execAsync = promisify(exec);

const MIME_TYPES = {
  '.js': 'application/javascript',
  '.mjs': 'application/javascript',
  '.css': 'text/css',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
  '.map': 'application/json',
};

function serveResourcesLegacy() {
  return {
    name: 'serve-resources-legacy',
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        if (!req.url?.startsWith('/legacy/')) return next();
        const filePath = path.resolve(
          'cms-assets/resources' + req.url.split('?')[0]
        );
        if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
          const ext = path.extname(filePath);
          res.writeHead(200, {
            'Content-Type': MIME_TYPES[ext] ?? 'application/octet-stream',
          });
          fs.createReadStream(filePath).pipe(res);
          return;
        }
        next();
      });
    },
  };
}

/** Everything the PHP-backed codegen plugins write into resources/js. */
const PHP_GENERATED_PATHS = [
  'resources/js/generated',
  'resources/js/actions',
  'resources/js/routes',
  'resources/js/wayfinder',
];

/**
 * Whether to bundle the PHP-generated sources already on disk rather than
 * regenerating them.
 *
 * Both the TypeScript transformer and Wayfinder shell out to
 * `./vendor/bin/testbench`, so any build that regenerates needs a PHP runtime
 * matching Composer's resolved platform — under an older PHP the whole of
 * `vendor` parses as syntax errors. Builds that only bundle output someone
 * else generated (the Storybook deploy jobs restore these paths from the
 * build-assets cache) opt out and skip installing PHP altogether.
 */
function skipPhpCodegen(env) {
  if (
    !['1', 'true'].includes(
      String(env.CRAFT_SKIP_PHP_CODEGEN ?? '').toLowerCase()
    )
  ) {
    return false;
  }

  // Opting out only makes sense when that output is actually present. Bundling
  // without it yields a broken build that surfaces as unrelated-looking module
  // resolution errors, so fail loudly and early instead.
  const missing = PHP_GENERATED_PATHS.filter(
    (dir) => !fs.existsSync(dir) || !fs.readdirSync(dir).length
  );

  if (missing.length) {
    throw new Error(
      'CRAFT_SKIP_PHP_CODEGEN is set, but these are missing or empty:\n' +
        missing.map((dir) => `  - ${dir}`).join('\n') +
        '\n\nGenerate them first with `vp run generate:types` and ' +
        '`vp run generate:wayfinder`\n(both need PHP), or unset the variable ' +
        'to generate them as part of this build.'
    );
  }

  return true;
}

function typescriptTransformer(skip = false) {
  const command = './vendor/bin/testbench typescript:transform';

  let context;

  async function runCommand() {
    try {
      await execAsync(command);
    } catch (error) {
      context.error('Error generating TypeScript transformer types: ' + error);
    }

    context.info('Types generated for TypeScript transformer');
  }

  function shouldRun(file, root) {
    const relativePath = path.relative(root, file).replaceAll(path.sep, '/');

    return (
      (relativePath.startsWith('src/') && relativePath.endsWith('.php')) ||
      relativePath ===
        'workbench/app/Providers/TypeScriptTransformerServiceProvider.php' ||
      (relativePath.startsWith('workbench/app/TypeScript/') &&
        relativePath.endsWith('.php'))
    );
  }

  return {
    name: 'craftcms-typescript-transformer',
    enforce: 'pre',
    buildStart() {
      context = this;

      if (skip) {
        context.info(
          'Reusing existing TypeScript transformer types (CRAFT_SKIP_PHP_CODEGEN)'
        );

        return;
      }

      return runCommand();
    },
    async handleHotUpdate({file, server}) {
      context = this;

      if (shouldRun(file, server.config.root)) {
        await runCommand();
      }
    },
  };
}

export default defineConfig(({mode}) => {
  const env = loadEnv(mode, process.cwd(), '');
  const publicDirectory = 'cms-assets/resources';
  const skipCodegen = skipPhpCodegen(env);

  let server = undefined;
  if (env.APP_URL) {
    const url = new URL(env.APP_URL || 'http://localhost');
    const host = url.hostname;

    server = url.hostname.includes('.ddev.site')
      ? {
          host,
          cors: url.toString(),
          hmr: {host},
          https: {
            key: fs.readFileSync(env.VITE_SERVER_HTTPS_PATH_KEY),
            cert: fs.readFileSync(env.VITE_SERVER_HTTPS_PATH_CERT),
          },
        }
      : undefined;
  }

  return {
    lint: {
      plugins: ['oxc', 'typescript', 'unicorn', 'react', 'vue'],
      categories: {
        correctness: 'warn',
      },
      env: {
        browser: true,
        builtin: true,
      },
      ignorePatterns: [
        '**/*',
        '!resources/js/**',
        '!workbench/resources/js/**',
        'resources/build/**',
        'resources/legacy/**',
        'resources/js/**/fixtures/**',
      ],
      overrides: [
        {
          files: ['resources/js/**/*.{ts,vue}'],
          rules: {
            'no-undef': 'off',
            'vue/require-default-prop': 'off',
            'typescript/no-explicit-any': 'off',
          },
          globals: {
            Craft: 'readonly',
            Garnish: 'readonly',
          },
        },
      ],
      options: {
        typeAware: true,
        typeCheck: true,
      },
      jsPlugins: [
        {
          name: 'vite-plus',
          specifier: 'vite-plus/oxlint-plugin',
        },
      ],
    },
    staged: {
      'yii2-adapter/**/*.php':
        './yii2-adapter/vendor/bin/ecs check --config ./yii2-adapter/ecs.php --ansi --fix',
      '!(yii2-adapter)/**/*.php': ['./vendor/bin/rector', './vendor/bin/pint'],
      'yii2-adapter/**/*.scss':
        'stylelint --fix --allow-empty-input -c ./yii2-adapter/.stylelintrc.json',
      '!(yii2-adapter)/**/*.scss': 'stylelint --fix --allow-empty-input',
      '!(yii2-adapter)/**/*.{html,json,css,scss}': 'vp fmt --write',
      'resources/js/**/*.{ts,vue}': 'vp check --fix',
    },
    fmt: {
      singleQuote: true,
      bracketSpacing: false,
      vueIndentScriptAndStyle: true,
      trailingComma: 'es5',
      printWidth: 80,
      sortPackageJson: false,
      ignorePatterns: [
        '*.md',
        '*.php',
        'composer.lock',
        '**/dist/*',
        'vendor/*',
        '.ddev/*',
        'resources/build/*',
        'resources/public/*',
        'resources/js/actions/*',
        'resources/js/routes/*',
        'resources/js/wayfinder/*',
        'yii2-adapter/*',
        'tests-playwright/.authentication.json',
      ],
    },
    base: './',
    server,

    publicDir: 'resources/public',

    resolve: {
      tsconfigPaths: true,
      alias: {
        vue: 'vue/dist/vue.esm-bundler.js',
      },
      dedupe: ['@awesome.me/webawesome', 'lit'],
    },

    // Vitest picks up this config's resolve/aliases. The unit tests colocated
    // under resources/js (e.g. modules/auth/elevated-session) need a DOM
    // environment; the craftcms-ui package has its own vitest projects.
    test: {
      environment: 'happy-dom',
      include: [
        'resources/js/**/*.test.ts',
        'yii2-adapter/resources/js/**/*.test.ts',
      ],
    },

    build: {
      emptyOutDir: true,
    },

    optimizeDeps: {
      include: ['lit'],
      // WebAwesome must NOT be pre-bundled. It's imported through many deep
      // entry points (dist/components/*/*.js), and esbuild's dep optimizer
      // duplicates their shared component modules across chunks — which makes
      // custom elements like `wa-icon` get registered twice
      // (NotSupportedError: "wa-icon" has already been used). Serving it as
      // native ESM means each module file loads once, so each element is
      // defined once.
      exclude: ['@awesome.me/webawesome'],
    },

    plugins: lazyPlugins(() => [
      serveResourcesLegacy(),
      tailwindcss(),
      typescriptTransformer(skipCodegen),
      // Wayfinder has no opt-out of its own, and its only hooks are buildStart
      // and handleHotUpdate — both of which shell out to PHP — so drop it
      // entirely rather than let it regenerate what the cache already holds.
      ...(skipCodegen
        ? []
        : [
            wayfinder({
              path: 'resources/js',
              command: './vendor/bin/testbench wayfinder:generate',
            }),
          ]),
      vue({
        script: {
          // vue-tsc resolves the ambient `CraftCms.*` namespace (generated by
          // the typescript transformer) through tsconfig, but compiler-sfc's
          // build-time defineProps<> resolver only sees global declarations
          // from files listed here.
          globalTypeFiles: [path.resolve('resources/js/generated/types.d.ts')],
        },
        template: {
          compilerOptions: {
            isCustomElement: (tag) => tag.includes('-'),
          },
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),
      // Skipped under Vitest, which builds its own Vite server from this same
      // config. The Laravel plugin owns the hot file — writing it when a server
      // starts and deleting it when one closes — so leaving it in means every
      // test run deletes the hot file out from under a running `npm run dev`.
      // The CP then silently falls back to stale built assets, which looks like
      // "my changes aren't showing up" rather than anything to do with tests.
      ...(process.env.VITEST
        ? []
        : [
            laravel({
              input: [
                'resources/js/cp.ts',
                'resources/js/legacy.ts',
                'resources/css/cp.css',
                'workbench/resources/js/cp.ts',
              ],
              publicDirectory,
              hotFile: `${publicDirectory}/hot`,
              refresh: [
                // The defaults
                'resources/lang/**',
                'resources/views/**',
                'routes/**',
                // Plus ours
                'resources/templates/**',
              ],
              detectTls: env.VITE_DETECT_TLS ?? undefined,
            }),
          ]),
      inertia({
        ssr: false,
      }),
    ]),
  };
});
