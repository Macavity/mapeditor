import { useEditorStore } from '@/stores/editorStore';
import type { Tile } from '@/types/MapLayer';

export function useFloodFill() {
    const editorStore = useEditorStore();

    // Check if two tiles are the same (same tileset and tile coordinates)
    function tilesMatch(tile1: Tile | null, tile2: Tile | null): boolean {
        if (!tile1 && !tile2) return true; // Both empty
        if (!tile1 || !tile2) return false; // One empty, one not

        return tile1.brush.tileset === tile2.brush.tileset && tile1.brush.tileX === tile2.brush.tileX && tile1.brush.tileY === tile2.brush.tileY;
    }

    // Get connected tiles using flood fill algorithm
    function getConnectedTiles(startX: number, startY: number, layerUuid?: string): { x: number; y: number }[] {
        const targetLayerUuid = layerUuid || editorStore.activeLayer;
        if (!targetLayerUuid) return [];

        const layer = editorStore.layers.find((l) => l.uuid === targetLayerUuid);
        if (!layer) return [];

        // Get the starting tile to match against
        const startTile = editorStore.getTileAt(startX, startY, targetLayerUuid);

        // Use flood fill to find all connected tiles
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
            const currentTile = editorStore.getTileAt(current.x, current.y, targetLayerUuid);

            // Check if current tile matches the start tile
            if (tilesMatch(currentTile, startTile)) {
                result.push({ x: current.x, y: current.y });

                // Add neighboring tiles to queue (4-directional)
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

    // Check if fill would have any effect (target tile is different from brush)
    function canFill(tileX: number, tileY: number): boolean {
        if (!editorStore.activeLayer || !editorStore.brushSelection.tilesetUuid || !editorStore.brushSelection.backgroundImage) {
            return false;
        }

        const targetTile = editorStore.getTileAt(tileX, tileY);

        // If no tile exists, we can fill with brush
        if (!targetTile) {
            return true;
        }

        // Check if the target tile is different from the brush
        return !(
            targetTile.brush.tileset === editorStore.brushSelection.tilesetUuid &&
            targetTile.brush.tileX === editorStore.brushSelection.tileX &&
            targetTile.brush.tileY === editorStore.brushSelection.tileY
        );
    }

    return {
        getConnectedTiles,
        canFill,
        tilesMatch,
    };
}
