import {
    ClassSerializerInterceptor,
    Controller,
    Logger,
    UseFilters,
    UseInterceptors,
    UsePipes,
    ValidationPipe
} from '@nestjs/common';
import { ApiTags } from '@nestjs/swagger';
import { Crud, CrudController } from '@nestjsx/crud';
import { QueryFailedExceptionFilter } from '../filters/query-failed-error.filter';
import { TileMap } from './entities/tile-map.entity';
import { TileMapsService } from './tile-maps.service';

@Controller('tile-maps')
@ApiTags('TileMaps')
@Crud({
    model: {
        type: TileMap,
    },
    params: {
        uuid: {
            field: 'uuid',
            type: 'uuid',
            primary: true,
        }
    },
    query: {
        alwaysPaginate: true,
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
export class TileMapsController implements CrudController<TileMap> {
    private logger: Logger;

    constructor(public readonly service: TileMapsService) {
        this.logger = new Logger(TileMapsController.name);
    }
}
