import { Module } from "@nestjs/common";
import { TypeOrmModule } from "@nestjs/typeorm";
import { TileSet } from "./entities/tile-set.entity";
import { TileSetsController } from "./tile-sets.controller";
import { TileSetsService } from "./tile-sets.service";

@Module({
    imports: [TypeOrmModule.forFeature([TileSet])],
    controllers: [TileSetsController],
    providers: [TileSetsService]
})
export class TileSetsModule {
}
