import { MapDto } from '@/dtos/Map.dto';
import api from '@/lib/api';
import type { MapLayer } from '@/types/MapLayer';
import type { TileMap } from '@/types/TileMap';

export class MapService {
    private static readonly BASE_URL = '/tile-maps';

    static async getAllMaps(): Promise<TileMap[]> {
        const response = await api.get<{ data: TileMap[] }>(this.BASE_URL);
        return response.data.data;
    }

    static async getMap(uuid: string): Promise<TileMap> {
        const response = await api.get<{ data: TileMap }>(`${this.BASE_URL}/${uuid}`);
        return response.data.data;
    }

    static async createMap(data: Partial<TileMap>): Promise<TileMap> {
        const response = await api.post<{ data: TileMap }>(this.BASE_URL, data);
        return response.data.data;
    }

    static async updateMap(uuid: string, data: Partial<TileMap>): Promise<TileMap> {
        const response = await api.put<{ data: TileMap }>(`${this.BASE_URL}/${uuid}`, data);
        return response.data.data;
    }

    static async deleteMap(uuid: string): Promise<void> {
        await api.delete(`${this.BASE_URL}/${uuid}`);
    }

    static async getMaps(): Promise<TileMap[]> {
        return api.get<TileMap[]>(this.BASE_URL).then((response) => {
            return response.data;
        });
    }

    static async getMapDto(uuid: string): Promise<MapDto> {
        const response = await api.get<{ data: TileMap }>(`${this.BASE_URL}/${uuid}`);
        const data = response.data.data;
        return new MapDto(data.uuid, data.name, data.height, data.width, data.tile_height, data.tile_width);
    }

    static async getMapLayers(uuid: string): Promise<MapLayer[]> {
        const response = await api.get<{ data: MapLayer[] }>(`${this.BASE_URL}/${uuid}/layers`);
        return response.data.data;
    }
}
