import {Injectable, NotFoundException} from '@nestjs/common';
import {InjectRepository} from '@nestjs/typeorm';
import {Repository} from 'typeorm';
import {TileSet} from './entities/tile-set.entity';
import {CreateTileSetDto} from './dto/create-tileset.dto';
import {UpdateTileSetDto} from './dto/update-tileset.dto';

@Injectable()
export class TileSetsService {
  constructor(
      @InjectRepository(TileSet) private readonly tileSetRepository: Repository<TileSet>,
  ) {}

  async create(createTileSetDto: CreateTileSetDto): Promise<TileSet> {
    const tileSet = this.tileSetRepository.create(createTileSetDto);
    return this.tileSetRepository.save(tileSet);
  }

  async findAll(): Promise<TileSet[]> {
    return this.tileSetRepository.find();
  }

  async findOne(id: string): Promise<TileSet> {
    const tileSet = await this.tileSetRepository.findOne({ where: { uuid: id } });
    if (!tileSet) {
      throw new NotFoundException(`TileSet with id ${id} not found`);
    }
    return tileSet;
  }

  async update(id: string, updateTileSetDto: UpdateTileSetDto): Promise<TileSet> {
    await this.tileSetRepository.update({ uuid: id }, updateTileSetDto);
    const updatedTileSet = await this.tileSetRepository.findOne({ where: { uuid: id } });
    if (!updatedTileSet) {
      throw new NotFoundException(`TileSet with id ${id} not found`);
    }
    return updatedTileSet;
  }

  async remove(id: string): Promise<void> {
    const result = await this.tileSetRepository.delete({ uuid: id });
    if (result.affected === 0) {
      throw new NotFoundException(`TileSet with id ${id} not found`);
    }
  }
}