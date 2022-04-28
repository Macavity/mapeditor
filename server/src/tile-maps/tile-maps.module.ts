import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Layer } from './entities/layer.entity';
import { TileMap } from './entities/tile-map.entity';
import { LayersController } from './layers.controller';
import { LayersService } from './layers.service';
import { TileMapsController } from './tile-maps.controller';
import { TileMapsService } from './tile-maps.service';

@Module({
    imports: [TypeOrmModule.forFeature([TileMap, Layer])],
    controllers: [TileMapsController, LayersController],
    providers: [TileMapsService, LayersService],
})
export class TileMapsModule {
}
