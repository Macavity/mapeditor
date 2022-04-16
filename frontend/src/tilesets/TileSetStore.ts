import { defineStore } from 'pinia';
import { TileSetFactory } from '@/tilesets/TileSetFactory';

export const useTileSetStore = defineStore({
  id: 'tileSetStore',
  state: () => ({
    tileSetEntries: [] as ITileSet[],
  }),
  getters: {
    tileSets: (state) => state.tileSetEntries,
  },
  actions: {
    loadTileSets() {},
  },
});
