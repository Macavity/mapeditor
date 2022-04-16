import { Test, TestingModule } from '@nestjs/testing';
import { TileMapsService } from './tile-maps.service';

describe('TileMapsService', () => {
  let service: TileMapsService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [TileMapsService],
    }).compile();

    service = module.get<TileMapsService>(TileMapsService);
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });
});
