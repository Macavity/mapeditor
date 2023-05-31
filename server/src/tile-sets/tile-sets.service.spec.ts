import { Test, TestingModule } from '@nestjs/testing';
import { mockRepositoryProvider } from '../../test/mocks/repositories';
import { TileSet } from './entities/tile-set.entity';
import { TileSetsService } from './tile-sets.service';

describe('TileSetsService', () => {
  let service: TileSetsService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [TileSetsService, mockRepositoryProvider(TileSet)],
    }).compile();

    service = module.get<TileSetsService>(TileSetsService);
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });
});
