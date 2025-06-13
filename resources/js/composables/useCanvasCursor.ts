import { computed, ref, type Ref } from 'vue';

export function useCanvasCursor(mapTileWidth: Ref<number>, mapTileHeight: Ref<number>) {
    const showCursor = ref(false);
    const cursorPosition = ref({ x: 0, y: 0 });

    // Optimized cursor style with minimal reactivity overhead
    const cursorStyle = computed(() => ({
        left: cursorPosition.value.x + 'px',
        top: cursorPosition.value.y + 'px',
        zIndex: 99,
    }));

    function show() {
        showCursor.value = true;
    }

    function hide() {
        showCursor.value = false;
    }

    function updatePosition(event: MouseEvent) {
        // Early exit if cursor not shown - avoid expensive calculations
        if (!showCursor.value) return;

        const target = event.currentTarget as HTMLElement;
        const rect = target.getBoundingClientRect();

        // Calculate mouse position relative to canvas
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        // Cache tile dimensions to avoid reactive getter calls in hot path
        const tileW = mapTileWidth.value;
        const tileH = mapTileHeight.value;

        // Calculate tile coordinates (snap to grid) - optimized integer operations
        const tileX = Math.floor(mouseX / tileW);
        const tileY = Math.floor(mouseY / tileH);

        // Convert back to pixel coordinates (snapped to tile grid)
        const pixelX = tileX * tileW;
        const pixelY = tileY * tileH;

        // Only update if position actually changed to reduce reactive updates
        if (cursorPosition.value.x !== pixelX || cursorPosition.value.y !== pixelY) {
            cursorPosition.value = { x: pixelX, y: pixelY };
        }
    }

    return {
        showCursor,
        cursorStyle,
        show,
        hide,
        updatePosition,
    };
}
