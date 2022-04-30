import { Test, TestingModule } from '@nestjs/testing';
import { mockRepositoryProvider } from '../../test/mocks/repositories';
import { TileMap } from './entities/tile-map.entity';
import { TileMapsService } from './tile-maps.service';

describe('TileMapsService', () => {
  let service: TileMapsService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [TileMapsService, mockRepositoryProvider(TileMap)],
    }).compile();

    service = module.get<TileMapsService>(TileMapsService);
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });
});
