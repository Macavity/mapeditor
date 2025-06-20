import { MapDto } from '@/dtos/Map.dto';
import api from '@/lib/api';
import { MapLayer, MapLayerType } from '@/types/MapLayer';
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

    static async updateLayer(mapUuid: string, layerUuid: string, data: Partial<MapLayer>): Promise<MapLayer> {
        const response = await api.put<{ data: MapLayer }>(`${this.BASE_URL}/${mapUuid}/layers/${layerUuid}`, data);
        return response.data.data;
    }

    static async saveLayers(mapUuid: string, layers: MapLayer[]): Promise<MapLayer[]> {
        // Ensure each layer's data is properly formatted
        const formattedLayers = layers.map((layer) => ({
            ...layer,
            data: layer.data.map((tile) => ({
                x: tile.x,
                y: tile.y,
                brush: {
                    tileset: tile.brush.tileset,
                    tileX: tile.brush.tileX,
                    tileY: tile.brush.tileY,
                },
            })),
        }));

        const response = await api.put<{ data: MapLayer[] }>(`${this.BASE_URL}/${mapUuid}/layers`, { layers: formattedLayers });
        return response.data.data;
    }

    static async saveLayerData(mapUuid: string, layerUuid: string, tileData: any[]): Promise<MapLayer> {
        const response = await api.put<{ data: MapLayer }>(`${this.BASE_URL}/${mapUuid}/layers/${layerUuid}/data`, { data: tileData });
        return response.data.data;
    }

    static async createSkyLayer(
        mapUuid: string,
        options?: {
            name?: string;
            x?: number;
            y?: number;
            z?: number;
            visible?: boolean;
            opacity?: number;
        },
    ): Promise<MapLayer> {
        return this.createLayer(mapUuid, MapLayerType.Sky, options);
    }

    static async createFloorLayer(
        mapUuid: string,
        options?: {
            name?: string;
            x?: number;
            y?: number;
            z?: number;
            visible?: boolean;
            opacity?: number;
        },
    ): Promise<MapLayer> {
        return this.createLayer(mapUuid, MapLayerType.Floor, options);
    }

    static async createObjectLayer(
        mapUuid: string,
        options?: {
            name?: string;
            x?: number;
            y?: number;
            z?: number;
            visible?: boolean;
            opacity?: number;
        },
    ): Promise<MapLayer> {
        return this.createLayer(mapUuid, MapLayerType.Object, options);
    }

    static async createFieldTypeLayer(
        mapUuid: string,
        options?: {
            name?: string;
            x?: number;
            y?: number;
            z?: number;
            visible?: boolean;
            opacity?: number;
        },
    ): Promise<MapLayer> {
        return this.createLayer(mapUuid, MapLayerType.FieldTypes, options);
    }

    /**
     * Generic layer creation method
     */
    private static async createLayer(
        mapUuid: string,
        layerType: MapLayerType,
        options?: {
            name?: string;
            x?: number;
            y?: number;
            z?: number;
            visible?: boolean;
            opacity?: number;
        },
    ): Promise<MapLayer> {
        // Convert enum value to URL format (underscore to hyphen)
        const urlLayerType = layerType.replace('_', '-');
        const response = await api.post<MapLayer>(`${this.BASE_URL}/${mapUuid}/layers/${urlLayerType}`, options || {});
        return response.data;
    }

    static async getLayerCounts(mapUuid: string): Promise<{
        counts: {
            background: number;
            floor: number;
            sky: number;
            field_type: number;
            object: number;
        };
        limits: {
            floor: number;
            sky: number;
        };
        canCreate: {
            floor: boolean;
            sky: boolean;
        };
    }> {
        const response = await api.get(`${this.BASE_URL}/${mapUuid}/layer-counts`);
        return response.data;
    }

    static async deleteLayer(mapUuid: string, layerUuid: string): Promise<void> {
        await api.delete(`${this.BASE_URL}/${mapUuid}/layers/${layerUuid}`);
    }
}
