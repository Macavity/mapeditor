import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { databaseConfig } from './database.provider';
import { DatabaseService } from './database.service';

@Module({
  imports: [TypeOrmModule.forRoot(databaseConfig())],
  providers: [DatabaseService],
})
export class DatabaseModule {}
