import { Controller } from '@nestjs/common';
import { LayersService } from './layers.service';

@Controller('layers')
export class LayersController {
  constructor(private readonly layersService: LayersService) {}
}
