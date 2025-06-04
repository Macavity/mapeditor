export type TileSet = {
    uuid: string;
    createdAt: string;
    updatedAt: string;

    name: string;

    firstGid: number;

    imageWidth: number;
    imageHeight: number;

    tileWidth: number;
    tileHeight: number;
    tileCount: number;

    margin: number;
    spacing: number;
}
