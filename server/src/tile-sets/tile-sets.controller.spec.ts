import { Test, TestingModule } from '@nestjs/testing';
import { mockRepositoryProvider } from '../../test/mocks/repositories';
import { TileSet } from './entities/tile-set.entity';
import { TileSetsController } from './tile-sets.controller';
import { TileSetsService } from './tile-sets.service';

describe('TileSetsController', () => {
  let controller: TileSetsController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [TileSetsController],
      providers: [TileSetsService, mockRepositoryProvider(TileSet)],
    }).compile();

    controller = module.get<TileSetsController>(TileSetsController);
  });

  it('should be defined', () => {
    expect(controller).toBeDefined();
  });
});
