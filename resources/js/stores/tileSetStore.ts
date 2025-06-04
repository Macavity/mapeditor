import type { TileSet } from '@/types/TileSet';
import axios from 'axios';
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
                const response = await axios.get('/api/tile-sets');
                this.tileSetEntries = response.data.data;
            } catch (error) {
                this.error = 'Failed to load tile sets';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async deleteTileSet(uuid: string) {
            try {
                await axios.delete(`/api/tile-sets/${uuid}`);
                this.tileSetEntries = this.tileSetEntries.filter((set) => set.uuid !== uuid);
            } catch (error) {
                this.error = 'Failed to delete tile set';
                throw error;
            }
        },
        async importTileSet(formData: FormData) {
            try {
                const response = await axios.post('/api/tile-sets/import', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
                this.tileSetEntries.push(response.data.data);
            } catch (error) {
                this.error = 'Failed to import tile set';
                throw error;
            }
        },
    },
});
