import {defineConfig, loadEnv} from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';

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
    server,

    build: {
      assetsDir: '',
      emptyOutDir: true,
      rollupOptions: {
        output: {
          entryFileNames: '[name].js',
          chunkFileNames: '[name].js',
          assetFileNames: '[name].[ext]',
        },
      },
    },

    plugins: [
      laravel({
        input: ['resources/js/cp.js', 'resources/css/cp.css'],
        publicDirectory: 'resources',
        refresh: true,
        detectTls: env.VITE_DETECT_TLS ?? undefined,
      }),
    ],
  };
});
