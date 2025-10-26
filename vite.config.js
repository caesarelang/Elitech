import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/app_ppic.js',
        'resources/js/app_produksi.js',
      ],
      refresh: true,
    }),
    vue(),
  ],
  publicDir: 'public',
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
})
