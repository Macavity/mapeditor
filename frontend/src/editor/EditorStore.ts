import { defineStore } from 'pinia';
import { MapService } from '@/maps/MapService';
import type { MapDto } from '@/editor/Map.dto';
import { EditorTool } from '@/editor/EditorTool';
import type { IMapLayer } from '@/types/IMapLayer';

export const useEditorStore = defineStore({
  id: 'editorStore',
  persist: true,
  state: () => ({
    loaded: false,
    showGrid: false,
    showProperties: false,
    showLeftSideBar: false,
    activeTool: EditorTool.DRAW,
    map: null as MapDto | null,
    layers: [] as IMapLayer[],
  }),
  getters: {
    isDrawToolActive: (state) => state.activeTool === EditorTool.DRAW,
    isFillToolActive: (state) => state.activeTool === EditorTool.FILL,
    isEraseToolActive: (state) => state.activeTool === EditorTool.ERASE,
  },
  actions: {
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

    async loadLayersForMap(mapUUID: string) {
      this.layers = await MapService.getMapLayers(mapUUID);
    },

    async loadMap(uuid: string) {
      this.map = await MapService.getMap(uuid);
      console.log('Map', uuid, 'loaded');
    },
  },
});
