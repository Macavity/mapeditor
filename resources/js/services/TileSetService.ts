import type { TileSet } from '@/types/TileSet';
import axios from 'axios';

export class TileSetService {
    static async getTileSets(): Promise<TileSet[]> {
        const response = await axios.get('/api/tile-sets');
        return response.data.data;
    }

    static async getTileSet(uuid: string): Promise<TileSet> {
        const response = await axios.get(`/api/tile-sets/${uuid}`);
        return response.data.data;
    }

    static async createTileSet(tileSet: Partial<TileSet>): Promise<TileSet> {
        const response = await axios.post('/api/tile-sets', tileSet);
        return response.data.data;
    }

    static async updateTileSet(uuid: string, tileSet: Partial<TileSet>): Promise<TileSet> {
        const response = await axios.put(`/api/tile-sets/${uuid}`, tileSet);
        return response.data.data;
    }

    static async deleteTileSet(uuid: string): Promise<void> {
        await axios.delete(`/api/tile-sets/${uuid}`);
    }
}
