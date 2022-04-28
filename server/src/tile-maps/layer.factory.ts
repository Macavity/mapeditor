import { LayerType } from './entities/layer-type';
import { Layer } from './entities/layer.entity';
import { TileMap } from './entities/tile-map.entity';


export class LayerFactory {
    static createNewBackground(map: TileMap): Layer {
        const layer = new Layer();
        layer.tileMap = map;
        layer.name = 'Background';
        layer.type = LayerType.Background;
        layer.height = map.height;
        layer.width = map.width;
        layer.x = 0;
        layer.y = 0;
        layer.z = 0;
        layer.data = [];
        layer.visible = true;
        layer.opacity = 1;

        return layer;
    }
}
