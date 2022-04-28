export class MapDto implements IMap {
  constructor(
    public uuid: string,
    public name: string,

    public height: number,
    public width: number,
    public tileHeight: number,
    public tileWidth: number
  ) {}
}
