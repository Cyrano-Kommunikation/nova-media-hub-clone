<template>
  <Modal :show="show" role="alertdialog" id="o1-nmh-confirm-rename-modal" @close-via-escape="$emit('close')">
    <div class="o1-bg-white dark:o1-bg-gray-800 o1-rounded-lg o1-shadow-lg o1-overflow-hidden" style="width: 460px">
      <ModalHeader v-text="'Kollektion umbenennen'" />

      <ModalContent>
        <p class="o1-leading-tight mb-4">
          Bitte gebe den neuen Namen der Kollektion an.
        </p>

        <input v-model="newCollectionName" type="text" class="form-control form-input form-input-bordered w-full mt-4"
          placeholder="Neuer Kollektionsname">
      </ModalContent>

      <ModalFooter>
        <div class="o1-ml-auto">
          <LinkButton type="button" @click.prevent="$emit('close')" class="o1-mr-3">
            {{ __('novaMediaHub.closeButton') }}
          </LinkButton>

          <LoadingButton @click.prevent="handleRename" :disabled="loading" :processing="loading"
            component="LoadingButton">
            Umbenennen
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

  data: () => ({
    loading: false,
    newCollectionName: ""
  }),

  methods: {
    async handleRename() {
      this.loading = true;

      const result = await this.renameCollection(this.collection, this.newCollectionName);

      if (result) {
        this.$emit('close', true);
      }

      this.loading = false;
    }
  },
};
</script>

<style lang="scss">
#o1-nmh-confirm-delete-modal {
  z-index: 130;

  +.fixed {
    z-index: 129;
  }
}
</style>
