import { Test, TestingModule } from '@nestjs/testing';
import { mockRepositoryProvider } from '../../test/mocks/repositories';
import { Layer } from './entities/layer.entity';
import { LayersService } from './layers.service';

describe('LayersService', () => {
  let service: LayersService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [LayersService, mockRepositoryProvider(Layer)],
    }).compile();

    service = module.get<LayersService>(LayersService);
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });
});
