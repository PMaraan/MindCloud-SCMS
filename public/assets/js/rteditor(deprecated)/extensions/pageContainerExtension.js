// /public/assets/js/rteditor/extensions/pageContainerExtension.js
// TipTap extension wrapper that installs the lightweight page wrapper helper
// (attachPageContainer) when the editor is created and ensures cleanup on destroy.

import { Extension } from '@tiptap/core';
import attachPageContainer from './pageContainer.js';

export default Extension.create({
  name: 'pageContainerWrapper',

  // Optionally accept options from editor init by reading editor.options
  // We support an optional editor.options.pageContainerGetConfig function.
  onCreate() {
    try {
      const getPageConfig = (typeof this.editor.options.pageContainerGetConfig === 'function')
        ? this.editor.options.pageContainerGetConfig
        : (window.__RT_getPageConfig || null);

      // Keep cleanup reference on the editor instance for debug/tests
      this.editor.__rt_pageContainerCleanup = attachPageContainer(this.editor, {
        getPageConfig,
      });
    } catch (e) {
      console.warn('[pageContainerExtension] attach failed', e);
    }
  },

  onDestroy() {
    try {
      const cleanup = this.editor && this.editor.__rt_pageContainerCleanup;
      if (typeof cleanup === 'function') {
        cleanup();
        delete this.editor.__rt_pageContainerCleanup;
      }
    } catch (e) {
      console.warn('[pageContainerExtension] cleanup failed', e);
    }
  }
});
