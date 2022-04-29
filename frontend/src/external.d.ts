declare module 'vue-toast-notification' {
  import type { ToastPluginApi } from 'vue-toast-notification';
  export const useToast: ToastPluginApi;
}

declare module 'vue/types/vue' {
  import type { VueFinalModalProperty } from 'vue-final-modal';

  interface Vue {
    $vfm: VueFinalModalProperty;
  }
}
