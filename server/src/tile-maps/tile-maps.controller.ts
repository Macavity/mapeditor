import {
  Body,
  ClassSerializerInterceptor,
  Controller,
  Delete,
  Get,
  Logger,
  Param,
  Post,
  Put,
  UseFilters,
  UseInterceptors,
  UsePipes,
  ValidationPipe,
} from '@nestjs/common';
import {ApiTags} from '@nestjs/swagger';
import {NotFoundError} from 'rxjs';
import {QueryFailedExceptionFilter} from '../filters/query-failed-error.filter';
import {TileMap} from './entities/tile-map.entity';
import {LayersService} from './layers.service';
import {TileMapsService} from './tile-maps.service';
import {InjectRepository} from "@nestjs/typeorm";
import {Repository} from 'typeorm';
import {CreateTileMapDto} from './dto/create-tile-map.dto';
import {UpdateTileMapDto} from "./dto/update-tile-map.dto";

@Controller('tile-maps')
@ApiTags('TileMaps')
@UsePipes(new ValidationPipe())
@UseInterceptors(ClassSerializerInterceptor)
@UseFilters(new QueryFailedExceptionFilter())
export class TileMapsController {
  private logger: Logger;

  constructor(
    @InjectRepository(TileMap) repo: Repository<TileMap>,
    public readonly service: TileMapsService,
    private readonly layersService: LayersService,
  ) {
    this.logger = new Logger(TileMapsController.name);
  }

  @Get()
  async getAll() {
    return this.service.find();
  }

  @Get('/:uuid')
  async getOne(@Param('uuid') uuid: string) {
    const map = await this.service.findByUuid(uuid);
    if (!map) {
      throw new NotFoundError(`Map with UUID ${uuid} not found`);
    }
    return map;
  }

  @Post()
  async create(@Body() createTileMapDto: CreateTileMapDto) {
    return this.service.create(createTileMapDto);
  }

  @Put('/:uuid')
  async update(@Param('uuid') uuid: string, @Body() updateTileMapDto: UpdateTileMapDto) {
    return this.service.update(uuid, updateTileMapDto);
  }

  @Delete('/:uuid')
  async delete(@Param('uuid') uuid: string) {
    return this.service.delete(uuid);
  }

  @Get('/:uuid/layers')
  async getLayers(@Param('uuid') uuid: string) {
    const map = await this.service.findByUuid(uuid);

    if (!map) {
      throw new NotFoundError(`Map with UUID ${uuid} not found`);
    }

    const layers = await this.layersService.findByMap(map);

    if (layers.length === 0) {
      this.logger.log(
        'Map ' + uuid + ' has no layers => Create default layers.',
      );
      await this.layersService.createDefaultLayers(map);
    }

    return layers.sort((a, b) => a.z - b.z);
  }
}
