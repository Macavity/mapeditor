import { defineStore } from 'pinia';

export const useTileSetStore = defineStore({
  id: 'tileSetStore',
  state: () => ({
    activeTileSetUUID: null as string | null,
    tileSetEntries: [] as ITileSet[],
  }),
  getters: {
    tileSets: (state) =>
      state.tileSetEntries.filter((entry) => entry.name !== '000-Types'),
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
      //this.tileSetEntries = await TileSetService.getTileSets();
    },
  },
});
