import { MapLayerType } from '@/maps/MapLayerType';

interface IMapLayer {
  id: number;
  uuid: string;
  name: string;
  type: MapLayerType;
  height: number;
  width: number;
  x: number;
  y: number;
  z: number;
  data: [];
  visible: boolean;
  opacity: number;
}
