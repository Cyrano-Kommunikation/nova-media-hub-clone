<template>
  <Modal :show="show" role="alertdialog" id="o1-nmh-move-collection-modal">
    <div class="o1-bg-white dark:o1-bg-gray-800 o1-rounded-lg o1-shadow-lg o1-overflow-hidden" style="width: 460px">
      <ModalHeader v-text="__('novaMediaHub.moveCollectionTitle')"/>

      <ModalContent class="o1-flex o1-flex-col">
        <p class="o1-leading-tight">{{ __('novaMediaHub.moveCollectionText') }}</p>

        <SelectControl v-model:selected="collection" @change="c => (collection = c)">
          <option v-for="c in filteredCollections" :key="c" :value="c.id">{{ c.name }}</option>
        </SelectControl>
      </ModalContent>

      <ModalFooter>
        <div class="o1-ml-auto">
          <LinkButton type="button" @click.prevent="$emit('close')" class="o1-mr-3">
            {{ __('novaMediaHub.closeButton') }}
          </LinkButton>

          <LoadingButton @click.prevent="handleMove" :disabled="loading" :processing="loading">{{
              __('novaMediaHub.moveButton')
            }}
          </LoadingButton>
        </div>
      </ModalFooter>
    </div>
  </Modal>
</template>

<script>
import API from '../api';
import HandlesMediaLists from '../mixins/HandlesMediaLists';

export default {
  mixins: [HandlesMediaLists],

  emits: ['confirm', 'close'],

  props: ['show', 'mediaItem'],

  data: () => ({loading: false}),

  async mounted() {
    console.log('MoveToCollectionModal');
    await this.getCollections();
  },

  watch: {
    show(newValue) {
      this.collection = this.filteredCollections.length > 0 ? this.filteredCollections[0].id : null;
    },
  },

  methods: {
    async handleMove() {
      this.loading = true;

      const result = await API.moveMediaToCollection(this.mediaItem.id, this.collection);
      if (result.response?.data?.error) {
        Nova.$toasted.error(result.response.data.error);
      } else {
        Nova.$toasted.success('Du hast die Datei erfolgreich in die ausgewählte Kollektion verschoben.');
      }

      this.$emit('close', true);

      this.$nextTick(() => (this.loading = false));
    },
  },

  computed: {
    filteredCollections() {
      if (!this.mediaItem) return this.collections;
      return this.collections.filter(c => c.id !== this.mediaItem.collection_id);
    },
  },
};
</script>

<style lang="scss">
#o1-nmh-move-collection-modal {
  z-index: 130;

  + .fixed {
    z-index: 129;
  }
}
</style>
