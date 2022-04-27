import { defineStore } from 'pinia';
import { MapService } from '@/maps/MapService';
import type { MapDto } from '@/maps/dtos/Map.dto';
import { EditorTool } from '@/maps/EditorTool';

export const useMapStore = defineStore({
  id: 'mapStore',
  state: () => ({
    loaded: false,
    showGrid: false,
    showProperties: false,
    showLeftSideBar: false,
    activeTool: EditorTool.DRAW,
    mapEntries: [] as IMap[],
    map: null as MapDto | null,
  }),
  getters: {
    maps: (state) => state.mapEntries,
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

    async loadMap(uuid: string) {
      this.map = await MapService.getMap(uuid);
      console.log('Map', uuid, 'loaded');
    },

    async loadMaps() {
      this.mapEntries = await MapService.getMaps();
      this.loaded = true;
      console.log('Maps loaded', this.mapEntries);
    },
  },
});
