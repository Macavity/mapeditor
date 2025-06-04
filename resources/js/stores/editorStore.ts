import { MapService } from '@/services/MapService';
import { EditorTool } from '@/types/EditorTool';
import { MapLayer } from '@/types/MapLayer';
import { TileMap } from '@/types/TileMap';
import { defineStore } from 'pinia';

export const useEditorStore = defineStore('editorStore', {
    persist: true,
    state: () => ({
        loaded: false,
        showGrid: false,
        showProperties: false,
        showLeftSideBar: false,
        activeLayer: null as number | null,
        activeTool: EditorTool.DRAW,
        map: null as TileMap | null,
        layers: [] as MapLayer[],
    }),
    getters: {
        isDrawToolActive: (state) => state.activeTool === EditorTool.DRAW,
        isFillToolActive: (state) => state.activeTool === EditorTool.FILL,
        isEraseToolActive: (state) => state.activeTool === EditorTool.ERASE,
        canvasWidth: (state) => {
            if (!state.map) {
                return 0;
            }
            return state.map.width * state.map.tile_width;
        },
        canvasHeight: (state) => {
            if (!state.map) {
                return 0;
            }
            return state.map.height * state.map.tile_height;
        },
    },
    actions: {
        activateLayer(layerId: number) {
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

        toggleLayerVisibility(layerId: number) {
            console.log('toggleLayerVisibility', layerId);
            this.layers = this.layers.map((layer) => {
                if (layer.id !== layerId) {
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
                const data = await MapService.getMap(uuid);
                this.map = data;
                this.layers = data.layers ?? ([] as MapLayer[]);
                this.loaded = true;
            } catch (error) {
                console.error('Error loading map:', error);
                throw error;
            }
        },
    },
});
