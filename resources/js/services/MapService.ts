import { MapDto } from '@/dtos/Map.dto';
import type { TileMap } from '@/types/maps';
import axios from 'axios';

export class MapService {
    private static readonly BASE_URL = '/api/tile-maps';

    static async getAllMaps(): Promise<TileMap[]> {
        const response = await axios.get<{ data: TileMap[] }>(this.BASE_URL);
        return response.data.data;
    }

    static async getMap(uuid: string): Promise<TileMap> {
        const response = await axios.get<{ data: TileMap }>(`${this.BASE_URL}/${uuid}`);
        return response.data.data;
    }

    static async createMap(data: Partial<TileMap>): Promise<TileMap> {
        const response = await axios.post<{ data: TileMap }>(this.BASE_URL, data);
        return response.data.data;
    }

    static async updateMap(uuid: string, data: Partial<TileMap>): Promise<TileMap> {
        const response = await axios.put<{ data: TileMap }>(`${this.BASE_URL}/${uuid}`, data);
        return response.data.data;
    }

    static async deleteMap(uuid: string): Promise<void> {
        await axios.delete(`${this.BASE_URL}/${uuid}`);
    }

    static async getMaps(): Promise<IMap[]> {
        return axios.get<IMap[]>(this.BASE_URL).then((response) => {
            return response.data;
        });
    }

    static async getMapDto(uuid: string): Promise<MapDto> {
        return axios.get<MapDto>(this.BASE_URL + '/' + uuid).then((response) => {
            console.log('Map DTO', response.data);
            const dto = response.data;
            return new MapDto(dto.uuid, dto.name, dto.height, dto.width, dto.tileHeight, dto.tileWidth);
        });
    }

    // static async getMapLayers(uuid: string) {
    //   return axios.get(this.BASE_URL + '/' + uuid + '/layers').then((response) => {
    //     console.log('MapLayers', response.data);
    //     return response.data;
    //   });
    // }
}
