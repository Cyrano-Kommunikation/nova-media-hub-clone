<template>
  <Modal :show="show" role="alertdialog" id="o1-nmh-confirm-delete-modal" @close-via-escape="$emit('close')">
    <div class="o1-bg-white dark:o1-bg-gray-800 o1-rounded-lg o1-shadow-lg o1-overflow-hidden" style="width: 460px">
      <ModalHeader v-text="'Kollektion löschen'" />

      <ModalContent>
        <p class="o1-leading-tight">
          Wenn du diese Kollektion löschst, werden alle in dieser Kollektion enthaltenen Dateien ebenfalls gelöscht. Dies kann nicht rückgängig gemacht werden.
        </p>
      </ModalContent>

      <ModalFooter>
        <div class="o1-ml-auto">
          <LinkButton type="button" @click.prevent="$emit('close')" class="o1-mr-3">
            {{ __('novaMediaHub.closeButton') }}
          </LinkButton>

          <LoadingButton
            @click.prevent="handleDelete"
            :disabled="loading"
            :processing="loading"
            component="DangerButton"
          >
            {{ __('novaMediaHub.deleteButton') }}
          </LoadingButton>
        </div>
      </ModalFooter>
    </div>
  </Modal>
</template>

<script>
import API from '../api';
import HandlesCollectionActions from '../mixins/HandlesCollectionActions';

export default {
  emits: ['confirm', 'close'],
  mixins: [HandlesCollectionActions],

  props: ['show', 'mediaItem', 'collection'],

  data: () => ({ loading: false }),

  methods: {
    async handleDelete() {
      this.loading = true;

      await this.deleteCollection(this.collection);

      this.$emit('close', true);

      this.loading = false;
    },
  },
};
</script>

<style lang="scss">
#o1-nmh-confirm-delete-modal {
  z-index: 130;

  + .fixed {
    z-index: 129;
  }
}
</style>
