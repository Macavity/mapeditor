import { Test, TestingModule } from '@nestjs/testing';
import { mockRepositoryProvider } from '../../test/mocks/repositories';
import { Layer } from './entities/layer.entity';
import { TileMap } from './entities/tile-map.entity';
import { LayersService } from './layers.service';
import { TileMapsController } from './tile-maps.controller';
import { TileMapsService } from './tile-maps.service';

describe('TileMapsController', () => {
  let controller: TileMapsController;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      controllers: [TileMapsController],
      providers: [
        TileMapsService,
        LayersService,
        mockRepositoryProvider(TileMap),
        mockRepositoryProvider(Layer),
      ],
    }).compile();

    controller = module.get<TileMapsController>(TileMapsController);
  });

  it('should be defined', () => {
    expect(controller).toBeDefined();
  });
});
