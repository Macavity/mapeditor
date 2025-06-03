import {defineStore} from 'pinia';
import { useToast } from 'vue-toast-notification';
import { TileSetService } from '@/services/TileSetService';

export const useTileSetStore = defineStore({
  id: 'tileSetStore',
  state: () => ({
    activeTileSetUUID: null as string | null,
    tileSetEntries: [] as ITileSet[],
  }),
  getters: {
    tileSets: (state) =>
      state.tileSetEntries,
    activeTileSet: (state) => {
      return state.tileSetEntries.find(
        (entry) => entry.uuid === state.activeTileSetUUID
      );
    },
  },
  actions: {
    activateTileSet(uuid: string) {
      this.activeTileSetUUID = uuid;
    },
    addTileSet(tileSet: ITileSet) {
      this.tileSetEntries.push(tileSet);
    },
    async loadTileSets() {
      this.tileSetEntries = await TileSetService.getTileSets();
    },
    async deleteTileSet(uuid: string) {
      try {
        await TileSetService.deleteTileSet(uuid);
        this.tileSetEntries = this.tileSetEntries.filter(set => set.uuid !== uuid);
        useToast().success('Tileset deleted successfully');
      } catch (error) {
        console.error('Failed to delete tileset:', error);
        useToast().error('Failed to delete tileset');
      }
    }
  },
});
