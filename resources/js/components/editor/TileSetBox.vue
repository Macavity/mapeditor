<script setup lang="ts">
import AddTileSetModal from '@/components/editor/AddTileSetModal.vue';
import { useTileSetStore } from '@/stores/tileSetStore';
import { ref } from 'vue';

const tileSetStore = useTileSetStore();
let showModal = ref(false);

if (tileSetStore.tileSets.length === 0) {
    tileSetStore.loadTileSets();
}

function addTileSet(url: string) {
    console.log('add', url);
    showModal.value = false;
    // TileSetFactory.create();
    // tileSetStore.addTileSet();
}
</script>

<template>
    <div class="card">
        <AddTileSetModal v-if="showModal" :show="showModal" @close="showModal = false" @addTileSet="addTileSet" />
        <div class="card-header"><i class="bi bi-pencil"></i> Tilesets</div>
        <div class="card-body">
            <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                <div class="btn-group" role="group">
                    <button
                        type="button"
                        id="tileSetMenuButton"
                        class="btn btn-outline-secondary dropdown-toggle"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        {{ tileSetStore.activeTileSet?.name || 'None' }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="tileSetMenuButton">
                        <li v-for="tileSet in tileSetStore.tileSets" :key="tileSet.uuid">
                            <a @click="tileSetStore.activateTileSet(tileSet.uuid)" class="dropdown-item" href="#">{{ tileSet.name }}</a>
                        </li>
                    </ul>
                </div>
                <button @click="showModal = true" type="button" class="btn btn-outline-secondary">Add TileSet</button>
            </div>

            <div class="tile-set-wrapper">
                <div
                    v-if="tileSetStore.activeTileSet"
                    id="active-tileset-container"
                    :style="{
                        width: tileSetStore.activeTileSet.imageWidth + 'px',
                        height: tileSetStore.activeTileSet.imageHeight + 'px',
                        background: `url('${tileSetStore.activeTileSet.image}') no-repeat`,
                    }"
                >
                    <div class="tile-set-wrapper__selection"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.tile-set-wrapper {
    position: relative;

    height: 250px;
    overflow: scroll;

    background-color: #fff;

    &__selection {
        position: absolute;
        background-color: rgba(0, 0, 0, 0.3);
        background-image: none !important;
        pointer-events: none;
        box-shadow: inset 0px 0px 0px 1px #000;
    }
}
</style>
