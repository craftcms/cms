import {defineConfig, loadEnv} from 'vite';
import laravel from 'laravel-vite-plugin';
import inertia from '@inertiajs/vite';
import fs from 'fs';
import path from 'path';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import {wayfinder} from '@laravel/vite-plugin-wayfinder';

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
          res.writeHead(200, {'Content-Type': MIME_TYPES[ext] ?? 'application/octet-stream'});
          fs.createReadStream(filePath).pipe(res);
          return;
        }
        next();
      });
    },
  };
}

export default defineConfig(({mode}) => {
  const env = loadEnv(mode, process.cwd(), '');
  const url = new URL(env.APP_URL);
  const host = url.hostname || 'localhost';

  const server = url.hostname.includes('.ddev.site')
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
      include: ['@awesome.me/webawesome', 'lit'],
    },

    plugins: [
      serveResourcesLegacy(),
      tailwindcss(),
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
        ssr: false
      }),
    ],
  };
});
