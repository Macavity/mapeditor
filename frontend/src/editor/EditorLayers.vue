<script setup lang="ts">
  import type { IMapLayer } from '@/types/IMapLayer';
  import { MapLayerType } from '@/maps/MapLayerType';
  import { ref } from 'vue';

  const props = defineProps<{
    layers: IMapLayer[];
  }>();

  const isSky = (layer: IMapLayer) => layer.type === MapLayerType.Sky;
  const isBackground = (layer: IMapLayer) =>
    layer.type === MapLayerType.Background;
  const activeLayerId = ref('');
  const layers = [];

  for (const layer of props.layers) {
    layers[layer.id] = layer;
  }

  const toggleLayerVisibility = (layerId: string) => {
    con;
  };
</script>

<template>
  <div class="panel-heading">
    <h2 class="panel-title">
      <i class="glyphicon glyphicon-th-list"></i> Layers
    </h2>
  </div>

  <div class="panel-body">
    <ul>
      <li
        v-for="(layer, i) in layers"
        :key="i"
        class="layer"
        :class="{ 'layer--active': true, 'layer--hidden': false }"
      >
        <i
          v-if="layer.id === activeLayerId"
          class="bi bi-arrow-right-circle"
        ></i>
        <i
          @click="toggleLayerVisibility(layer)"
          class="toggle-layer bi bi-eye"
        ></i>
        {{ name }}
        <span v-if="isBackground(layer)" class="badge right">Bg</span>
        <span v-if="isSky(layer)" class="badge badge-info right">Sky</span>
      </li>
    </ul>
  </div>
</template>

<style scoped lang="scss">
  @import '../assets/mixins';

  $listHoverColor: #e6e6e6;

  ul {
    list-style: none;
    padding: 0;

    li {
      cursor: pointer;

      &:hover {
        background-color: $listHoverColor;
        .badge {
          @include prefix(box-shadow, inset 1px #000);
        }
      }

      i {
        width: 30px;
        opacity: 1;

        @include prefix(transition, opacity 0.15s ease-in-out);
      }
    }
  }
</style>
