import { defineStore } from 'pinia';
import { TileSetService } from '@/tilesets/TileSetService';

export const useTileSetStore = defineStore({
  id: 'tileSetStore',
  state: () => ({
    tileSetEntries: [] as ITileSet[],
  }),
  getters: {
    tileSets: (state) => state.tileSetEntries,
  },
  actions: {
    async loadTileSets() {
      this.tileSetEntries = await TileSetService.getTileSets();
    },
  },
});
