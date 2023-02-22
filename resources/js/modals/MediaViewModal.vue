<template>
  <Modal size="custom" :show="show" @close-via-escape="$emit('close')" role="alertdialog" maxWidth="w-full"
         class="o1-px-24" id="o1-nmh-media-view-modal">
    <LoadingCard :loading="loading" class="mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
      <slot>
        <ModalContent class="o1-px-8 o1-flex o1-flex-col">
          <div class="o1-flex">
            <!-- File info and media fields -->
            <div
              class="o1-flex o1-flex-col o1-pr-4 o1-border-r o1-border-slate-200 o1-mr-4 o1-max-w-sm o1-w-full dark:o1-border-slate-700">
              <MediaViewModalInfoListItem :label="__('novaMediaHub.viewModalIdTitle')" :value="mediaItem.id"/>
              <MediaViewModalInfoListItem :label="__('novaMediaHub.fileNameTitle')" :value="mediaItem.file_name"/>
              <MediaViewModalInfoListItem :label="__('novaMediaHub.fileSizeTitle')" :value="fileSize"/>
              <MediaViewModalInfoListItem :label="__('novaMediaHub.mimeTypeTitle')" :value="mediaItem.mime_type"/>

              <div>
                <div class="o1-flex o1-flex-col o1-mb-2">
                  <span
                    class="o1-text-xs o1-leading-tight o1-text-teal-600 o1-font-semibold o1-ml-1">Schlagwörter</span>
                  <span
                    class="o1-bg-slate-50 o1-border o1-border-slate-100 o1-rounded-md o1-px-2 o1-py-1 o1-whitespace-nowrap o1-text-slate-500 dark:o1-bg-slate-800 dark:o1-border-slate-700 dark:o1-text-slate-400 o1-overflow-x-auto"
                  >
                    <span v-if="mediaItem.tags.length === 0">
                      Keine Schlagwörter zugewiesen
                    </span>
                  <div v-for="tag in mediaItem.tags" :key="tag.id">
                    {{ tag.name }}
                  </div>
                  </span>
                </div>
                <div class="o1-flex o1-flex-col o1-mb-2">
                  <span
                    class="o1-text-xs o1-leading-tight o1-text-teal-600 o1-font-semibold o1-ml-1">Benutzergruppen</span>
                  <span
                    class="o1-bg-slate-50 o1-border o1-border-slate-100 o1-rounded-md o1-px-2 o1-py-1 o1-whitespace-nowrap o1-text-slate-500 dark:o1-bg-slate-800 dark:o1-border-slate-700 dark:o1-text-slate-400 o1-overflow-x-auto"
                  >
                    <span v-if="mediaItem.roles.length === 0">
                      Keine Benutzergruppe zugewiesen.
                    </span>
                    <span v-for="role in mediaItem.roles" :key="role.id"
                          class="after:o1-content-[',_'] last:after:o1-content-['']">
                      {{ role.name }}
                    </span>
                  </span>
                </div>
              </div>

              <div>
              </div>
            </div>

            <!-- File itself -->
            <div class="o1-flex o1-flex-col o1-m-auto o1-h-full o1-w-full o1-items-center o1-justify-center"
                 style="max-height: 60vh">
              <Loader v-show="type === 'image' && !image"></Loader>
              <img v-if="type === 'image'" v-show="image"
                   class="o1-object-contain o1-max-w-full o1-w-full o1-max-h-full o1-border o1-border-slate-100 o1-bg-slate-50 o1-min-h-0 dark:o1-bg-slate-900 dark:o1-border-slate-700"
                   :src="image" :alt="mediaItem.file_name"/>

              <video v-else-if="type === 'video'" controls autoplay class="o1-ml-auto o1-h-full o1-w-full o1-min-h-0">
                <source :src="mediaItem.url" :type="mediaItem.mime_type"/>
              </video>

              <audio v-else-if="type === 'audio'" :src="mediaItem.url" controls autoplay>
                <source :src="mediaItem.url" :type="mediaItem.mime_type"/>
              </audio>

              <div v-else>
                Bei dieser Datei handelt es sich um kein Bild / Video / Audio, dadurch ist die Vorschau nicht verfügbar.
              </div>
            </div>
          </div>
        </ModalContent>
      </slot>

      <ModalFooter>
        <div class="ml-auto">
          <CancelButton @click.prevent="$emit('close')" class="o1-mr-4">
            {{ __('Close') }}
          </CancelButton>
        </div>
      </ModalFooter>
    </LoadingCard>
  </Modal>
</template>

<script>
import API from '../api';
import MediaViewModalInfoListItem from '../components/MediaViewModalInfoListItem';
import DividerLine from "../../../vendor/laravel/nova/resources/js/components/DividerLine.vue";
import HandlesMediaLists from "../mixins/HandlesMediaLists";

export default {
  emits: ['close'],
  props: ['show', 'mediaItem', 'collection', 'roles', 'tags'],

  components: {DividerLine, MediaViewModalInfoListItem},

  mixins: [HandlesMediaLists],

  data: () => ({
    loading: false,
    collectionName: '',
    selectedFiles: '',
    selectedCollection: '',
    collections: [],
    dataFields: [],
    image: ''
  }),

  async created() {
  },

  async mounted() {
    Nova.$emit('close-dropdowns');
  },

  watch: {
    async show(newValue) {
      if (newValue) {
        const response = await API.getImage(this.mediaItem.file_name, this.mediaItem.id, this.mediaItem.mime_type);
        this.image = window.URL.createObjectURL(response.data);
        await this.getCollections();
        this.selectedCollection = this.collections.find(col => col.id === this.collection);
      } else {
        if (!this.image) {
          return;
        }
        window.URL.revokeObjectURL(this.image);
        this.image = null;
      }
    },
  },

  methods: {
    async saveAndExit() {
      this.loading = true;
      try {
        const formData = new FormData();
        for (const field of this.dataFields) {
          field.fill(formData);
        }

        const {data} = await API.updateMediaData(this.mediaItem.id, formData);
        this.mediaItem.data = data.data;

        this.$emit('close');
        Nova.$toasted.success(this.__('novaMediaHub.mediaItemUpdated'));
      } catch (e) {
        console.error(e);
      }
      this.loading = false;
    },

    async getCollections() {
      const {data} = await API.getCollections();
      this.collections = data || [];

      if (!this.selectedCollection) {
        if (this.collections.length) {
          this.selectedCollection = this.collections.find((col) => this.selectedCollection === col.id);
        }
      }
    },

    createField(attribute, name) {
      const hasLocales = !!this.locales && this.locales.en !== 'mediaHubHidden';

      let value = '';
      if (!hasLocales) {
        value = {en: this.mediaItem.data?.[attribute] || ''};
      } else {
        value = this.mediaItem.data?.[attribute] || {};
      }

      return {
        name,
        attribute,
        visible: true,
        stacked: true,
        extraClass: 'field-wrapper',
        translatable: {locales: this.locales, original_component: 'text-field', value},
      };
    },
  },

  computed: {
    newCollection() {
      return this.selectedCollection === 'media-hub-new-collection';
    },

    finalCollectionName() {
      return this.newCollection ? this.collectionName : this.selectedCollection;
    },

    type() {
      if (!this.mediaItem) return;

      const mimeType = this.mediaItem.mime_type.split('/')[0];
      if (mimeType === 'image') return 'image';
      if (mimeType === 'audio') return 'audio';
      if (mimeType === 'video') return 'video';
      return 'other';
    },

    locales() {
      return Nova.appConfig.novaMediaHub.locales || {en: 'mediaHubHidden'};
    },

    fileSize() {
      if (!this.mediaItem) return '';

      const sizeInKb = this.mediaItem.size / 1000;
      return Number(Math.round(sizeInKb + 'e' + 2) + 'e-' + 2) + ' kb';
    },
  },
};
</script>

<style lang="scss">
#o1-nmh-media-view-modal {
  z-index: 130;

  + .fixed {
    z-index: 129;
  }

  .nova-media-hub-media-modal-translatable-field {
    margin-bottom: 15px;

    .nova-translatable-locale-tabs {
      padding-left: 0;
      padding-right: 0;
    }

    > div:not(.nova-translatable-locale-tabs) {
      > div {
        margin-top: -25px;
      }

      > div > div {
        padding-left: 0;
        padding-right: 0;
      }
    }

    /* > * {

    } */
  }
}
</style>
