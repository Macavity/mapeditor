import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { TypeOrmCrudService } from '@nestjsx/crud-typeorm';
import { Repository } from 'typeorm';
import { Layer } from './entities/layer.entity';
import { TileMap } from './entities/tile-map.entity';
import { LayerFactory } from './layer.factory';

@Injectable()
export class LayersService extends TypeOrmCrudService<Layer> {
    constructor(
        @InjectRepository(Layer) public repository: Repository<Layer>
    ) {
        super(repository);
    }

    async findByMap(tileMap: TileMap): Promise<Layer[] | null> {
        const results = await this.repository.find({
            where: {
                tileMap,
            },
        });

        if (results) {
            return results;
        }

        return null;
    }

    async createDefaultLayers(tileMap: TileMap) {
        const layers = await this.findByMap(tileMap);

        if (layers && layers.length > 0) {
            return;
        }

        const backgroundLayer = LayerFactory.createNewBackground(tileMap);

        return this.repository.save(backgroundLayer);
    }

    async findSortedLayersForMap(uuid: string) {
        return Promise.resolve(undefined);
    }

    async findByMapUuid(uuid: string) {
        return this.repository.find({
            where: {
                tileMap: uuid,
            },
        });
    }
}
