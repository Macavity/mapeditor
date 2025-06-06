import { MapService } from '@/services/MapService';
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
        },
        layers: [] as MapLayer[],
        brushSelection: {
            width: 32,
            height: 32,
            backgroundImage: null as string | null,
            tileX: 0,
            tileY: 0,
            backgroundPosition: '0px 0px',
            tilesetUuid: null as string | null,
        },
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
                };
                this.layers = layers;

                // Ensure the first layer is set as active if none is selected
                if (!this.activeLayer && this.layers.length > 0) {
                    this.activeLayer = this.layers[0].uuid;
                }

                this.loaded = true;
            } catch (error) {
                console.error('Error loading map:', error);
                throw error;
            }
        },

        setBrushSelection(tileX: number, tileY: number, tileWidth: number, tileHeight: number, tilesetImageUrl: string, tilesetUuid: string) {
            this.brushSelection = {
                width: tileWidth,
                height: tileHeight,
                backgroundImage: tilesetImageUrl,
                tileX,
                tileY,
                backgroundPosition: `-${tileX * tileWidth}px -${tileY * tileHeight}px`,
                tilesetUuid,
            };
        },

        clearBrushSelection() {
            this.brushSelection = {
                width: 32,
                height: 32,
                backgroundImage: null,
                tileX: 0,
                tileY: 0,
                backgroundPosition: '0px 0px',
                tilesetUuid: null,
            };
        },

        placeTile(mapX: number, mapY: number) {
            // Only place tile if we have an active layer and brush selection
            if (!this.activeLayer || !this.brushSelection.tilesetUuid || !this.brushSelection.backgroundImage) {
                return;
            }

            const activeLayerIndex = this.layers.findIndex((layer) => layer.uuid === this.activeLayer);
            if (activeLayerIndex === -1) {
                return;
            }

            // Create or update the tile data
            const tileData = {
                x: mapX,
                y: mapY,
                brush: {
                    tileset: this.brushSelection.tilesetUuid,
                    tileX: this.brushSelection.tileX,
                    tileY: this.brushSelection.tileY,
                },
            };

            // Initialize data array if it doesn't exist
            if (!this.layers[activeLayerIndex].data) {
                this.layers[activeLayerIndex].data = [];
            }

            // Find existing tile at this position and replace it, or add new tile
            const existingTileIndex = this.layers[activeLayerIndex].data.findIndex((tile) => tile.x === mapX && tile.y === mapY);

            if (existingTileIndex !== -1) {
                // Replace existing tile
                this.layers[activeLayerIndex].data[existingTileIndex] = tileData;
            } else {
                // Add new tile
                this.layers[activeLayerIndex].data.push(tileData);
            }
        },
    },
});
