import {Module} from '@nestjs/common';
import {TypeOrmModule} from '@nestjs/typeorm';
import {databaseConfig} from './database.provider';
import {DatabaseService} from './database.service';
import {TileSet} from "../tile-sets/entities/tile-set.entity";
import {TileSetSeeder} from "./seeders/tile-set-seeder.service";

@Module({
  imports: [TypeOrmModule.forRoot(databaseConfig()), TypeOrmModule.forFeature([TileSet])],
  providers: [DatabaseService, TileSetSeeder],
})
export class DatabaseModule {}
