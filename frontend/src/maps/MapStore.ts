import { defineStore } from 'pinia';
import { MapService } from '@/maps/MapService';

export const useMapStore = defineStore({
  id: 'mapStore',
  state: () => ({
    loaded: false,
    mapEntries: [] as IMap[],
  }),
  getters: {
    maps: (state) => state.mapEntries,
  },
  actions: {
    async loadMaps() {
      this.mapEntries = await MapService.getMaps();
      this.loaded = true;
      console.log('Maps loaded', this.mapEntries);
    },
  },
});
