<template>
  <Modal
    :show="show"
    @close-via-escape="$emit('close')"
    role="alertdialog"
    maxWidth="2xl"
    id="o1-nmh-media-upload-modal"
  >
    <LoadingCard :loading="loading" class="mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
      <slot>
        <ModalHeader class="flex items-center">{{ __('novaMediaHub.uploadMediaTitle') }}</ModalHeader>

        <ModalContent class="px-8 o1-flex o1-flex-col">
          <LoadingButton class="mt-2" type="button" @click="handleFileSelect">
            Dateien auswählen
          </LoadingButton>
          <div class="mt-3" v-if="selectedFiles.length > 0">
            Ausgewählte Dateien:
          </div>
          <div class="flex flex-col">
            <div class="py-3 flex items-center" v-for="(file, index) in selectedFiles">
              <HeroiconsSolidXCircle
                class="mr-2 cursor-pointer hover:text-cyan-900 dark:hover:text-cyan-900 transition-all"
                @click.prevent="removeFile(index)"/>
              {{ file.name }}
            </div>
          </div>
          <input
            id="fileSelect"
            class="form-control form-input form-input-bordered mt-2 hidden"
            style="padding-top: 4px;"
            type="file"
            name="selected_media"
            ref="filesInput"
            @change="onFilesChange"
            multiple
          />
        </ModalContent>
      </slot>

      <ModalFooter>
        <div class="ml-auto">
          <LoadingButton @click.prevent="$emit('close')" class="o1-mr-4">
            {{ __('novaMediaHub.closeButton') }}
          </LoadingButton>

          <LoadingButton @click.prevent="uploadFiles">{{ __('novaMediaHub.uploadFilesButton') }}</LoadingButton>
        </div>
      </ModalFooter>
    </LoadingCard>
  </Modal>
</template>

<script>
import API from '../api';
import HandlesMediaUpload from '../mixins/HandlesMediaUpload';
import HeroiconsSolidXCircle
  from "../../../vendor/laravel/nova/resources/js/components/Heroicons/solid/HeroiconsSolidXCircle.vue";

export default {
  components: {HeroiconsSolidXCircle},
  mixins: [HandlesMediaUpload],
  emits: ['close'],
  props: ['show', 'activeCollection'],

  data: () => ({
    loading: false,
    collectionName: '',
    selectedFiles: [],
    selectedCollection: null,
    collections: [],
  }),

  mounted() {
    this.selectedCollection = this.activeCollection;
    Nova.$emit('close-dropdowns');
  },

  methods: {
    removeFile(fileIndex) {
      this.selectedFiles.splice(fileIndex, 1);
    },
    handleFileSelect() {
      const el = document.getElementById('fileSelect');
      el.click();
    },
    async uploadFiles() {
      this.loading = true;

      const {success, media, hadExisting} = await this.uploadFilesToCollection(
        this.selectedFiles,
        this.activeCollection);

      let goToCollection = this.selectedCollection;

      if (hadExisting) {
        // Find possible new collection name
        const diffCollNameMedia = media.find(mi => mi.collection_name !== this.selectedCollection);
        if (diffCollNameMedia) goToCollection = diffCollNameMedia.collection_name;
      }

      if (success) {
        this.selectedFiles = [];
        this.$emit('close', true, goToCollection);
      }

      this.loading = false;
    },

    onFilesChange(e) {
      if (this.$refs.filesInput) {
        this.selectedFiles = [...this.$refs.filesInput.files, ...this.selectedFiles];
      }
    },

    async getCollections() {
      const {data} = await API.getCollections();
      this.collections = data || [];

      if (!this.selectedCollection) {
        this.selectedCollection = this.collections.length ? this.collections[0] : void 0;
      }
    },
  },

  computed: {
    newCollection() {
      return this.selectedCollection === 'media-hub-new-collection';
    },

    finalCollectionName() {
      return this.newCollection ? this.collectionName : this.selectedCollection;
    },

    canCreateCollections() {
      return Nova.appConfig.novaMediaHub.canCreateCollections;
    },
  },
};
</script>

<style lang="scss">
#o1-nmh-media-upload-modal {
  z-index: 120;

  + .fixed {
    z-index: 119;
  }
}
</style>
