import { LayerItemFactory } from '@/factories/LayerItemFactory';
import { FieldTypeService, type FieldType } from '@/services/FieldTypeService';
import { MapService } from '@/services/MapService';
import { useSaveManager } from '@/stores/saveManager';
import type { BrushSelection, BrushSelectionConfig } from '@/types/BrushSelection';
import { EditorTool } from '@/types/EditorTool';
import { MapLayer, MapLayerType, isFieldTypeLayer, isTileLayer, type FieldTypeTile, type Tile } from '@/types/MapLayer';
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
        // Field type state
        fieldTypes: [] as FieldType[],
        selectedFieldType: null as FieldType | null,
        fieldTypesLoaded: false,
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

            const activeLayer = this.layers[activeLayerIndex];
            if (!isTileLayer(activeLayer)) {
                return; // Can't place tiles on field type layers
            }

            if (!activeLayer.data) {
                activeLayer.data = [];
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
                    const tileData: Tile = {
                        x: targetMapX,
                        y: targetMapY,
                        brush: {
                            tileset: this.brushSelection.tilesetUuid,
                            tileX: sourceTileX,
                            tileY: sourceTileY,
                        },
                    };

                    // Find existing tile at this position and replace it, or add new tile
                    const existingTileIndex = activeLayer.data.findIndex((tile) => tile.x === targetMapX && tile.y === targetMapY);

                    if (existingTileIndex !== -1) {
                        // Replace existing tile
                        activeLayer.data[existingTileIndex] = tileData;
                    } else {
                        // Add new tile
                        activeLayer.data.push(tileData);
                    }
                }
            }

            this.markAsChanged();
        },

        placeFieldType(mapX: number, mapY: number, fieldTypeId: number) {
            // Only place field types if we have an active layer
            if (!this.activeLayer) {
                return;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return;
            }

            const activeLayer = this.layers[activeLayerIndex];
            if (!isFieldTypeLayer(activeLayer)) {
                return; // Can't place field types on tile layers
            }

            if (!activeLayer.data) {
                activeLayer.data = [];
            }

            // Create field type using factory
            const fieldTypeData = LayerItemFactory.createFieldTypeAtPosition(mapX, mapY, fieldTypeId);

            this.placeItemAtPosition(activeLayer, fieldTypeData, mapX, mapY);

            this.markAsChanged();
        },

        placeItem(mapX: number, mapY: number, fieldTypeId?: number) {
            if (!this.activeLayer) {
                return;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return;
            }

            const activeLayer = this.layers[activeLayerIndex];
            if (!activeLayer.data) {
                activeLayer.data = [];
            }

            if (isTileLayer(activeLayer)) {
                // Place tiles
                if (!this.brushSelection.tilesetUuid || !this.brushSelection.backgroundImage) {
                    return;
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
                        const tileData: Tile = {
                            x: targetMapX,
                            y: targetMapY,
                            brush: {
                                tileset: this.brushSelection.tilesetUuid,
                                tileX: sourceTileX,
                                tileY: sourceTileY,
                            },
                        };

                        this.placeItemAtPosition(activeLayer, tileData, targetMapX, targetMapY);
                    }
                }
            } else if (isFieldTypeLayer(activeLayer)) {
                // Place field type
                if (fieldTypeId === undefined) {
                    return;
                }

                const fieldTypeData: FieldTypeTile = {
                    x: mapX,
                    y: mapY,
                    fieldType: fieldTypeId,
                };

                this.placeItemAtPosition(activeLayer, fieldTypeData, mapX, mapY);
            }

            this.markAsChanged();
        },

        placeItemAtPosition(layer: MapLayer, item: Tile | FieldTypeTile, x: number, y: number) {
            // Validate the item using the factory
            if (!LayerItemFactory.isValid(item)) {
                console.warn('Invalid item provided to placeItemAtPosition:', item);
                return;
            }

            const existingIndex = layer.data.findIndex((existingItem) => {
                // Use factory validation for existing items too
                if (!LayerItemFactory.isValid(existingItem)) return false;
                return existingItem.x === x && existingItem.y === y;
            });

            if (existingIndex !== -1) {
                // Replace existing item
                (layer.data as (Tile | FieldTypeTile)[])[existingIndex] = item;
            } else {
                // Add new item
                (layer.data as (Tile | FieldTypeTile)[]).push(item);
            }
        },

        eraseItem(mapX: number, mapY: number): boolean {
            if (!this.activeLayer) {
                return false;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return false;
            }

            const activeLayer = this.layers[activeLayerIndex];
            if (!activeLayer.data) {
                return false; // No items to erase
            }

            // Find existing item at this position
            const existingItemIndex = activeLayer.data.findIndex((item) => {
                // Add null checks to prevent errors
                if (!item || typeof item !== 'object') return false;
                if (typeof item.x !== 'number' || typeof item.y !== 'number') return false;
                return item.x === mapX && item.y === mapY;
            });

            if (existingItemIndex !== -1) {
                // Remove the item
                activeLayer.data.splice(existingItemIndex, 1);
                this.markAsChanged();
                return true; // Item was erased
            }

            return false; // No item to erase
        },

        fillItems(mapX: number, mapY: number, fieldTypeId?: number): boolean {
            if (!this.activeLayer) {
                return false;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return false;
            }

            const activeLayer = this.layers[activeLayerIndex];
            if (!activeLayer.data) {
                activeLayer.data = [];
            }

            if (isTileLayer(activeLayer)) {
                return this.fillTiles(mapX, mapY);
            } else if (isFieldTypeLayer(activeLayer)) {
                if (fieldTypeId === undefined) {
                    return false;
                }
                return this.fillFieldTypes(mapX, mapY, fieldTypeId);
            }

            return false;
        },

        fillTiles(mapX: number, mapY: number): boolean {
            // Only fill if we have brush selection
            if (!this.brushSelection.tilesetUuid || !this.brushSelection.backgroundImage) {
                return false;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return false;
            }

            const activeLayer = this.layers[activeLayerIndex];
            if (!isTileLayer(activeLayer)) {
                return false; // Can't fill tiles on field type layers
            }

            if (!activeLayer.data) {
                activeLayer.data = [];
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

                const tileData: Tile = {
                    x: tile.x,
                    y: tile.y,
                    brush: {
                        tileset: this.brushSelection.tilesetUuid,
                        tileX: sourceTileX,
                        tileY: sourceTileY,
                    },
                };

                this.placeItemAtPosition(activeLayer, tileData, tile.x, tile.y);
            }

            this.markAsChanged();
            return true;
        },

        fillFieldTypes(mapX: number, mapY: number, fieldTypeId: number): boolean {
            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return false;
            }

            const activeLayer = this.layers[activeLayerIndex];
            if (!isFieldTypeLayer(activeLayer)) {
                return false; // Can't fill field types on tile layers
            }

            if (!activeLayer.data) {
                activeLayer.data = [];
            }

            // Get the starting field type to match against
            const startFieldType = this.getFieldTypeAt(mapX, mapY);

            // Use flood fill algorithm to find all connected field types first
            const visited = new Set<string>();
            const fieldTypesToFill: { x: number; y: number }[] = [];
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
                const currentFieldType = this.getFieldTypeAt(current.x, current.y);

                // Check if current field type matches the start field type
                const fieldTypesMatch = this.fieldTypesMatch(currentFieldType, startFieldType);

                if (fieldTypesMatch) {
                    fieldTypesToFill.push({ x: current.x, y: current.y });

                    // Add neighboring field types to queue (4-directional)
                    queue.push(
                        { x: current.x + 1, y: current.y }, // Right
                        { x: current.x - 1, y: current.y }, // Left
                        { x: current.x, y: current.y + 1 }, // Down
                        { x: current.x, y: current.y - 1 }, // Up
                    );
                }
            }

            if (fieldTypesToFill.length === 0) {
                return false;
            }

            // Check if fill would have any effect
            if (startFieldType && startFieldType.fieldType === fieldTypeId) {
                return false; // No change needed - field type already matches what would be placed
            }

            // Fill all identified field types
            for (const fieldType of fieldTypesToFill) {
                const fieldTypeData: FieldTypeTile = {
                    x: fieldType.x,
                    y: fieldType.y,
                    fieldType: fieldTypeId,
                };

                this.placeItemAtPosition(activeLayer, fieldTypeData, fieldType.x, fieldType.y);
            }

            this.markAsChanged();
            return true;
        },

        // Helper method for tile comparison used by fillTiles
        tilesMatch(tile1: any, tile2: any): boolean {
            if (!tile1 && !tile2) return true; // Both empty
            if (!tile1 || !tile2) return false; // One empty, one not

            return tile1.brush.tileset === tile2.brush.tileset && tile1.brush.tileX === tile2.brush.tileX && tile1.brush.tileY === tile2.brush.tileY;
        },

        getTileAt(mapX: number, mapY: number, layerUuid?: string): Tile | null {
            const targetLayerUuid = layerUuid || this.activeLayer;
            if (!targetLayerUuid) return null;

            const layer = this.layers.find((l) => l.uuid === targetLayerUuid);
            if (!layer?.data || !isTileLayer(layer)) return null;

            const tile = layer.data.find((item) => {
                // Add null checks to prevent errors
                if (!item || typeof item !== 'object') return false;
                if (typeof item.x !== 'number' || typeof item.y !== 'number') return false;
                return item.x === mapX && item.y === mapY;
            });
            return tile && 'brush' in tile ? (tile as Tile) : null;
        },

        getFieldTypeAt(mapX: number, mapY: number, layerUuid?: string): FieldTypeTile | null {
            const targetLayerUuid = layerUuid || this.activeLayer;
            if (!targetLayerUuid) return null;

            const layer = this.layers.find((l) => l.uuid === targetLayerUuid);
            if (!layer?.data || !isFieldTypeLayer(layer)) return null;

            const fieldType = layer.data.find((item) => {
                // Add null checks to prevent errors
                if (!item || typeof item !== 'object') return false;
                if (typeof item.x !== 'number' || typeof item.y !== 'number') return false;
                return item.x === mapX && item.y === mapY;
            });
            return fieldType && 'fieldType' in fieldType ? fieldType : null;
        },

        fieldTypesMatch(fieldType1: FieldTypeTile | null, fieldType2: FieldTypeTile | null): boolean {
            if (!fieldType1 && !fieldType2) return true; // Both empty
            if (!fieldType1 || !fieldType2) return false; // One empty, one not

            return fieldType1.fieldType === fieldType2.fieldType;
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
            return this.createLayer(MapLayerType.Sky, options);
        },

        async createFloorLayer(options?: { name?: string }) {
            return this.createLayer(MapLayerType.Floor, options);
        },

        async createObjectLayer(options?: { name?: string }) {
            return this.createLayer(MapLayerType.Object, options);
        },

        async createFieldTypeLayer(options?: { name?: string }) {
            return this.createLayer(MapLayerType.FieldType, options);
        },

        /**
         * Generic layer creation method
         */
        async createLayer(layerType: MapLayerType, options?: { name?: string }) {
            if (!this.mapMetadata.uuid) return;

            try {
                let newLayer;

                switch (layerType) {
                    case MapLayerType.Sky:
                        newLayer = await MapService.createSkyLayer(this.mapMetadata.uuid, options);
                        break;
                    case MapLayerType.Floor:
                        newLayer = await MapService.createFloorLayer(this.mapMetadata.uuid, options);
                        break;
                    case MapLayerType.Object:
                        newLayer = await MapService.createObjectLayer(this.mapMetadata.uuid, options);
                        break;
                    case MapLayerType.FieldType:
                        newLayer = await MapService.createFieldTypeLayer(this.mapMetadata.uuid, options);
                        break;
                    default:
                        throw new Error(`Unknown layer type: ${layerType}`);
                }

                // Refresh all layers to get updated z-indices
                this.layers = await MapService.getMapLayers(this.mapMetadata.uuid);

                // Activate the new layer
                this.activeLayer = newLayer.uuid;

                return newLayer;
            } catch (error) {
                console.error(`Error creating ${layerType} layer:`, error);
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

        // Helper method to mark changes and schedule auto-save
        markAsChanged() {
            const saveManager = useSaveManager();
            saveManager.markAsChanged();
            saveManager.scheduleAutoSave(async () => {
                await this.saveAllLayers();
            });
        },

        // Field type management methods
        async loadFieldTypes() {
            if (this.fieldTypesLoaded) {
                return; // Already loaded
            }

            try {
                this.fieldTypes = await FieldTypeService.getAll();
                if (this.fieldTypes.length > 0 && !this.selectedFieldType) {
                    this.selectedFieldType = this.fieldTypes[0];
                }
                this.fieldTypesLoaded = true;
            } catch (error) {
                console.error('Failed to load field types:', error);
                throw error;
            }
        },

        selectFieldType(fieldType: FieldType) {
            this.selectedFieldType = fieldType;
        },

        getSelectedFieldTypeId(): number | null {
            return this.selectedFieldType?.id ?? null;
        },
    },
});
