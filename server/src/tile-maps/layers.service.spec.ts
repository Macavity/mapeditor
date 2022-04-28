import { Test, TestingModule } from '@nestjs/testing';
import { LayersService } from './layers.service';

describe('LayersService', () => {
  let service: LayersService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [LayersService],
    }).compile();

    service = module.get<LayersService>(LayersService);
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });
});
