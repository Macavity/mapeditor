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

    // Get the bounding box of a set of tiles
    function getTilesBoundingBox(tiles: { x: number; y: number }[]): { minX: number; minY: number; maxX: number; maxY: number } | null {
        if (tiles.length === 0) return null;

        let minX = tiles[0].x;
        let minY = tiles[0].y;
        let maxX = tiles[0].x;
        let maxY = tiles[0].y;

        for (const tile of tiles) {
            minX = Math.min(minX, tile.x);
            minY = Math.min(minY, tile.y);
            maxX = Math.max(maxX, tile.x);
            maxY = Math.max(maxY, tile.y);
        }

        return { minX, minY, maxX, maxY };
    }

    // Check if fill would have any effect (target tile is different from brush)
    function canFill(tileX: number, tileY: number): boolean {
        if (!editorStore.activeLayer || !editorStore.brushSelection.tilesetUuid || !editorStore.brushSelection.backgroundImage) {
            return false;
        }

        const targetTile = editorStore.getTileAt(tileX, tileY);

        // For multi-tile patterns spanning at least 2 columns, we need to consider the connected tiles
        // to determine the proper alignment
        if (editorStore.brushTilesWide >= 2) {
            const connectedTiles = getConnectedTiles(tileX, tileY);
            const boundingBox = getTilesBoundingBox(connectedTiles);

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

    // Get the tile that should be placed at a specific position based on the pattern
    // For multi-tile patterns (>= 2 columns), align to the connected tiles bounding box
    function getTileForPosition(tileX: number, tileY: number, connectedTiles?: { x: number; y: number }[]): { tileX: number; tileY: number } | null {
        if (!editorStore.brushSelection.tilesetUuid) return null;

        // For multi-tile patterns spanning at least 2 columns, align to connected tiles
        if (editorStore.brushTilesWide >= 2 && connectedTiles) {
            const boundingBox = getTilesBoundingBox(connectedTiles);
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

    return {
        getConnectedTiles,
        getTilesBoundingBox,
        canFill,
        tilesMatch,
        getTileForPosition,
    };
}
