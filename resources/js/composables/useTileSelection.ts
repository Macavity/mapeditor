import type { TileSelection } from '@/types/BrushSelection';
import { createSingleTileSelection, isSingleTileSelection } from '@/types/BrushSelection';
import { ref } from 'vue';

export function useTileSelection() {
    // Selection state
    const currentSelection = ref<TileSelection | null>(null);
    const isDragging = ref(false);
    const dragStart = ref<{ tileX: number; tileY: number } | null>(null);

    // Calculate tile coordinates from mouse event
    function calculateTileCoordinates(
        event: MouseEvent,
        containerElement: HTMLElement,
        tileWidth: number,
        tileHeight: number,
    ): { tileX: number; tileY: number } | null {
        const rect = containerElement.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        const tileX = Math.floor(x / tileWidth);
        const tileY = Math.floor(y / tileHeight);

        return { tileX, tileY };
    }

    // Create a multi-tile selection from start and end coordinates
    function createMultiTileSelection(
        startTileX: number,
        startTileY: number,
        endTileX: number,
        endTileY: number,
        tileWidth: number,
        tileHeight: number,
    ): TileSelection {
        const minTileX = Math.min(startTileX, endTileX);
        const minTileY = Math.min(startTileY, endTileY);
        const maxTileX = Math.max(startTileX, endTileX);
        const maxTileY = Math.max(startTileY, endTileY);

        return {
            x: minTileX * tileWidth,
            y: minTileY * tileHeight,
            tileX: minTileX,
            tileY: minTileY,
            width: (maxTileX - minTileX + 1) * tileWidth,
            height: (maxTileY - minTileY + 1) * tileHeight,
            startTileX: minTileX,
            startTileY: minTileY,
            endTileX: maxTileX,
            endTileY: maxTileY,
        };
    }

    // Update the current selection during drag
    function updateDragSelection(endTileX: number, endTileY: number, tileWidth: number, tileHeight: number): void {
        if (!dragStart.value) return;

        currentSelection.value = createMultiTileSelection(dragStart.value.tileX, dragStart.value.tileY, endTileX, endTileY, tileWidth, tileHeight);
    }

    // Start drag selection
    function startDragSelection(tileX: number, tileY: number, tileWidth: number, tileHeight: number): void {
        isDragging.value = true;
        dragStart.value = { tileX, tileY };

        // Create initial single tile selection
        currentSelection.value = createSingleTileSelection(tileX, tileY, tileWidth, tileHeight);
    }

    // Update drag selection during mouse move
    function continueDragSelection(tileX: number, tileY: number, tileWidth: number, tileHeight: number): void {
        if (!isDragging.value || !dragStart.value) return;

        updateDragSelection(tileX, tileY, tileWidth, tileHeight);
    }

    // Complete drag selection
    function completeDragSelection(tileX: number, tileY: number, tileWidth: number, tileHeight: number): TileSelection | null {
        if (!isDragging.value || !dragStart.value) {
            return null;
        }

        // Check if this was just a click (no drag)
        const isClick = dragStart.value.tileX === tileX && dragStart.value.tileY === tileY;

        if (isClick) {
            // Single tile selection
            currentSelection.value = createSingleTileSelection(tileX, tileY, tileWidth, tileHeight);
        } else {
            // Multi-tile selection
            updateDragSelection(tileX, tileY, tileWidth, tileHeight);
        }

        isDragging.value = false;
        return currentSelection.value;
    }

    // Clear all selections
    function clearSelection(): void {
        currentSelection.value = null;
        isDragging.value = false;
        dragStart.value = null;
    }

    return {
        // State
        currentSelection,
        isDragging,

        // Methods
        calculateTileCoordinates,
        startDragSelection,
        continueDragSelection,
        completeDragSelection,
        clearSelection,
        isSingleTileSelection,
    };
}
