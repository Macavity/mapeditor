import { computed, ref, type Ref } from 'vue';

export function useBrushCursor(mapTileWidth: Ref<number>, mapTileHeight: Ref<number>) {
    const showBrush = ref(false);
    const brushPosition = ref({ x: 0, y: 0 });

    const cursorStyle = computed(() => ({
        left: brushPosition.value.x + 'px',
        top: brushPosition.value.y + 'px',
        zIndex: 99,
    }));

    function showCursor() {
        showBrush.value = true;
    }

    function hideCursor() {
        showBrush.value = false;
    }

    function updateCursorPosition(event: MouseEvent) {
        if (!showBrush.value) return;

        const target = event.currentTarget as HTMLElement;
        const rect = target.getBoundingClientRect();

        // Calculate mouse position relative to canvas
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        // Calculate tile coordinates (snap to grid)
        const tileX = Math.floor(mouseX / mapTileWidth.value);
        const tileY = Math.floor(mouseY / mapTileHeight.value);

        // Convert back to pixel coordinates (snapped to tile grid)
        const pixelX = tileX * mapTileWidth.value;
        const pixelY = tileY * mapTileHeight.value;

        // Update brush position
        brushPosition.value = { x: pixelX, y: pixelY };
    }

    return {
        showBrush,
        cursorStyle,
        showCursor,
        hideCursor,
        updateCursorPosition,
    };
}
