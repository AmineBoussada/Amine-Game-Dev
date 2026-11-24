import { defineConfig } from 'vite';

// Builds the WordPress theme script: theme-src/main.ts → wordpress/nova-immobilier/assets/js/theme.js
export default defineConfig({
  build: {
    outDir: 'wordpress/nova-immobilier/assets/js',
    emptyOutDir: false,
    lib: { entry: 'theme-src/main.ts', formats: ['iife'], name: 'NovaTheme', fileName: () => 'theme.js' },
    minify: true,
  },
});
