import {
    ClassSerializerInterceptor,
    Controller,
    Get,
    Logger,
    Param,
    UseFilters,
    UseInterceptors,
    UsePipes,
    ValidationPipe
} from '@nestjs/common';
import { ApiTags } from '@nestjs/swagger';
import { Crud, CrudController } from '@nestjsx/crud';
import { NotFoundError } from 'rxjs';
import { QueryFailedExceptionFilter } from '../filters/query-failed-error.filter';
import { TileMap } from './entities/tile-map.entity';
import { LayersService } from './layers.service';
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

    constructor(public readonly service: TileMapsService, private readonly layersService: LayersService) {
        this.logger = new Logger(TileMapsController.name);
    }

    @Get('/:uuid/layers')
    async getLayers(
        @Param('uuid') uuid: string,
    ) {
        const map = await this.service.findByUuid(uuid);

        if (!map) {
            throw new NotFoundError(`Map with UUID ${uuid} not found`);
        }

        const layers = await this.layersService.findByMap(map);

        if (layers.length === 0) {
            this.logger.log('Map ' + uuid + ' has no layers => Create default layers.');
            await this.layersService.createDefaultLayers(map);
        }

        return layers.sort((a, b) => a.z - b.z);
    }
}
