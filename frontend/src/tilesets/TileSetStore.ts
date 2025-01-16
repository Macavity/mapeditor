import {defineStore} from 'pinia';
import {TileSetService} from "@/tilesets/TileSetService";

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
  },
});
