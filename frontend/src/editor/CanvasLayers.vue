<script setup lang="ts">
  import { useEditorStore } from '@/editor/EditorStore';
  import { onMounted } from 'vue';

  const store = useEditorStore();

  const brushSelection = {
    width: 32,
    height: 32,
    backgroundImage: null,
  };

  onMounted(() => {
    for (const layer of store.layers) {
      console.log('Draw Layer', layer.uuid);
    }
  });

  const onMouseMove = () => {
    // var container = $('#canvas');
    // var offset = container.offset();
    //
    // var selection = container.find('.selection');
    //
    // var activeTileset = Tilesets.findOne(Session.get('activeTileset'));
    // var tileWidth = activeTileset.tilewidth;
    // var tileHeight = activeTileset.tileheight;
    //
    // var x = Math.floor((event.pageX - offset.left) / tileWidth);
    // var y = Math.floor((event.pageY - offset.top) / tileHeight);
    //
    // var currentCursor = Template.instance().cursor;
    //
    // // Only move if the position changed to another tile
    // if (currentCursor[0] !== x || currentCursor[1] !== y) {
    //   Template.instance().cursor = [x, y];
    //   selection.css({
    //     top: y * tileHeight,
    //     left: x * tileWidth,
    //   });
    // }
  };
</script>

<template>
  <div
    @mousemove="onMouseMove"
    :style="{
      width: store.canvasWidth + 'px',
      height: store.canvasHeight + 'px',
    }"
  >
    <div class="selection" style="">
      <div
        id="brush"
        :style="{
          width: brushSelection.width + 'px',
          height: brushSelection.height + 'px',
          background:
            'url(\'' + brushSelection.backgroundImage + '\') no-repeat',
        }"
      ></div>
    </div>
    <div class="tilemap">
      <canvas
        v-for="layer in store.layers"
        :key="layer.id"
        :id="`canvas-layer-${layer.id}`"
        class="tilemap__layer"
        :class="{ 'layer-active': layer.id === store.activeLayer }"
        :style="{
          'z-index': layer.z,
          width: store.canvasWidth + 'px',
          height: store.canvasHeight + 'px',
        }"
        :width="store.canvasWidth"
        :height="store.canvasHeight"
      >
      </canvas>
    </div>
    <!-- A scalable grid in the background -->
    <!--    <div-->
    <!--      id="grid"-->
    <!--      class="{{#unless showGrid}}hidden{{/unless}}"-->
    <!--      style="width:{{canvasWidth}}px; height: {{canvasHeight}}px; background-size: {{tileset.tilewidth}}px {{tileset.tileheight}}px;background-image:-->
    <!--                repeating-linear-gradient(0deg, #000, #000 1px, transparent 1px, transparent {{tileset.tilewidth}}px),-->
    <!--                repeating-linear-gradient(-90deg, #000, #000 1px, transparent 1px, transparent {{tileset.tileheight}}px);"-->
    <!--    ></div>-->
  </div>
</template>

<style lang="scss" scoped>
  .tilemap {
    position: relative;

    &__layer {
      position: absolute;
      width: 100%;
      height: 100%;
    }
  }
</style>
