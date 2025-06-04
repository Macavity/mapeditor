<template>
    <div class="map-edit">
        <h1>Map: {{ map.name }}</h1>

        <div class="row m-b-2">
            <div class="col-md-12">
                <EditorToolbar />
            </div>
        </div>
        <div class="row">
            <aside id="aside-left" class="col-md-3" :class="{ 'd-none': !store.showProperties }">
                <section id="section-properties" class="panel panel-primary" :class="{ 'd-none': !store.showProperties }">
                    <EditorMapProperties v-if="store.map" :map="store.map" />
                </section>
            </aside>

            <section
                id="section-canvas"
                :class="{
                    'col-md-6': store.showProperties,
                    'col-md-9': !store.showProperties,
                }"
            >
                <CanvasLayers />
            </section>

            <aside id="aside-right" class="col-md-3">
                <section id="section-minimap" class="mb-2" v-if="false">
                    <EditorMiniMap />
                </section>
                <section id="section-layers" class="mb-2">
                    <EditorLayers :layers="[]" />
                </section>
                <section id="section-tilesets" class="mb-2">
                    <TileSetBox />
                </section>
            </aside>
        </div>
    </div>
</template>

<script setup lang="ts">
import CanvasLayers from '@/components/editor/CanvasLayers.vue';
import EditorLayers from '@/components/editor/EditorLayers.vue';
import EditorMapProperties from '@/components/editor/EditorMapProperties.vue';
import EditorMiniMap from '@/components/editor/EditorMiniMap.vue';
import EditorToolbar from '@/components/editor/EditorToolbar.vue';
import TileSetBox from '@/components/editor/TileSetBox.vue';
import type { MapDto } from '@/dtos/Map.dto';
import { useEditorStore } from '@/stores/editorStore';
import { reactive } from 'vue';

const props = defineProps<{
    map: MapDto;
}>();
const store = useEditorStore();

const map = reactive(props.map);
</script>

<style lang="scss">
@import '../assets/mixins';

$zGrid: 100;
$zSelection: 99;

// Layers are 2-98
$zBackground: 1;
$zCanvas: 0;

#section-canvas {
    height: 100%;
    position: relative;
    overflow: scroll;
}

#canvas {
    position: relative;
    z-index: $zCanvas;

    display: block;
    overflow: hidden;
    background-color: gray;

    .selection {
        position: absolute;
        z-index: $zSelection;
        opacity: 0.5;

        pointer-events: none;
        box-shadow: inset 0px 0px 0px 1px #000;
    }

    #brush {
    }

    #grid {
        $width: 32px;
        $height: 32px;

        background-size: $width $height;
        background-image:
            repeating-linear-gradient(0deg, #000, #000 1px, transparent 1px, transparent $width),
            repeating-linear-gradient(-90deg, #000, #000 1px, transparent 1px, transparent $height);
        height: 100%;
        width: 100%;
        opacity: 0.4;
        position: absolute;
        top: 0;
        left: 0;
        z-index: $zGrid;
    }

    .layer {
        position: absolute;
        top: 0px;
        left: 0px;
        opacity: 1;

        @include prefix(transition, opacity 0.15s ease-in-out);

        &.layer-invisible {
            opacity: 0;
        }
    }
}
</style>
