import type { RouteLocationRaw } from 'vue-router';

export class RouteFactory {
  static toMapEdit(uuid: string): RouteLocationRaw {
    return { name: 'map-edit', params: { uuid } };
  }
}
