import { Test, TestingModule } from '@nestjs/testing';
import { TileSetsController } from './tile-sets.controller';

describe('TileSetsController', () => {
  let controller: TileSetsController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [TileSetsController],
    }).compile();

    controller = module.get<TileSetsController>(TileSetsController);
  });

  it('should be defined', () => {
    expect(controller).toBeDefined();
  });
});
