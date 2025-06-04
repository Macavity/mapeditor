import { TileSetService } from '@/services/TileSetService';
import type { TileSet } from '@/types/TileSet';
import { defineStore } from 'pinia';

interface TileSetState {
    activeTileSetUUID: string | null;
    tileSetEntries: TileSet[];
    loading: boolean;
    error: string | null;
}

export const useTileSetStore = defineStore('tileSetStore', {
    state: (): TileSetState => ({
        activeTileSetUUID: null,
        tileSetEntries: [],
        loading: false,
        error: null,
    }),
    getters: {
        tileSets: (state): TileSet[] => state.tileSetEntries,
        activeTileSet: (state): TileSet | undefined => {
            return state.tileSetEntries.find((entry) => entry.uuid === state.activeTileSetUUID);
        },
    },
    actions: {
        activateTileSet(uuid: string) {
            this.activeTileSetUUID = uuid;
        },
        addTileSet(tileSet: TileSet) {
            this.tileSetEntries.push(tileSet);
        },
        async loadTileSets() {
            this.loading = true;
            this.error = null;
            try {
                this.tileSetEntries = await TileSetService.getTileSets();
            } catch (error) {
                this.error = 'Failed to load tile sets';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async deleteTileSet(uuid: string) {
            try {
                await TileSetService.deleteTileSet(uuid);
                this.tileSetEntries = this.tileSetEntries.filter((set) => set.uuid !== uuid);
            } catch (error) {
                console.error('Failed to delete tile set:', error);
                throw error;
            }
        },
    },
});
