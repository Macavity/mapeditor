<script setup lang="ts">
  import type { IMapLayer } from '@/types/IMapLayer';
  import { MapLayerType } from '@/maps/MapLayerType';
  import { ref } from 'vue';
  import { useEditorStore } from '@/editor/EditorStore';

  const props = defineProps<{
    layers: IMapLayer[];
  }>();

  const store = useEditorStore();
  if (store.map) {
    store.loadLayersForMap(store.map.uuid);
  }

  const isSky = (layer: IMapLayer) => layer.type === MapLayerType.Sky;
  const isBackground = (layer: IMapLayer) =>
    layer.type === MapLayerType.Background;
  let activeLayerId = ref(null as number | null);

  const toggleLayerVisibility = (layerId: number) => {
    store.toggleLayerVisibility(layerId);
  };
  const toggleActiveLayer = (layerId: number) => {
    activeLayerId.value = layerId;
  };
</script>

<template>
  <div class="card">
    <div class="card-header"><i class="bi bi-list"></i> Layers</div>
    <div class="card-body">
      <ul>
        <li
          v-for="layer in store.layers"
          :key="layer.id"
          @click="toggleActiveLayer(layer.id)"
          class="layer"
          :class="{ 'layer--active': true, 'layer--hidden': false }"
        >
          <i
            v-if="layer.id === activeLayerId"
            class="bi bi-arrow-right-circle"
          ></i>
          <i
            @click="toggleLayerVisibility(layer.id)"
            class="bi"
            :class="{
              'bi-eye-fill': layer.visible,
              'bi-eye-slash': !layer.visible,
            }"
          ></i>
          {{ layer.name }}
          <span v-if="isBackground(layer)" class="badge bg-secondary right">
            Bg
          </span>
          <span v-if="isSky(layer)" class="badge bg-info right">Sky</span>
        </li>
      </ul>
    </div>
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
