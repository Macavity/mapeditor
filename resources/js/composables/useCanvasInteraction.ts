import { useSaveManager } from '@/composables/useSaveManager';
import { useEditorStore } from '@/stores/editorStore';
import { EditorTool } from '@/types/EditorTool';

export function useCanvasInteraction() {
    const editorStore = useEditorStore();
    const saveManager = useSaveManager();

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

    function canEraseTiles(): boolean {
        return !!(editorStore.activeLayer && editorStore.isEraseToolActive);
    }

    function canFillTiles(): boolean {
        return !!(
            editorStore.activeLayer &&
            editorStore.brushSelection.tilesetUuid &&
            editorStore.brushSelection.backgroundImage &&
            editorStore.isFillToolActive
        );
    }

    function handleCanvasClick(event: MouseEvent): { success: boolean; action: 'draw' | 'erase' | 'fill' | 'none'; tileExists?: boolean } {
        const position = calculateTilePosition(event);
        if (!position) {
            return { success: false, action: 'none' };
        }

        if (editorStore.activeTool === EditorTool.DRAW && canPlaceTiles()) {
            // Place tiles (single or multi-tile)
            editorStore.placeTiles(position.tileX, position.tileY);
            saveManager.markAsChanged();
            return { success: true, action: 'draw' };
        } else if (editorStore.activeTool === EditorTool.ERASE && canEraseTiles()) {
            // Erase tile
            const tileExists = editorStore.eraseTile(position.tileX, position.tileY);
            if (tileExists) {
                saveManager.markAsChanged();
            }
            return { success: true, action: 'erase', tileExists };
        } else if (editorStore.activeTool === EditorTool.FILL && canFillTiles()) {
            // Fill connected tiles
            const filled = editorStore.fillTiles(position.tileX, position.tileY);
            if (filled) {
                saveManager.markAsChanged();
            }
            return { success: true, action: 'fill', tileExists: filled };
        } else {
            return { success: false, action: 'none' };
        }
    }

    function getTileAtPosition(event: MouseEvent): { tileX: number; tileY: number; hasTitle: boolean } | null {
        const position = calculateTilePosition(event);
        if (!position) return null;

        const tile = editorStore.getTileAt(position.tileX, position.tileY);
        return {
            tileX: position.tileX,
            tileY: position.tileY,
            hasTitle: !!tile,
        };
    }

    return {
        calculateTilePosition,
        canPlaceTiles,
        canEraseTiles,
        canFillTiles,
        handleCanvasClick,
        getTileAtPosition,
    };
}
