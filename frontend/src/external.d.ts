import type { ActiveToast, ToastProps } from 'vue-toast-notification';

interface IWindow extends Window {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  bootstrap: any;
}

declare let window: IWindow;

declare module 'vue-toast-notification' {
  function useToast(globalProps?: Partial<ToastProps>): {
    open(options: string | Partial<ToastProps>): ActiveToast;
    clear(): void;
    success(message: string, options?: Partial<ToastProps>): ActiveToast;
    error(message: string, options?: Partial<ToastProps>): ActiveToast;
    info(message: string, options?: Partial<ToastProps>): ActiveToast;
    warning(message: string, options?: Partial<ToastProps>): ActiveToast;
    default(message: string, options?: Partial<ToastProps>): ActiveToast;
  };
}

declare module 'vue/types/vue' {
  import type { VueFinalModalProperty } from 'vue-final-modal';

  interface Vue {
    $vfm: VueFinalModalProperty;
  }
}
