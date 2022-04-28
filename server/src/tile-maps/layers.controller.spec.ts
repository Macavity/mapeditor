import { Test, TestingModule } from '@nestjs/testing';
import { LayersController } from './layers.controller';
import { LayersService } from './layers.service';

describe('LayersController', () => {
  let controller: LayersController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [LayersController],
      providers: [LayersService],
    }).compile();

    controller = module.get<LayersController>(LayersController);
  });

  it('should be defined', () => {
    expect(controller).toBeDefined();
  });
});
