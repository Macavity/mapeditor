import { useEditorStore } from '@/stores/editorStore';
import type { FieldTypeTile, Tile } from '@/types/MapLayer';
import { isFieldTypeLayer, isTileLayer } from '@/types/MapLayer';

export function useUnifiedFloodFill() {
    const editorStore = useEditorStore();

    // Generic item matching function
    function itemsMatch<T>(item1: T | null, item2: T | null, matcher: (a: T, b: T) => boolean): boolean {
        if (!item1 && !item2) return true; // Both empty
        if (!item1 || !item2) return false; // One empty, one not
        return matcher(item1, item2);
    }

    // Tile-specific matching
    function tilesMatch(tile1: Tile | null, tile2: Tile | null): boolean {
        return itemsMatch(
            tile1,
            tile2,
            (a, b) => a.brush.tileset === b.brush.tileset && a.brush.tileX === b.brush.tileX && a.brush.tileY === b.brush.tileY,
        );
    }

    // Field type-specific matching
    function fieldTypesMatch(fieldType1: FieldTypeTile | null, fieldType2: FieldTypeTile | null): boolean {
        return itemsMatch(fieldType1, fieldType2, (a, b) => a.fieldType === b.fieldType);
    }

    // Generic flood fill algorithm
    function getConnectedItemsGeneric<T>(
        startX: number,
        startY: number,
        layerUuid: string,
        getItemAt: (x: number, y: number, layerUuid: string) => T | null,
        itemsMatch: (a: T | null, b: T | null) => boolean,
    ): { x: number; y: number }[] {
        if (!layerUuid) return [];

        const layer = editorStore.layers.find((l) => l.uuid === layerUuid);
        if (!layer) return [];

        // Get the starting item to match against
        const startItem = getItemAt(startX, startY, layerUuid);

        // Use flood fill to find all connected items
        const visited = new Set<string>();
        const result: { x: number; y: number }[] = [];
        const queue: { x: number; y: number }[] = [{ x: startX, y: startY }];

        while (queue.length > 0) {
            const current = queue.shift()!;
            const key = `${current.x},${current.y}`;

            // Skip if already visited or out of bounds
            if (
                visited.has(key) ||
                current.x < 0 ||
                current.x >= editorStore.mapMetadata.width ||
                current.y < 0 ||
                current.y >= editorStore.mapMetadata.height
            ) {
                continue;
            }

            visited.add(key);
            const currentItem = getItemAt(current.x, current.y, layerUuid);

            // Check if current item matches the start item
            if (itemsMatch(currentItem, startItem)) {
                result.push({ x: current.x, y: current.y });

                // Add neighboring items to queue (4-directional)
                queue.push(
                    { x: current.x + 1, y: current.y }, // Right
                    { x: current.x - 1, y: current.y }, // Left
                    { x: current.x, y: current.y + 1 }, // Down
                    { x: current.x, y: current.y - 1 }, // Up
                );
            }
        }

        return result;
    }

    // Get connected tiles using flood fill algorithm
    function getConnectedTiles(startX: number, startY: number, layerUuid?: string): { x: number; y: number }[] {
        const targetLayerUuid = layerUuid || editorStore.activeLayer;
        if (!targetLayerUuid) return [];

        return getConnectedItemsGeneric(startX, startY, targetLayerUuid, editorStore.getTileAt.bind(editorStore), tilesMatch);
    }

    // Get connected field types using flood fill algorithm
    function getConnectedFieldTypes(startX: number, startY: number, layerUuid?: string): { x: number; y: number }[] {
        const targetLayerUuid = layerUuid || editorStore.activeLayer;
        if (!targetLayerUuid) return [];

        return getConnectedItemsGeneric(startX, startY, targetLayerUuid, editorStore.getFieldTypeAt.bind(editorStore), fieldTypesMatch);
    }

    // Get the bounding box of a set of items
    function getItemsBoundingBox(items: { x: number; y: number }[]): { minX: number; minY: number; maxX: number; maxY: number } | null {
        if (items.length === 0) return null;

        let minX = items[0].x;
        let minY = items[0].y;
        let maxX = items[0].x;
        let maxY = items[0].y;

        for (const item of items) {
            minX = Math.min(minX, item.x);
            minY = Math.min(minY, item.y);
            maxX = Math.max(maxX, item.x);
            maxY = Math.max(maxY, item.y);
        }

        return { minX, minY, maxX, maxY };
    }

    // Check if tile fill would have any effect
    function canFillTiles(tileX: number, tileY: number): boolean {
        if (!editorStore.activeLayer || !editorStore.brushSelection.tilesetUuid || !editorStore.brushSelection.backgroundImage) {
            return false;
        }

        const targetTile = editorStore.getTileAt(tileX, tileY);

        // For multi-tile patterns spanning at least 2 columns, we need to consider the connected tiles
        // to determine the proper alignment
        if (editorStore.brushTilesWide >= 2) {
            const connectedTiles = getConnectedTiles(tileX, tileY);
            const boundingBox = getItemsBoundingBox(connectedTiles);

            if (boundingBox) {
                // Calculate pattern offset relative to the bounding box
                const patternOffsetX = (tileX - boundingBox.minX) % editorStore.brushTilesWide;
                const patternOffsetY = (tileY - boundingBox.minY) % editorStore.brushTilesHigh;
                const expectedTileX = editorStore.brushSelection.tileX + patternOffsetX;
                const expectedTileY = editorStore.brushSelection.tileY + patternOffsetY;

                if (!targetTile) {
                    return true;
                }

                return !(
                    targetTile.brush.tileset === editorStore.brushSelection.tilesetUuid &&
                    targetTile.brush.tileX === expectedTileX &&
                    targetTile.brush.tileY === expectedTileY
                );
            }
        } else {
            // For single tiles, use the original logic
            const patternOffsetX = tileX % editorStore.brushTilesWide;
            const patternOffsetY = tileY % editorStore.brushTilesHigh;
            const expectedTileX = editorStore.brushSelection.tileX + patternOffsetX;
            const expectedTileY = editorStore.brushSelection.tileY + patternOffsetY;

            if (!targetTile) {
                return true;
            }

            return !(
                targetTile.brush.tileset === editorStore.brushSelection.tilesetUuid &&
                targetTile.brush.tileX === expectedTileX &&
                targetTile.brush.tileY === expectedTileY
            );
        }

        return true;
    }

    // Check if field type fill would have any effect
    function canFillFieldTypes(tileX: number, tileY: number): boolean {
        if (!editorStore.activeLayer) {
            return false;
        }

        const selectedFieldTypeId = editorStore.getSelectedFieldTypeId();
        if (selectedFieldTypeId === null) {
            return false;
        }

        const targetFieldType = editorStore.getFieldTypeAt(tileX, tileY);

        // If there's no field type at the target position, we can fill
        if (!targetFieldType) {
            return true;
        }

        // If the target field type is different from the selected field type, we can fill
        return targetFieldType.fieldType !== selectedFieldTypeId;
    }

    // Get the tile that should be placed at a specific position based on the pattern
    function getTileForPosition(tileX: number, tileY: number, connectedTiles?: { x: number; y: number }[]): { tileX: number; tileY: number } | null {
        if (!editorStore.brushSelection.tilesetUuid) return null;

        // For multi-tile patterns spanning at least 2 columns, align to connected tiles
        if (editorStore.brushTilesWide >= 2 && connectedTiles) {
            const boundingBox = getItemsBoundingBox(connectedTiles);
            if (boundingBox) {
                const patternOffsetX = (tileX - boundingBox.minX) % editorStore.brushTilesWide;
                const patternOffsetY = (tileY - boundingBox.minY) % editorStore.brushTilesHigh;

                return {
                    tileX: editorStore.brushSelection.tileX + patternOffsetX,
                    tileY: editorStore.brushSelection.tileY + patternOffsetY,
                };
            }
        }

        // Fallback to original behavior for single tiles or when no connected tiles provided
        const patternOffsetX = tileX % editorStore.brushTilesWide;
        const patternOffsetY = tileY % editorStore.brushTilesHigh;

        return {
            tileX: editorStore.brushSelection.tileX + patternOffsetX,
            tileY: editorStore.brushSelection.tileY + patternOffsetY,
        };
    }

    // Generic can fill function that determines the layer type and calls the appropriate function
    function canFill(tileX: number, tileY: number): boolean {
        if (!editorStore.activeLayer) return false;

        const layer = editorStore.layers.find((l) => l.uuid === editorStore.activeLayer);
        if (!layer) return false;

        if (isTileLayer(layer)) {
            return canFillTiles(tileX, tileY);
        } else if (isFieldTypeLayer(layer)) {
            return canFillFieldTypes(tileX, tileY);
        }

        return false;
    }

    // Generic get connected items function that determines the layer type
    function getConnectedItems(startX: number, startY: number, layerUuid?: string): { x: number; y: number }[] {
        const targetLayerUuid = layerUuid || editorStore.activeLayer;
        if (!targetLayerUuid) return [];

        const layer = editorStore.layers.find((l) => l.uuid === targetLayerUuid);
        if (!layer) return [];

        if (isTileLayer(layer)) {
            return getConnectedTiles(startX, startY, layerUuid);
        } else if (isFieldTypeLayer(layer)) {
            return getConnectedFieldTypes(startX, startY, layerUuid);
        }

        return [];
    }

    return {
        // Generic functions
        getConnectedItems,
        canFill,
        getItemsBoundingBox,

        // Tile-specific functions
        getConnectedTiles,
        canFillTiles,
        tilesMatch,
        getTileForPosition,

        // Field type-specific functions
        getConnectedFieldTypes,
        canFillFieldTypes,
        fieldTypesMatch,
    };
}
