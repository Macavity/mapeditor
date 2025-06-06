import api from '@/lib/api';
import type { TileSet } from '@/types/TileSet';

export class TileSetService {
    static async getTileSets(): Promise<TileSet[]> {
        const response = await api.get('/tile-sets');
        return response.data.data;
    }

    static async getTileSet(uuid: string): Promise<TileSet> {
        const response = await api.get(`/tile-sets/${uuid}`);
        return response.data.data;
    }

    static async createTileSet(tileSet: Partial<TileSet>): Promise<TileSet> {
        const response = await api.post('/tile-sets', tileSet);
        return response.data.data;
    }

    static async updateTileSet(uuid: string, tileSet: Partial<TileSet>): Promise<TileSet> {
        const response = await api.put(`/tile-sets/${uuid}`, tileSet);
        return response.data.data;
    }

    static async deleteTileSet(uuid: string): Promise<void> {
        await api.delete(`/tile-sets/${uuid}`);
    }
}
