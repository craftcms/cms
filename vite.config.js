import {defineConfig, loadEnv} from 'vite';
import laravel from 'laravel-vite-plugin';
import inertia from '@inertiajs/vite';
import fs from 'fs';
import vue from '@vitejs/plugin-vue';
import tsconfigPaths from 'vite-tsconfig-paths';
import tailwindcss from '@tailwindcss/vite';
import {wayfinder} from '@laravel/vite-plugin-wayfinder';

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

    resolve: {
      alias: {
        vue: 'vue/dist/vue.esm-bundler.js',
      },
    },

    build: {
      emptyOutDir: true,
      rollupOptions: {
        output: {
          entryFileNames: '[name].js',
          chunkFileNames: '[name].js',
          assetFileNames: 'assets/[name].[ext]',
        },
      },
    },

    plugins: [
      tailwindcss(),
      tsconfigPaths(),
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
          'resources/js/cp.js',
          'resources/js/legacy.js',
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
      inertia(),
    ],
  };
});
