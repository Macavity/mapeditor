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
import { Crud } from '@nestjsx/crud';
import { QueryFailedExceptionFilter } from '../filters/query-failed-error.filter';
import { Layer } from './entities/layer.entity';
import { LayersService } from './layers.service';

@Controller('layers')
@ApiTags('Layers')
@Crud({
    model: {
        type: Layer,
    },
    params: {
        uuid: {
            field: 'uuid',
            type: 'uuid',
            primary: true,
        }
    },
    query: {
        sort: [
            {
                field: 'z',
                order: 'DESC',
            },
        ],
    },
})
@UsePipes(new ValidationPipe())
@UseInterceptors(ClassSerializerInterceptor)
@UseFilters(new QueryFailedExceptionFilter())
export class LayersController {
    private logger: Logger;

    constructor(private readonly service: LayersService) {
        this.logger = new Logger(LayersController.name);
    }
}
