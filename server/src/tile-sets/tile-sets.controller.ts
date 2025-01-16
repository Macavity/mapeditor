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
import {TileSetsService} from './tile-sets.service';
import {TileSet} from './entities/tile-set.entity';
import {CreateTileSetDto} from './dto/create-tileset.dto';
import {QueryFailedExceptionFilter} from '../filters/query-failed-error.filter';
import {UpdateTileSetDto} from "./dto/update-tileset.dto";

@Controller('tile-sets')
@ApiTags('TileSets')
@UsePipes(new ValidationPipe())
@UseInterceptors(ClassSerializerInterceptor)
@UseFilters(new QueryFailedExceptionFilter())
export class TileSetsController {
  private logger: Logger;

  constructor(private readonly tileSetsService: TileSetsService) {
    this.logger = new Logger(TileSetsController.name);
  }

  @Post()
  async create(@Body() createTileSetDto: CreateTileSetDto): Promise<TileSet> {
    return this.tileSetsService.create(createTileSetDto);
  }

  @Get()
  async findAll(): Promise<TileSet[]> {
    return this.tileSetsService.findAll();
  }

  @Get(':id')
  async findOne(@Param('id') id: string): Promise<TileSet> {
    return this.tileSetsService.findOne(id);
  }

  @Put(':id')
  async update(
      @Param('id') id: string,
      @Body() updateTileSetDto: UpdateTileSetDto,
  ): Promise<TileSet> {
    return this.tileSetsService.update(id, updateTileSetDto);
  }

  @Delete(':id')
  async remove(@Param('id') id: string): Promise<void> {
    return this.tileSetsService.remove(id);
  }
}