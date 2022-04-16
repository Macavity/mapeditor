import { Test, TestingModule } from '@nestjs/testing';
import { TileMapsController } from './tile-maps.controller';

describe('TileMapsController', () => {
  let controller: TileMapsController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [TileMapsController],
    }).compile();

    controller = module.get<TileMapsController>(TileMapsController);
  });

  it('should be defined', () => {
    expect(controller).toBeDefined();
  });
});
