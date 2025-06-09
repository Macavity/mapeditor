import { useEditorStore } from '@/stores/editorStore';

export function useCanvasInteraction() {
    const editorStore = useEditorStore();

    function calculateTilePosition(event: MouseEvent): { tileX: number; tileY: number } | null {
        const target = event.currentTarget as HTMLElement;
        const rect = target.getBoundingClientRect();

        // Calculate mouse position relative to canvas
        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        // Get tile dimensions from the map
        const tileWidth = editorStore.mapMetadata.tileWidth;
        const tileHeight = editorStore.mapMetadata.tileHeight;

        // Calculate tile coordinates (snap to grid)
        const tileX = Math.floor(mouseX / tileWidth);
        const tileY = Math.floor(mouseY / tileHeight);

        // Check if position is within bounds
        if (tileX < 0 || tileY < 0 || tileX >= editorStore.mapMetadata.width || tileY >= editorStore.mapMetadata.height) {
            return null;
        }

        return { tileX, tileY };
    }

    function canPlaceTiles(): boolean {
        return !!(
            editorStore.activeLayer &&
            editorStore.brushSelection.tilesetUuid &&
            editorStore.brushSelection.backgroundImage &&
            editorStore.isDrawToolActive
        );
    }

    function handleCanvasClick(event: MouseEvent): boolean {
        if (!canPlaceTiles()) {
            return false;
        }

        const position = calculateTilePosition(event);
        if (!position) {
            return false;
        }

        editorStore.placeTiles(position.tileX, position.tileY);

        return true;
    }

    return {
        calculateTilePosition,
        canPlaceTiles,
        handleCanvasClick,
    };
}
