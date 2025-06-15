import { MapService } from '@/services/MapService';
import { useSaveManager } from '@/stores/saveManager';
import type { BrushSelection, BrushSelectionConfig } from '@/types/BrushSelection';
import { EditorTool } from '@/types/EditorTool';
import { MapLayer } from '@/types/MapLayer';
import { defineStore } from 'pinia';

export const useEditorStore = defineStore('editorStore', {
    persist: true,
    state: () => ({
        loaded: false,
        showGrid: false,
        showProperties: false,
        showLeftSideBar: false,
        activeLayer: null as string | null,
        activeTool: EditorTool.DRAW,
        mapMetadata: {
            uuid: null as string | null,
            name: null as string | null,
            creatorName: null as string | null,
            width: 0,
            height: 0,
            tileWidth: 32,
            tileHeight: 32,
            tilesetUsage: {} as Record<string, number>,
        },
        layers: [] as MapLayer[],
        brushSelection: {
            width: 32,
            height: 32,
            backgroundImage: null,
            tileX: 0,
            tileY: 0,
            backgroundPosition: '0px 0px',
            tilesetUuid: null,
        } as BrushSelection,
    }),
    getters: {
        isDrawToolActive: (state) => state.activeTool === EditorTool.DRAW,
        isFillToolActive: (state) => state.activeTool === EditorTool.FILL,
        isEraseToolActive: (state) => state.activeTool === EditorTool.ERASE,
        canvasWidth: (state) => {
            return state.mapMetadata.width * state.mapMetadata.tileWidth;
        },
        canvasHeight: (state) => {
            return state.mapMetadata.height * state.mapMetadata.tileHeight;
        },
        layersSortedByZ: (state) => {
            return [...state.layers].sort((a, b) => a.z - b.z);
        },
        layersDisplayOrder: (state) => {
            // For UI display: highest z-index at top of list (reverse order)
            return [...state.layers].sort((a, b) => b.z - a.z);
        },
        brushTilesWide: (state) => {
            return Math.ceil(state.brushSelection.width / state.mapMetadata.tileWidth);
        },
        brushTilesHigh: (state) => {
            return Math.ceil(state.brushSelection.height / state.mapMetadata.tileHeight);
        },
    },
    actions: {
        activateLayer(layerId: string) {
            this.activeLayer = layerId;
        },

        toggleGrid() {
            this.showGrid = !this.showGrid;
        },

        toggleProperties() {
            this.showProperties = !this.showProperties;
        },

        selectTool(tool: EditorTool) {
            this.activeTool = tool;
        },

        toggleLayerVisibility(layerId: string) {
            console.log('toggleLayerVisibility', layerId);
            this.layers = this.layers.map((layer) => {
                if (layer.uuid !== layerId) {
                    return layer;
                }

                return {
                    ...layer,
                    visible: !layer.visible,
                };
            });
        },

        async loadMap(uuid: string) {
            try {
                const [mapData, layers] = await Promise.all([MapService.getMap(uuid), MapService.getMapLayers(uuid)]);

                this.mapMetadata = {
                    uuid: mapData.uuid,
                    name: mapData.name,
                    creatorName: mapData.creator?.name ?? 'Unknown',
                    width: mapData.width,
                    height: mapData.height,
                    tileWidth: mapData.tile_width,
                    tileHeight: mapData.tile_height,
                    tilesetUsage: mapData.tileset_usage || {},
                };
                this.layers = layers;

                // Ensure the first layer is set as active if none is selected
                if (!this.activeLayer && this.layers.length > 0) {
                    this.activeLayer = this.layers[0].uuid;
                }

                this.loaded = true;
                const saveManager = useSaveManager();
                saveManager.markAsSaved();
            } catch (error) {
                console.error('Error loading map:', error);
                throw error;
            }
        },

        setBrushSelection(config: BrushSelectionConfig) {
            // Calculate background position using map metadata tile dimensions
            const backgroundPositionX = -config.tileX * this.mapMetadata.tileWidth;
            const backgroundPositionY = -config.tileY * this.mapMetadata.tileHeight;

            this.brushSelection = {
                width: config.brushWidth,
                height: config.brushHeight,
                backgroundImage: config.tilesetImageUrl,
                tileX: config.tileX,
                tileY: config.tileY,
                backgroundPosition: `${backgroundPositionX}px ${backgroundPositionY}px`,
                tilesetUuid: config.tilesetUuid,
            };
        },

        clearBrushSelection() {
            this.brushSelection = {
                width: this.mapMetadata.tileWidth,
                height: this.mapMetadata.tileHeight,
                backgroundImage: null,
                tileX: 0,
                tileY: 0,
                backgroundPosition: '0px 0px',
                tilesetUuid: null,
            };
        },

        placeTiles(mapX: number, mapY: number) {
            // Only place tiles if we have an active layer and brush selection
            if (!this.activeLayer || !this.brushSelection.tilesetUuid || !this.brushSelection.backgroundImage) {
                return;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return;
            }

            if (!this.layers[activeLayerIndex].data) {
                this.layers[activeLayerIndex].data = [];
            }

            // Place each tile in the brush selection using computed dimensions
            for (let offsetY = 0; offsetY < this.brushTilesHigh; offsetY++) {
                for (let offsetX = 0; offsetX < this.brushTilesWide; offsetX++) {
                    const targetMapX = mapX + offsetX;
                    const targetMapY = mapY + offsetY;

                    // Check bounds
                    if (targetMapX >= this.mapMetadata.width || targetMapY >= this.mapMetadata.height) {
                        continue;
                    }

                    // Calculate the source tile coordinates in the tileset
                    const sourceTileX = this.brushSelection.tileX + offsetX;
                    const sourceTileY = this.brushSelection.tileY + offsetY;

                    // Create tile data
                    const tileData = {
                        x: targetMapX,
                        y: targetMapY,
                        brush: {
                            tileset: this.brushSelection.tilesetUuid,
                            tileX: sourceTileX,
                            tileY: sourceTileY,
                        },
                    };

                    // Find existing tile at this position and replace it, or add new tile
                    const existingTileIndex = this.layers[activeLayerIndex].data.findIndex((tile) => tile.x === targetMapX && tile.y === targetMapY);

                    if (existingTileIndex !== -1) {
                        // Replace existing tile
                        this.layers[activeLayerIndex].data[existingTileIndex] = tileData;
                    } else {
                        // Add new tile
                        this.layers[activeLayerIndex].data.push(tileData);
                    }
                }
            }

            const saveManager = useSaveManager();
            saveManager.markAsChanged();
            saveManager.scheduleAutoSave(async () => {
                await this.saveAllLayers();
            });
        },

        eraseTile(mapX: number, mapY: number): boolean {
            // Only erase if we have an active layer
            if (!this.activeLayer) {
                return false;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return false;
            }

            if (!this.layers[activeLayerIndex].data) {
                return false; // No tiles to erase
            }

            // Find existing tile at this position
            const existingTileIndex = this.layers[activeLayerIndex].data.findIndex((tile) => tile.x === mapX && tile.y === mapY);

            if (existingTileIndex !== -1) {
                // Remove the tile
                this.layers[activeLayerIndex].data.splice(existingTileIndex, 1);
                const saveManager = useSaveManager();
                saveManager.markAsChanged();
                saveManager.scheduleAutoSave(async () => {
                    await this.saveAllLayers();
                });
                return true; // Tile was erased
            }

            return false; // No tile to erase
        },

        fillTiles(mapX: number, mapY: number): boolean {
            // Only fill if we have an active layer and brush selection
            if (!this.activeLayer || !this.brushSelection.tilesetUuid || !this.brushSelection.backgroundImage) {
                return false;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return false;
            }

            if (!this.layers[activeLayerIndex].data) {
                this.layers[activeLayerIndex].data = [];
            }

            // Get the starting tile to match against
            const startTile = this.getTileAt(mapX, mapY);

            // Use flood fill algorithm to find all connected tiles first
            const visited = new Set<string>();
            const tilesToFill: { x: number; y: number }[] = [];
            const queue: { x: number; y: number }[] = [{ x: mapX, y: mapY }];

            while (queue.length > 0) {
                const current = queue.shift()!;
                const key = `${current.x},${current.y}`;

                // Skip if already visited or out of bounds
                if (
                    visited.has(key) ||
                    current.x < 0 ||
                    current.x >= this.mapMetadata.width ||
                    current.y < 0 ||
                    current.y >= this.mapMetadata.height
                ) {
                    continue;
                }

                visited.add(key);
                const currentTile = this.getTileAt(current.x, current.y);

                // Check if current tile matches the start tile
                const tilesMatch = this.tilesMatch(currentTile, startTile);

                if (tilesMatch) {
                    tilesToFill.push({ x: current.x, y: current.y });

                    // Add neighboring tiles to queue (4-directional)
                    queue.push(
                        { x: current.x + 1, y: current.y }, // Right
                        { x: current.x - 1, y: current.y }, // Left
                        { x: current.x, y: current.y + 1 }, // Down
                        { x: current.x, y: current.y - 1 }, // Up
                    );
                }
            }

            if (tilesToFill.length === 0) {
                return false;
            }

            // Calculate bounding box for multi-tile pattern alignment
            let boundingBox: { minX: number; minY: number; maxX: number; maxY: number } | null = null;
            if (this.brushTilesWide >= 2) {
                let minX = tilesToFill[0].x;
                let minY = tilesToFill[0].y;
                let maxX = tilesToFill[0].x;
                let maxY = tilesToFill[0].y;

                for (const tile of tilesToFill) {
                    minX = Math.min(minX, tile.x);
                    minY = Math.min(minY, tile.y);
                    maxX = Math.max(maxX, tile.x);
                    maxY = Math.max(maxY, tile.y);
                }

                boundingBox = { minX, minY, maxX, maxY };
            }

            // Check if fill would have any effect by testing the clicked position
            let expectedTileX: number;
            let expectedTileY: number;

            if (boundingBox && this.brushTilesWide >= 2) {
                // For multi-tile patterns, align to bounding box
                const patternOffsetX = (mapX - boundingBox.minX) % this.brushTilesWide;
                const patternOffsetY = (mapY - boundingBox.minY) % this.brushTilesHigh;
                expectedTileX = this.brushSelection.tileX + patternOffsetX;
                expectedTileY = this.brushSelection.tileY + patternOffsetY;
            } else {
                // For single tiles, use original logic
                const patternOffsetX = mapX % this.brushTilesWide;
                const patternOffsetY = mapY % this.brushTilesHigh;
                expectedTileX = this.brushSelection.tileX + patternOffsetX;
                expectedTileY = this.brushSelection.tileY + patternOffsetY;
            }

            // Check if fill would have any effect
            if (
                startTile &&
                startTile.brush.tileset === this.brushSelection.tilesetUuid &&
                startTile.brush.tileX === expectedTileX &&
                startTile.brush.tileY === expectedTileY
            ) {
                return false; // No change needed - tile already matches what would be placed
            }

            // Fill all identified tiles with the appropriate tile from the pattern
            for (const tile of tilesToFill) {
                // Calculate which tile from the pattern should be used at this position
                let tilePatternOffsetX: number;
                let tilePatternOffsetY: number;

                if (boundingBox && this.brushTilesWide >= 2) {
                    // For multi-tile patterns, align to bounding box
                    tilePatternOffsetX = (tile.x - boundingBox.minX) % this.brushTilesWide;
                    tilePatternOffsetY = (tile.y - boundingBox.minY) % this.brushTilesHigh;
                } else {
                    // For single tiles, use original logic
                    tilePatternOffsetX = tile.x % this.brushTilesWide;
                    tilePatternOffsetY = tile.y % this.brushTilesHigh;
                }

                const sourceTileX = this.brushSelection.tileX + tilePatternOffsetX;
                const sourceTileY = this.brushSelection.tileY + tilePatternOffsetY;

                const tileData = {
                    x: tile.x,
                    y: tile.y,
                    brush: {
                        tileset: this.brushSelection.tilesetUuid,
                        tileX: sourceTileX,
                        tileY: sourceTileY,
                    },
                };

                // Find existing tile at this position and replace it, or add new tile
                const existingTileIndex = this.layers[activeLayerIndex].data.findIndex((t) => t.x === tile.x && t.y === tile.y);

                if (existingTileIndex !== -1) {
                    // Replace existing tile
                    this.layers[activeLayerIndex].data[existingTileIndex] = tileData;
                } else {
                    // Add new tile
                    this.layers[activeLayerIndex].data.push(tileData);
                }
            }

            const saveManager = useSaveManager();
            saveManager.markAsChanged();
            saveManager.scheduleAutoSave(async () => {
                await this.saveAllLayers();
            });
            return true;
        },

        // Helper method for tile comparison used by fillTiles
        tilesMatch(tile1: any, tile2: any): boolean {
            if (!tile1 && !tile2) return true; // Both empty
            if (!tile1 || !tile2) return false; // One empty, one not

            return tile1.brush.tileset === tile2.brush.tileset && tile1.brush.tileX === tile2.brush.tileX && tile1.brush.tileY === tile2.brush.tileY;
        },

        getTileAt(mapX: number, mapY: number, layerUuid?: string): any | null {
            const targetLayerUuid = layerUuid || this.activeLayer;
            if (!targetLayerUuid) return null;

            const layer = this.layers.find((l) => l.uuid === targetLayerUuid);
            if (!layer?.data) return null;

            return layer.data.find((tile) => tile.x === mapX && tile.y === mapY) || null;
        },

        async saveAllLayers() {
            if (!this.mapMetadata.uuid) return;

            try {
                await MapService.saveLayers(this.mapMetadata.uuid, this.layers);
                const saveManager = useSaveManager();
                saveManager.markAsSaved();
                return { success: true };
            } catch (error) {
                console.error('Error saving layers:', error);
                const saveManager = useSaveManager();
                saveManager.markAsChanged();
                throw error; // Propagate the error to the save manager
            }
        },

        async createSkyLayer(options?: { name?: string }) {
            if (!this.mapMetadata.uuid) return;

            try {
                const newLayer = await MapService.createSkyLayer(this.mapMetadata.uuid, options);

                // Refresh all layers to get updated z-indices
                this.layers = await MapService.getMapLayers(this.mapMetadata.uuid);

                // Activate the new layer
                this.activeLayer = newLayer.uuid;

                return newLayer;
            } catch (error) {
                console.error('Error creating sky layer:', error);
                throw error;
            }
        },

        async createFloorLayer(options?: { name?: string }) {
            if (!this.mapMetadata.uuid) return;

            try {
                const newLayer = await MapService.createFloorLayer(this.mapMetadata.uuid, options);

                // Refresh all layers to get updated z-indices (sky layers get shifted up)
                this.layers = await MapService.getMapLayers(this.mapMetadata.uuid);

                // Activate the new layer
                this.activeLayer = newLayer.uuid;

                return newLayer;
            } catch (error) {
                console.error('Error creating floor layer:', error);
                throw error;
            }
        },

        getTileCount(layerUuid: string): number {
            const layer = this.layers.find((l) => l.uuid === layerUuid);
            return layer?.data?.length ?? 0;
        },

        async deleteLayer(layerUuid: string): Promise<{ success: boolean; error?: string }> {
            if (!this.mapMetadata.uuid) {
                return { success: false, error: 'No map loaded' };
            }

            const layer = this.layers.find((l) => l.uuid === layerUuid);
            if (!layer) {
                return { success: false, error: 'Layer not found' };
            }

            try {
                await MapService.deleteLayer(this.mapMetadata.uuid, layerUuid);

                // Refresh all layers to get updated z-indices after deletion
                this.layers = await MapService.getMapLayers(this.mapMetadata.uuid);

                // If deleted layer was active, switch to another layer
                if (this.activeLayer === layerUuid && this.layers.length > 0) {
                    this.activeLayer = this.layers[0].uuid;
                } else if (this.layers.length === 0) {
                    this.activeLayer = null;
                }

                return { success: true };
            } catch (error) {
                console.error('Error deleting layer:', error);
                return {
                    success: false,
                    error: error instanceof Error ? error.message : 'Failed to delete layer',
                };
            }
        },
    },
});
