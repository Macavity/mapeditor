import axios from 'axios';

const URL = import.meta.env.VITE_API_URL + '/tile-sets';

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

  static async deleteTileSet(uuid: string): Promise<void> {
    return axios.delete(`${URL}/${uuid}`).then((response) => {
      return response.data;
    });
  }
}
