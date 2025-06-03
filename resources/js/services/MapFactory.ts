export class MapFactory {
  static createFromObject(map: IMap): IMap {
    return {
      ...map,
    };
  }
}
