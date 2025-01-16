import {PartialType} from '@nestjs/swagger';
import {CreateTileSetDto} from './create-tileset.dto';

export class UpdateTileSetDto extends PartialType(CreateTileSetDto) {}