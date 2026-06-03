import {defineConfig, loadEnv} from 'vite';
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
        const filePath = path.resolve('resources' + req.url.split('?')[0]);
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

function typescriptTransformer() {
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

  let server = undefined;
  if (env.APP_URL) {
    const url = new URL(env.APP_URL|| 'http://localhost');
    const host = url.hostname ;

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

    plugins: [
      serveResourcesLegacy(),
      tailwindcss(),
      typescriptTransformer(),
      wayfinder({
        path: 'resources/js',
        command: './vendor/bin/testbench wayfinder:generate',
      }),
      vue({
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
      laravel({
        input: [
          'resources/js/cp.ts',
          'resources/js/legacy.ts',
          'resources/css/cp.css',
        ],
        publicDirectory: 'resources',
        hotFile: 'resources/hot',
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
      inertia({
        ssr: false,
      }),
    ],
  };
});
