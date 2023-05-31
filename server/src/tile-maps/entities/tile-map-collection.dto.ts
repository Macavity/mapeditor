import { Type } from 'class-transformer';

export class TileMapCollectionDto {
  @Type(() => TileMapCollectionEntryDto)
  public entries: TileMapCollectionEntryDto[];
}

export class TileMapCollectionEntryDto {
  public uuid: string;
  public name: string;
}
