export enum MapLayerType {
    Background = 'background',
    Floor = 'floor',
    Sky = 'sky',
    Object = 'object',
    FieldType = 'field_type',
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

export type FieldTypeTile = {
    x: number;
    y: number;
    fieldType: number;
};

export type ObjectTile = {
    x: number;
    y: number;
    objectType: number;
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
    data: Tile[] | FieldTypeTile[] | ObjectTile[];
    visible: boolean;
    opacity: number;
};

// Type guards
export function isTileLayer(layer: MapLayer): layer is MapLayer & { data: Tile[] } {
    return layer.type !== MapLayerType.FieldType && layer.type !== MapLayerType.Object;
}

export function isFieldTypeLayer(layer: MapLayer): layer is MapLayer & { data: FieldTypeTile[] } {
    return layer.type === MapLayerType.FieldType;
}

export function isObjectLayer(layer: MapLayer): layer is MapLayer & { data: ObjectTile[] } {
    return layer.type === MapLayerType.Object;
}

export function isTile(item: Tile | FieldTypeTile | ObjectTile): item is Tile {
    return 'brush' in item;
}

export function isFieldTypeTile(item: Tile | FieldTypeTile | ObjectTile): item is FieldTypeTile {
    return 'fieldType' in item;
}

export function isObjectTile(item: Tile | FieldTypeTile | ObjectTile): item is ObjectTile {
    return 'objectType' in item;
}
