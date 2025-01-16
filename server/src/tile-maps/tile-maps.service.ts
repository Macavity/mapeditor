import {Injectable} from '@nestjs/common';
import {InjectRepository} from '@nestjs/typeorm';
import {Repository} from 'typeorm';
import {TileMap} from './entities/tile-map.entity';
import {CreateTileMapDto} from './dto/create-tile-map.dto';
import {UpdateTileMapDto} from './dto/update-tile-map.dto';

@Injectable()
export class TileMapsService {
  constructor(
      @InjectRepository(TileMap) public repository: Repository<TileMap>,
  ) {}

  async find() {
    return this.repository.find();
  }

  async findByUuid(uuid: string) {
    return this.repository.findOne({
      where: { uuid },
    });
  }

  async create(createTileMapDto: CreateTileMapDto) {
    const tileMap = this.repository.create(createTileMapDto);
    return this.repository.save(tileMap);
  }

  async update(uuid: string, updateTileMapDto: UpdateTileMapDto) {
    await this.repository.update({ uuid }, updateTileMapDto);
    return this.findByUuid(uuid);
  }

  async delete(uuid: string) {
    const result = await this.repository.delete({ uuid });
    return result.affected > 0;
  }
}