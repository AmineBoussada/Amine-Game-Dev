import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// `vite build --mode preview-single` produces one self-contained JS/CSS bundle
// (no code-splitting) used to generate a single-file preview page.
export default defineConfig(({ mode }) => ({
  plugins: [react()],
  build:
    mode === 'preview-single'
      ? {
          outDir: 'dist-preview',
          cssCodeSplit: false,
          assetsInlineLimit: 100_000_000,
          rollupOptions: { output: { inlineDynamicImports: true } },
        }
      : {},
}));
