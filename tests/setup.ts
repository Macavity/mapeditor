import { config } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach } from 'vitest';

// Set up Pinia for testing
beforeEach(() => {
  const pinia = createPinia();
  setActivePinia(pinia);
});

// Global mocks
config.global.mocks = {
  $t: (key: string) => key, // For i18n if you use it
};

// Global stubs
config.global.stubs = {
  // Add any global component stubs here
  'router-link': true,
  'router-view': true,
};
