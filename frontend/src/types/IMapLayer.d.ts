import { MapLayerType } from "@/maps/MapLayerType";

interface IMapLayer {
  id: string;
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
