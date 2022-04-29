import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { TypeOrmCrudService } from '@nestjsx/crud-typeorm';
import { Repository } from 'typeorm';
import { TileMap } from './entities/tile-map.entity';

@Injectable()
export class TileMapsService extends TypeOrmCrudService<TileMap> {
    constructor(
        @InjectRepository(TileMap) public repository: Repository<TileMap>,
    ) {
        super(repository);
    }

    async findByUuid(uuid: string) {
        return this.repository.findOne({
            uuid,
        });
    }
}
