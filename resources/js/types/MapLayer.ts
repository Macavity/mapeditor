export enum MapLayerType {
    Background = 'background',
    Floor = 'floor',
    Sky = 'sky',
    Object = 'object',
    Player = 'player',
    FieldTypes = 'field_type',
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
