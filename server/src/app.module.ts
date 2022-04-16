import { Module } from '@nestjs/common';
import * as dotenv from 'dotenv';
import * as fs from 'fs';
import * as path from 'path';
import { AppController } from './app.controller';
import { AppService } from './app.service';
import { DatabaseModule } from './database/database.module';
import { TileMapsModule } from './tile-maps/tile-maps.module';
import { UsersModule } from './users/users.module';

const ENV = process.env.NODE_ENV || 'prod';

const envFilePath = path.resolve(process.cwd(), `.env.${ENV}`);
const envDefaultFilePath = path.resolve(process.cwd(), `.env`);

if (fs.existsSync(envFilePath)) {
  dotenv.config({ path: envFilePath });
} else {
  dotenv.config({ path: envDefaultFilePath });
}

@Module({
  imports: [UsersModule, TileMapsModule, DatabaseModule],
  controllers: [AppController],
  providers: [AppService],
})
export class AppModule {}
