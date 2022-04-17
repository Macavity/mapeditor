import { defineStore } from 'pinia';
import { TileSetFactory } from '@/tilesets/TileSetFactory';
import { mande } from 'mande';

const api = mande('http://localhost:8085/tile-sets');

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
      this.tileSetEntries = await api.get<ITileSet[]>('');
      console.log(this.tileSetEntries);
    },
  },
});
