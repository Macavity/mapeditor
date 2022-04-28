import { defineStore } from 'pinia';
import { MapService } from '@/maps/MapService';
import type { MapDto } from '@/editor/Map.dto';
import { EditorTool } from '@/editor/EditorTool';

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

    async loadMap(uuid: string) {
      this.map = await MapService.getMap(uuid);
      console.log('Map', uuid, 'loaded');
    },
  },
});
