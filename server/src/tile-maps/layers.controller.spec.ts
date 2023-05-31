import { Test, TestingModule } from '@nestjs/testing';
import { mockRepositoryProvider } from '../../test/mocks/repositories';
import { Layer } from './entities/layer.entity';
import { LayersController } from './layers.controller';
import { LayersService } from './layers.service';

describe('LayersController', () => {
  let controller: LayersController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [LayersController],
      providers: [LayersService, mockRepositoryProvider(Layer)],
    }).compile();

    controller = module.get<LayersController>(LayersController);
  });

  it('should be defined', () => {
    expect(controller).toBeDefined();
  });
});
