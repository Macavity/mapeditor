interface IMap {
  name: string;
  creatorId: string;
  creatorName: string;
  width: number;
  height: number;
  tileWidth: number;
  tileHeight: number;
  tileSets: [];
  layers: IMapLayer[];
  nextObjectId: number;
  orientation: string;
  renderOrder: string;
  properties: any;
  version: number;
}
