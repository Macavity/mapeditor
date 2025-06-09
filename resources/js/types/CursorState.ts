import type { ComputedRef, Ref } from 'vue';

export interface CursorState {
    showCursor: Ref<boolean>;
    cursorStyle: ComputedRef<{
        left: string;
        top: string;
        zIndex: number;
    }>;
    mapTileWidth: ComputedRef<number>;
    mapTileHeight: ComputedRef<number>;
}
