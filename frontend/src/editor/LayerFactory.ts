import type { IMapLayer } from '@/types/IMapLayer';
import { MapLayerType } from '@/maps/MapLayerType';

export class LayerFactory {
  static createNewBackground(height: number, width: number): IMapLayer {
    return {
      id: null,
      name: 'Background',
      type: MapLayerType.Background,
      height,
      width,
      x: 0,
      y: 0,
      z: 0,
      data: [],
      visible: true,
      opacity: 1,
    };
  }
}
