import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Layer } from '../layers/entities/layer.entity';
import { TileMap } from './entities/tile-map.entity';
import { TileMapsController } from './tile-maps.controller';
import { TileMapsService } from './tile-maps.service';

@Module({
    imports: [TypeOrmModule.forFeature([TileMap, Layer])],
    controllers: [TileMapsController],
    providers: [TileMapsService],
})
export class TileMapsModule {
}
