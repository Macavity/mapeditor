<template>
  <div class="map-edit">
    <h1>Map: {{ map.name }}</h1>

    <div class="row m-b-2">
      <div class="col-md-12">
        <EditorToolbar />
      </div>
    </div>
    <div class="row">
      <aside
        id="aside-left"
        class="col-md-3"
        :class="{ 'd-none': !store.showProperties }"
      >
        <section
          id="section-properties"
          class="panel panel-primary"
          :class="{ 'd-none': !store.showProperties }"
        >
          <EditorMapProperties :map="store.map" />
        </section>
      </aside>

      <section
        id="section-canvas"
        :class="{
          'col-md-6': store.showProperties,
          'col-md-9': !store.showProperties,
        }"
      >
        <!--        {{ >canvas map = map layers = mapLayers canvasWidth = canvasWidth canvasHeight = canvasHeight }}-->
      </section>

      <aside id="aside-right" class="col-md-3">
        <section id="section-minimap" class="panel panel-primary">
          <EditorMiniMap />
        </section>
        <section id="section-layers" class="panel panel-primary">
          <EditorLayers :layers="[]" />
        </section>
        <section id="section-tilesets" class="panel panel-primary">
          <TileSetBox />
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { MapDto } from '@/editor/Map.dto';
  import { reactive } from 'vue';
  import TileSetBox from '@/editor/TileSetBox.vue';
  import { useEditorStore } from '@/editor/EditorStore';
  import EditorMapProperties from '@/editor/EditorMapProperties.vue';
  import EditorMiniMap from '@/editor/EditorMiniMap.vue';
  import EditorLayers from '@/editor/EditorLayers.vue';
  import EditorToolbar from '@/editor/EditorToolbar.vue';

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
      background-image: repeating-linear-gradient(
          0deg,
          #000,
          #000 1px,
          transparent 1px,
          transparent $width
        ),
        repeating-linear-gradient(
          -90deg,
          #000,
          #000 1px,
          transparent 1px,
          transparent $height
        );
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

  #tilemap {
    position: relative;

    .layer {
      position: absolute;
      width: 100%;
      height: 100%;
    }
  }
</style>
