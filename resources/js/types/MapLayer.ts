export enum MapLayerType {
    Background,
    Floor,
    Sky,
    FieldTypes,
}

export type Tile = {
    x: number;
    y: number;
    brush: {
        tileset: string;
        tileX: number;
        tileY: number;
    };
};

export type MapLayer = {
    id: number;
    uuid: string;
    name: string;
    type: MapLayerType;
    height: number;
    width: number;
    x: number;
    y: number;
    z: number;
    data: Tile[];
    visible: boolean;
    opacity: number;
};
