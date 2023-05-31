import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { TypeOrmCrudService } from '@nestjsx/crud-typeorm';
import { Repository } from 'typeorm';
import { TileSet } from './entities/tile-set.entity';

@Injectable()
export class TileSetsService extends TypeOrmCrudService<TileSet> {
  constructor(
    @InjectRepository(TileSet) public repository: Repository<TileSet>,
  ) {
    super(repository);
  }
}
