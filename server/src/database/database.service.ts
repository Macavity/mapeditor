import {Injectable} from '@nestjs/common';
import {TileSetSeeder} from "./seeders/tile-set-seeder.service";

@Injectable()
export class DatabaseService {
    constructor(private readonly tileSetSeeder: TileSetSeeder) {}

    async onModuleInit() {
        await this.tileSetSeeder.seed();
    }
}
