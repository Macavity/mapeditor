import type { CreateMapDto } from '@/maps/dtos/CreateMap.dto';
import { mande } from 'mande';
import axios from 'axios';

const URL = 'http://localhost:8085/tile-maps';

export class MapService {
  static async createMap(newMap: CreateMapDto): Promise<IMap> {
    return axios.post<IMap>(URL, newMap).then((response) => {
      return response.data;
    });
  }

  static async getMaps(): Promise<IMap[]> {
    return axios.get<IMap[]>(URL).then((response) => {
      return response.data;
    });
  }
}
