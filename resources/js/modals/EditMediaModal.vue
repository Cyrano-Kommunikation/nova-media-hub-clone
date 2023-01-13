<template>
  <Modal :show="show" role="alertdialog" id="o1-nmh-confirm-rename-modal !o1-overflow-visible"
         @close-via-escape="$emit('close')">
    <div class="o1-bg-white dark:o1-bg-gray-800 o1-rounded-lg o1-shadow-lg o1-overflow-hidden" style="width: 460px">
      <ModalHeader v-text="'Datei bearbeiten'"/>

      <ModalContent class="!o1-overflow-visible">
        <div>
          <input v-model="alt" type="text" class="form-control form-input form-input-bordered w-full mt-4"
                 placeholder="Alt Titel">
        </div>
        <div class="o1-mt-4">
          <input v-model="copyright" type="text" class="form-control form-input form-input-bordered w-full mt-4"
                 placeholder="Copyright">
        </div>
        <div class="o1-mt-8 relative">
          <input v-model="inputTag" @input="handleInputChange" type="text"
                 class="form-control form-input form-input-bordered w-full mt-4"
                 placeholder="Schlagwörter" @keydown.enter="addTag">
          <div v-show="showTagDropdown"
               class="o1-w-[394px] o1-absolute o1-z-[1000] top[40px] o1-max-h-[150px] dark:o1-bg-gray-900 o1-bg-gray-200 o1-z-10 o1-rounded o1-border-gray-300 o1-border dark:o1-border-gray-700 o1-mt-2 shadow o1-overflow-hidden o1-overflow-y-scroll"
               v-if="filterTags.length > 0">
            <div class="o1-p-4 cursor-pointer o1-border-b o1-border-gray-300 dark:o1-border-gray-700"
                 @click="addTagFromSelect(tag)" v-for="tag in filterTags" :key="tag">
              {{ tag }}
            </div>
          </div>
          <div
            class="o1-w-full dark:o1-bg-gray-900 o1-bg-gray-200 o1-z-10 o1-rounded o1-border-gray-300 o1-border dark:o1-border-gray-700 o1-mt-2 o1-p-2">
            <span
              class="o1-text-xs o1-px-3 o1-py-1 o1-bg-slate-700 o1-bg-slate-300 dark:o1-bg-slate-500 o1-m-1 o1-rounded-xl o1-inline-block"
              v-for="(tag, index) in selectedTags" :key="tag">
                <span class="o1-inline-block">{{ tag }}</span>
                <HeroiconsSolidX class="o1-w-3 o1-h-3 o1-inline-block cursor-pointer"
                                 @click="removeTag(index, tag)"/>
              </span>
          </div>
        </div>
        <div class="o1-mt-4 relative">
          <div>
            <Button @input="handleInputChange"
                    class="form-control form-input form-input-bordered w-full mt-4"
                    @click="showRoleDropdown = !showRoleDropdown">
              Benutzergruppen auswählen
            </Button>
            <div v-show="showRoleDropdown"
                 class="o1-w-[394px] o1-absolute o1-z-[1000] o1-top-[-60px] o1-max-h-[150px] dark:o1-bg-gray-900 o1-bg-gray-500 o1-z-10 o1-rounded o1-border-gray-300 o1-border dark:o1-border-gray-700 o1-mt-2 shadow o1-overflow-hidden o1-overflow-y-scroll"
                 v-if="filterRoles.length > 0">
              <div class="o1-p-4 cursor-pointer o1-border-b o1-border-gray-300 dark:o1-border-gray-700 text-white"
                   v-for="role in filterRoles"
                   @click="addRoleFromSelect(role)">
                {{ getRoleData(role).name }}
              </div>
            </div>
            <div
              class="o1-w-full dark:o1-bg-gray-900 o1-z-10 o1-rounded o1-border-gray-300 o1-border dark:o1-border-gray-700 o1-mt-2 o1-p-2"
              v-if="selectedRoles.length > 0">
              <div
                class="o1-p-2 last:o1-border-0 o1-border-b o1-border-gray-300 dark:o1-border-gray-700 o1-transition-all hover:o1-bg-gray-300 dark:hover:o1-bg-gray-800"
                v-if="selectedRoles.length === 0">
                Es wurden keine Benutzergruppen zugewiesen.
              </div>
              <span
                class="o1-text-xs o1-px-3 o1-py-1 o1-bg-slate-300 o1-m-1 last:o1-mr-0 o1-rounded-xl o1-inline-block"
                v-for="(role, index) in selectedRoles" :key="role">
                <span class="o1-inline-block">{{ getRoleData(role).name }}</span>
                <HeroiconsSolidX class="o1-w-3 o1-h-3 o1-ml-2 o1-inline-block cursor-pointer"
                                 @click="removeRole(role)"/>
              </span>
              <!--              <div-->
              <!--                class="o1-p-2 last:o1-border-0 o1-border-b o1-border-gray-300 dark:o1-border-gray-700 o1-transition-all hover:o1-bg-gray-300 dark:hover:o1-bg-gray-800 cursor-pointer o1-flex o1-items-center"-->
              <!--                v-for="role in selectedRoles" :key="role" @click="removeRole(role)">-->
              <!--                <transition enter-from-class="o1-opacity-0 o1-translate-x-[-50px]"-->
              <!--                            leave-to-class="o1-opacity-0 o1-translate-x-[-50px]">-->
              <!--                  <HeroiconsSolidCheck class="o1-w-4 o1-h-4 o1-mr-2" v-show="checkIfRoleIsSelected(role)"/>-->
              <!--                </transition>-->
              <!--                {{ getRoleData(role).name }}-->
              <!--              </div>-->
            </div>
            <small>Wenn keine Gruppe ausgewählt ist, hat jeder Nutzer Zugriff auf die Datei.</small>
          </div>
        </div>


      </ModalContent>

      <ModalFooter>
        <div class="o1-ml-auto">
          <LinkButton type="button" @click.prevent="$emit('close')" class="o1-mr-3">
            {{ __('novaMediaHub.closeButton') }}
          </LinkButton>

          <LoadingButton @click.prevent="handleUpdate" :disabled="loading" :processing="loading"
                         component="LoadingButton">
            Datei aktualisieren
          </LoadingButton>
        </div>
      </ModalFooter>
    </div>
  </Modal>
</template>

<script>
import API from '../api';
import HeroiconsSolidX from "../../../vendor/laravel/nova/resources/js/components/Heroicons/solid/HeroiconsSolidX.vue";

export default {
  components: {HeroiconsSolidX},
  emits: ['confirm', 'close'],

  props: ['show', 'media', 'roles', 'tags'],

  data: () => ({
    showDropdown: false,
    loading: false,
    alt: "",
    copyright: "",
    inputTag: '',
    selectedTags: [],
    selectedRoles: [],
    showTagDropdown: false,
    showRoleDropdown: false
  }),

  watch: {
    async show(newValue) {
      this.selectedRoles = [];
      this.selectedTags = [];
      if (newValue) {
        this.alt = this.media?.data?.alt;
        this.copyright = this.media?.data?.copyright;
        let tagList = [];
        const tags = this.media.tags;
        for (let i = 0; i < tags.length; i++) {
          this.selectedTags.push(tags[i].name);
        }

        let roleList = [];
        const roles = this.media.roles;
        for (let i = 0; i < roles.length; i++) {
          roleList.push(roles[i].id);
        }
        this.selectedRoles = roleList;
      }
    }
  },

  computed: {
    filterTags() {
      const tags = this.tags;
      const selectedTags = this.selectedTags;
      const input = this.inputTag.toLowerCase();
      let tagsArray = [];
      for (let i = 0; i < tags.length; i++) {
        if (!selectedTags.includes(tags[i].name)) {
          tagsArray.push(tags[i].name);
        }
      }
      const result = tagsArray.filter(tag => tag.toLowerCase().indexOf(input) > -1);
      return result;
    },
    filterRoles() {
      const roles = this.roles;
      const selectedRoles = this.selectedRoles;
      let roleArray = [];
      for (let i = 0; i < roles.length; i++) {
        if (!selectedRoles.includes(roles[i].id)) {
          roleArray.push(roles[i].id);
        }
      }
      return roleArray;
    }
  },

  methods: {
    addRoleFromSelect(id) {
      if (!this.selectedRoles.includes(id)) {
        this.selectedRoles.push(id);
        this.showRoleDropdown = false;
      }
    },
    getRoleData(id) {
      return this.roles.find(role => role.id === id);
    },
    addTagFromSelect(name) {
      if (!this.selectedTags.includes(name)) {
        this.selectedTags.push(name);
        this.inputTag = "";
        this.showTagDropdown = false;
      }
    },
    handleInputChange() {
      if (this.inputTag.length > 2) {
        this.showTagDropdown = true;
        return;
      }

      this.showTagDropdown = false;
    },
    removeTag(index, tag) {
      if (this.selectedTags.includes(tag)) {
        this.selectedTags.splice(index, 1);
      }
    },
    addTag() {
      if (!this.selectedTags.includes(this.inputTag) && this.inputTag.length > 0) {
        this.selectedTags.push(this.inputTag);
        this.inputTag = "";
        this.showTagDropdown = false;
      }
    },
    checkIfRoleIsSelected(id) {
      return this.selectedRoles.includes(id);
    },
    removeRole(id) {
      // If the selected roles array contains our id already we're gonna remove it out of the array.s
      if (this.selectedRoles.includes(id)) {
        const roleIndex = this.selectedRoles.findIndex((role) => role === id);
        if (roleIndex <= -1) {
          return;
        }
        this.selectedRoles.splice(roleIndex, 1);
      }
    },
    handleChange(value) {
      let selectedOption = find(
        this.currentField.options,
        v => v.value == value
      )

      this.selectOption(selectedOption)
    },
    async handleUpdate() {
      this.loading = true;

      const result = await API.updateMediaData(this.media.id, {
        alt: this.alt,
        copyright: this.copyright,
        tags: this.selectedTags,
        roles: this.selectedRoles
      });

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

  + .fixed {
    z-index: 129;
  }
}
</style>
