import { useEditorStore } from '@/stores/editorStore';
import { EditorTool } from '@/types/EditorTool';
import { isFieldTypeLayer, isTileLayer } from '@/types/MapLayer';

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
        if (!editorStore.activeLayer) return false;

        const activeLayer = editorStore.layers.find((layer) => layer.uuid === editorStore.activeLayer);
        if (!activeLayer || !isTileLayer(activeLayer)) return false;

        return !!(editorStore.brushSelection.tilesetUuid && editorStore.brushSelection.backgroundImage && editorStore.isDrawToolActive);
    }

    function canPlaceFieldTypes(): boolean {
        if (!editorStore.activeLayer) return false;

        const activeLayer = editorStore.layers.find((layer) => layer.uuid === editorStore.activeLayer);
        if (!activeLayer || !isFieldTypeLayer(activeLayer)) return false;

        return editorStore.isDrawToolActive && editorStore.getSelectedFieldTypeId() !== null;
    }

    function canEraseTiles(): boolean {
        return !!(editorStore.activeLayer && editorStore.isEraseToolActive);
    }

    function canFillTiles(): boolean {
        if (!editorStore.activeLayer) return false;

        const activeLayer = editorStore.layers.find((layer) => layer.uuid === editorStore.activeLayer);
        if (!activeLayer || !isTileLayer(activeLayer)) return false;

        return !!(editorStore.brushSelection.tilesetUuid && editorStore.brushSelection.backgroundImage && editorStore.isFillToolActive);
    }

    function canFillFieldTypes(): boolean {
        if (!editorStore.activeLayer) return false;

        const activeLayer = editorStore.layers.find((layer) => layer.uuid === editorStore.activeLayer);
        if (!activeLayer || !isFieldTypeLayer(activeLayer)) return false;

        return editorStore.isFillToolActive && editorStore.getSelectedFieldTypeId() !== null;
    }

    function handleCanvasClick(event: MouseEvent): { success: boolean; action: 'draw' | 'erase' | 'fill' | 'none'; tileExists?: boolean } {
        const position = calculateTilePosition(event);
        if (!position) {
            return { success: false, action: 'none' };
        }

        const activeLayer = editorStore.layers.find((layer) => layer.uuid === editorStore.activeLayer);
        if (!activeLayer) {
            return { success: false, action: 'none' };
        }

        if (editorStore.activeTool === EditorTool.DRAW) {
            if (isTileLayer(activeLayer) && canPlaceTiles()) {
                // Place tiles (single or multi-tile)
                editorStore.placeItem(position.tileX, position.tileY);
                return { success: true, action: 'draw' };
            } else if (isFieldTypeLayer(activeLayer) && canPlaceFieldTypes()) {
                // Place field type using selected field type from store
                const selectedFieldTypeId = editorStore.getSelectedFieldTypeId();
                if (selectedFieldTypeId !== null) {
                    editorStore.placeItem(position.tileX, position.tileY, selectedFieldTypeId);
                    return { success: true, action: 'draw' };
                }
                return { success: false, action: 'none' };
            }
        } else if (editorStore.activeTool === EditorTool.ERASE && canEraseTiles()) {
            // Erase tile or field type
            const itemExists = editorStore.eraseItem(position.tileX, position.tileY);
            return { success: true, action: 'erase', tileExists: itemExists };
        } else if (editorStore.activeTool === EditorTool.FILL) {
            if (isTileLayer(activeLayer) && canFillTiles()) {
                // Fill connected tiles
                const filled = editorStore.fillItems(position.tileX, position.tileY);
                return { success: true, action: 'fill', tileExists: filled };
            } else if (isFieldTypeLayer(activeLayer) && canFillFieldTypes()) {
                // Fill connected field types using selected field type from store
                const selectedFieldTypeId = editorStore.getSelectedFieldTypeId();
                if (selectedFieldTypeId !== null) {
                    const filled = editorStore.fillItems(position.tileX, position.tileY, selectedFieldTypeId);
                    return { success: true, action: 'fill', tileExists: filled };
                }
                return { success: false, action: 'none' };
            }
        }

        return { success: false, action: 'none' };
    }

    function getTileAtPosition(event: MouseEvent): { tileX: number; tileY: number; hasTitle: boolean } | null {
        const position = calculateTilePosition(event);
        if (!position) return null;

        const activeLayer = editorStore.layers.find((layer) => layer.uuid === editorStore.activeLayer);
        if (!activeLayer) return null;

        let hasTile = false;
        if (isTileLayer(activeLayer)) {
            const tile = editorStore.getTileAt(position.tileX, position.tileY);
            hasTile = !!tile;
        } else if (isFieldTypeLayer(activeLayer)) {
            const fieldType = editorStore.getFieldTypeAt(position.tileX, position.tileY);
            hasTile = !!fieldType;
        }

        return {
            tileX: position.tileX,
            tileY: position.tileY,
            hasTitle: hasTile,
        };
    }

    return {
        calculateTilePosition,
        canPlaceTiles,
        canPlaceFieldTypes,
        canEraseTiles,
        canFillTiles,
        canFillFieldTypes,
        handleCanvasClick,
        getTileAtPosition,
    };
}
