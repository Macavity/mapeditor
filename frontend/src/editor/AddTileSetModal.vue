<script lang="ts">
  import type { IWindow } from '@/external';

  declare let window: IWindow;
</script>
<script lang="ts" setup>
  import { onMounted, ref } from 'vue';

  const props = defineProps<{
    show: boolean;
  }>();

  const emit = defineEmits(['close', 'addTileSet']);

  const url = '';
  let modal: { show: () => void; hide: () => void };
  const showModal = ref(props.show);

  onMounted(() => {
    modal = new window.bootstrap.Modal(
      document.getElementById('addTileSetModal')
    );

    if (showModal.value) {
      modal.show();
    }
  });

  const close = () => {
    showModal.value = false;
    emit('close');
  };

  const confirm = () => {
    showModal.value = false;
    modal.hide();
    emit('addTileSet', url);
  };
</script>
<template>
  <div
    v-show="showModal"
    class="modal fade"
    id="addTileSetModal"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
    tabindex="-1"
    aria-labelledby="staticBackdropLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="staticBackdropLabel">
            Add TileSet to Map
          </h5>
          <button
            type="button"
            @click="close"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <form>
            <div class="mb-3">
              <label for="tileSetUrl" class="form-label">TileSet URL</label>
              <input
                v-model="url"
                id="tileSetUrl"
                type="text"
                class="form-control"
                aria-describedby="tileSetUrlHelp"
              />
              <div id="tileSetUrlHelp" class="form-text">
                Enter the URL to the external TileSet
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button
            @click="close"
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal"
          >
            Close
          </button>
          <button
            @click="confirm"
            type="button"
            class="btn btn-primary"
            :class="{ 'btn-disabled': url.length === 0 }"
          >
            Add
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
