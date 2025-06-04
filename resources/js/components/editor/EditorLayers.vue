<script setup lang="ts">
import { useEditorStore } from '@/stores/editorStore';
import { IMapLayer, MapLayerType } from '@/types/MapLayer';

const store = useEditorStore();
if (store.map) {
    store.loadLayersForMap(store.map.uuid);
}

const isSky = (layer: IMapLayer) => layer.type === MapLayerType.Sky;
const isBackground = (layer: IMapLayer) => layer.type === MapLayerType.Background;

const toggleLayerVisibility = (layerId: number) => {
    store.toggleLayerVisibility(layerId);
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
                    @click="store.activateLayer(layer.id)"
                    class="layer"
                    :class="{ 'layer--active': true, 'layer--hidden': false }"
                >
                    <i v-if="layer.id === store.activeLayer" class="bi bi-arrow-right-circle"></i>&nbsp;
                    <i
                        @click="toggleLayerVisibility(layer.id)"
                        class="bi"
                        :class="{
                            'bi-eye-fill': layer.visible,
                            'bi-eye-slash': !layer.visible,
                        }"
                    ></i>
                    {{ layer.name }}
                    <span v-if="isBackground(layer)" class="badge bg-secondary right"> Bg </span>
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
