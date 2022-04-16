import { defineStore } from "pinia";

const useMapStore = defineStore({
  id: "mapStore",
  state: () => ({
    mapEntries: [],
  }),
  getters: {
    maps: (state) => state.mapEntries,
  },
  actions: {
    loadMaps() {
      const maps = loadMapsFromAPI();
    },
  },
});
