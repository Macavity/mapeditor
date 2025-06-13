export interface TileSelection {
    x: number;
    y: number;
    tileX: number;
    tileY: number;
    width: number;
    height: number;
    startTileX: number;
    startTileY: number;
    endTileX: number;
    endTileY: number;
}

export interface BrushSelectionConfig {
    tileX: number;
    tileY: number;
    brushWidth: number;
    brushHeight: number;
    tilesetImageUrl: string;
    tilesetUuid: string;
}

export interface BrushSelection {
    width: number;
    height: number;
    backgroundImage: string | null;
    tileX: number;
    tileY: number;
    backgroundPosition: string;
    tilesetUuid: string | null;
}

// Helper function to check if a selection is a single tile
export function isSingleTileSelection(selection: TileSelection): boolean {
    return selection.startTileX === selection.endTileX && selection.startTileY === selection.endTileY;
}

// Helper function to create a single tile selection
export function createSingleTileSelection(tileX: number, tileY: number, tileWidth: number, tileHeight: number): TileSelection {
    return {
        x: tileX * tileWidth,
        y: tileY * tileHeight,
        tileX,
        tileY,
        width: tileWidth,
        height: tileHeight,
        startTileX: tileX,
        startTileY: tileY,
        endTileX: tileX,
        endTileY: tileY,
    };
}
