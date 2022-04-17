import { Test, TestingModule } from '@nestjs/testing';
import { TileSetsService } from './tile-sets.service';

describe('TileSetsService', () => {
  let service: TileSetsService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [TileSetsService],
    }).compile();

    service = module.get<TileSetsService>(TileSetsService);
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });
});
