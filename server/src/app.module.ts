import { Module } from '@nestjs/common';
import { AppController } from './app.controller';
import { AppService } from './app.service';
import { DatabaseModule } from './database/database.module';
import { TileMapsModule } from './tile-maps/tile-maps.module';
import { UsersModule } from './users/users.module';

@Module({
  imports: [UsersModule, TileMapsModule, DatabaseModule],
  controllers: [AppController],
  providers: [AppService],
})
export class AppModule {}
