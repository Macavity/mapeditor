import { TypeOrmModuleOptions } from '@nestjs/typeorm';
import { Layer } from '../tile-maps/entities/layer.entity';
import { TileMap } from '../tile-maps/entities/tile-map.entity';
import { User } from '../users/entities/user.entity';

export function databaseConfig(): TypeOrmModuleOptions {
  return {
    type: 'postgres',
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT),
    username: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    entities: [Layer, TileMap, User],
    // entities: [__dirname ,'..' , '**', '*.entity.ts'],
    synchronize: true,
  };
}
