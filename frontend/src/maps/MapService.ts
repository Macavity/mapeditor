import type { CreateMapDto } from '@/maps/dtos/CreateMap.dto';
import axios from 'axios';
import { MapDto } from '@/editor/Map.dto';

const URL = 'http://localhost:8085/tile-maps';

export class MapService {
  static async createMap(newMap: CreateMapDto): Promise<IMap> {
    return axios.post<IMap>(URL, newMap).then((response) => {
      return response.data;
    });
  }

  static async getMaps(): Promise<IMap[]> {
    return axios.get<IPaginationResponse<IMap>>(URL).then((response) => {
      return response.data.data;
    });
  }

  static async getMap(uuid: string): Promise<MapDto> {
    return axios.get<MapDto>(URL + '/' + uuid).then((response) => {
      console.log('Map DTO', response.data);
      const dto = response.data;
      return new MapDto(
        dto.uuid,
        dto.name,
        dto.height,
        dto.width,
        dto.tileHeight,
        dto.tileWidth
      );
    });
  }
}
