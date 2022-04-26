import type { CreateMapDto } from '@/maps/dtos/CreateMap.dto';
import axios from 'axios';

const URL = 'http://localhost:8085/tile-sets';

export class TileSetService {
  static async getTileSets(): Promise<ITileSet[]> {
    return axios.get<ITileSet[]>(URL).then((response) => {
      return response.data;
    });
  }

  static async getTileSet(uuid: string): Promise<ITileSet> {
    return axios.get<ITileSet>(URL + '/' + uuid).then((response) => {
      return response.data;
    });
  }
}
