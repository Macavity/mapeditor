import {
  ClassSerializerInterceptor,
  Controller,
  Logger,
  UseFilters,
  UseInterceptors,
  UsePipes,
  ValidationPipe,
} from '@nestjs/common';
import { ApiTags } from '@nestjs/swagger';
import { Crud, CrudController } from '@nestjsx/crud';
import { QueryFailedExceptionFilter } from '../filters/query-failed-error.filter';
import { TileSet } from './entities/tile-set.entity';
import { TileSetsService } from './tile-sets.service';

@Controller('tile-sets')
@ApiTags('TileSets')
@Crud({
  model: {
    type: TileSet,
  },
  query: {
    sort: [
      {
        field: 'name',
        order: 'ASC',
      },
    ],
  },
})
@UsePipes(new ValidationPipe())
@UseInterceptors(ClassSerializerInterceptor)
@UseFilters(new QueryFailedExceptionFilter())
export class TileSetsController implements CrudController<TileSet> {
  private logger: Logger;

  constructor(public readonly service: TileSetsService) {
    this.logger = new Logger(TileSetsController.name);
  }
}
