import type { CreateMapDto } from '@/dtos/CreateMap.dto';
import api from '@/lib/api';
import type { MapLayer } from '@/types/MapLayer';
import type { TileMap } from '@/types/TileMap';

export class LayerService {
    static async createMap(newMap: CreateMapDto): Promise<TileMap> {
        return api.post<TileMap>('/layers', newMap).then((response) => {
            return response.data;
        });
    }

    static async getLayer(uuid: string): Promise<MapLayer[]> {
        return api.get<MapLayer[]>('/layers/' + uuid).then((response) => {
            return response.data;
        });
    }
}
