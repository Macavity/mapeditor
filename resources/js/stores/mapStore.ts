import { MapService } from '@/services/MapService';
import type { TileMap } from '@/types/maps';
import { defineStore } from 'pinia';

interface MapState {
    maps: TileMap[];
    loading: boolean;
    error: string | null;
}

export const useMapStore = defineStore('maps', {
    state: (): MapState => ({
        maps: [],
        loading: false,
        error: null,
    }),

    getters: {
        loaded: (state) => state.maps.length > 0,
    },

    actions: {
        async loadMaps() {
            if (this.loading) return;

            this.loading = true;
            this.error = null;

            try {
                this.maps = await MapService.getAllMaps();
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Failed to load maps';
                console.error('Error loading maps:', error);
            } finally {
                this.loading = false;
            }
        },

        async createMap(data: Partial<TileMap>) {
            this.loading = true;
            this.error = null;

            try {
                const newMap = await MapService.createMap(data);
                this.maps.unshift(newMap);
                return newMap;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Failed to create map';
                console.error('Error creating map:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateMap(uuid: string, data: Partial<TileMap>) {
            this.loading = true;
            this.error = null;

            try {
                const updatedMap = await MapService.updateMap(uuid, data);
                const index = this.maps.findIndex((m) => m.uuid === uuid);
                if (index !== -1) {
                    this.maps[index] = updatedMap;
                }
                return updatedMap;
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Failed to update map';
                console.error('Error updating map:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteMap(uuid: string) {
            this.loading = true;
            this.error = null;

            try {
                await MapService.deleteMap(uuid);
                this.maps = this.maps.filter((m) => m.uuid !== uuid);
            } catch (error) {
                this.error = error instanceof Error ? error.message : 'Failed to delete map';
                console.error('Error deleting map:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
