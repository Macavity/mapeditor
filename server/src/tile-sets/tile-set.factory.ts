export class TileSetFactory {
  static create(
    name: string,
    image: string,
    firstGid: number,
    tileCount: number,
    tileHeight: number,
    tileWidth: number,
    imageHeight: number,
    imageWidth: number,
    margin: number,
    spacing: number
  ): ITileSet {
    return {
      name,
      image,
      firstGid,
      tileCount,
      tileHeight,
      tileWidth,
      imageHeight,
      imageWidth,
      margin,
      spacing,
    };
  }
}
