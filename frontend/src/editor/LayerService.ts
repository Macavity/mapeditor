import type { CreateMapDto } from '@/maps/dtos/CreateMap.dto';
import axios from 'axios';
import type { IMapLayer } from '@/types/IMapLayer';

const URL = import.meta.env.VITE_API_URL + '/layers';

export class LayerService {
  static async createMap(newMap: CreateMapDto): Promise<IMap> {
    return axios.post<IMap>(URL, newMap).then((response) => {
      return response.data;
    });
  }

  static async getLayer(uuid: string): Promise<IMapLayer[]> {
    return axios.get<IMapLayer[]>(URL + '/' + uuid).then((response) => {
      return response.data;
    });
  }
}
