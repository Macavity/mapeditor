import {Injectable} from '@nestjs/common';
import {InjectRepository} from '@nestjs/typeorm';
import {Repository} from 'typeorm';
import tileSetSeeds from './tileset-seeds.json';
import {TileSet} from "../../tile-sets/entities/tile-set.entity";

@Injectable()
export class TileSetSeeder {
    constructor(
        @InjectRepository(TileSet)
        private readonly tileSetRepository: Repository<TileSet>,
    ) {}

    async seed() {
        const count = await this.tileSetRepository.count();
        if (count === 0) {
            for (const tileSetSeed of tileSetSeeds) {
                const tileSets = this.tileSetRepository.create(tileSetSeed);
                await this.tileSetRepository.save(tileSets);
            }
        }
    }
}