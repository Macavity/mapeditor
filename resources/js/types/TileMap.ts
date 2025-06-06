export interface TileMap {
    uuid: string;
    name: string;
    width: number;
    height: number;
    tile_width: number;
    tile_height: number;
    created_at: string;
    updated_at: string;
    external_creator?: string;
    creator?: {
        id: number;
        name: string;
        email: string;
    };
    layers?: Array<{
        id: number;
        name: string;
    }>;
}
