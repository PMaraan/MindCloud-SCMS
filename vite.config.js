import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig(({ command }) => {
  // For dev we want a simple root/base so imports are easy to test.
  // For build we still write output to public/assets/js/rteditor
  const isDev = command === 'serve';

  return {
    root: process.cwd(),
    base: '/',                       // keep dev simple; production bundle will still be placed into public/
    publicDir: 'public',             // ensure Vite serves public/ statics
    server: {
      port: 5173,
      strictPort: false,             // change port if 5173 is taken
    },
    resolve: {
      // prevent duplicate ProseMirror/Tiptap copies later when building
      // (we'll expand this if you still see duplicate runtime errors)
      dedupe: ['prosemirror-model', 'prosemirror-state', 'prosemirror-view', '@tiptap/core']
    },
    build: {
      outDir: 'public/assets/js/rteditor', // final bundle destination
      emptyOutDir: false,                  // DON'T delete the folder on build
      rollupOptions: {
        input: path.resolve(__dirname, 'public/assets/js/rteditor/collab-editor.js'),
        output: {
          entryFileNames: 'collab-editor.bundle.js',
          format: 'iife',
          name: 'RTEditor',
        }
      }
    }
  };
});
