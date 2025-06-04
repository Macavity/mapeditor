import type { CreateMapDto } from '@/dtos/CreateMap.dto';
import type { MapLayer } from '@/types/MapLayer';
import type { TileMap } from '@/types/TileMap';
import axios from 'axios';

const URL = import.meta.env.VITE_API_URL + '/layers';

export class LayerService {
    static async createMap(newMap: CreateMapDto): Promise<TileMap> {
        return axios.post<TileMap>(URL, newMap).then((response) => {
            return response.data;
        });
    }

    static async getLayer(uuid: string): Promise<MapLayer[]> {
        return axios.get<MapLayer[]>(URL + '/' + uuid).then((response) => {
            return response.data;
        });
    }
}
