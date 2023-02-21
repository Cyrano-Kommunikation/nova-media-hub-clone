<template>
  <Modal id="o1-nmh-confirm-rename-modal" :show="show" role="alertdialog" @close-via-escape="$emit('close')">
    <div class="o1-bg-white dark:o1-bg-gray-800 o1-rounded-lg o1-shadow-lg o1-overflow-hidden" style="width: 460px">
      <ModalHeader v-text="'Kollektion bearbeiten'"/>

      <ModalContent>
        <input v-model="collectionData.name"
               class="form-control form-input form-input-bordered w-full mb-4"
               placeholder="Kollektionsname" type="text">
        <input
          class="form-control form-input form-input-bordered w-full mt-4" placeholder="Rollen" type="text"
          @blur="closeRoleDropdown"
          @focus="showRoleDropdown = true">
        <div v-if="filterRoles.length > 0"
             v-show="showRoleDropdown"
             class="o1-w-[394px] o1-absolute o1-z-[1000] o1-top-[60px] o1-max-h-[150px] dark:o1-bg-gray-900 o1-bg-gray-500 o1-z-10 o1-rounded o1-border-gray-300 o1-border dark:o1-border-gray-700 o1-mt-2 shadow o1-overflow-hidden o1-overflow-y-scroll">
          <div v-for="role in filterRoles"
               class="o1-p-4 cursor-pointer o1-border-b o1-border-gray-300 dark:o1-border-gray-700 text-white"
               @click="addRole(role.id)">
            {{ role.name }}
          </div>
        </div>
        <div class="o1-mt-2">
          <span
            v-for="role in collectionData.roles"
            :key="role"
            class="o1-text-xs o1-px-3 o1-py-1 o1-bg-slate-300 dark:o1-text-black dark:o1-bg-slate-500 o1-m-1 last:o1-mr-0 o1-rounded-xl o1-inline-block">
                <span class="o1-inline-block">{{ getRoleById(role)?.name }}</span>
                <HeroiconsSolidX class="o1-w-3 o1-h-3 o1-ml-2 o1-inline-block cursor-pointer"
                                 @click="removeRole(role)"/>
              </span>
        </div>
      </ModalContent>

      <ModalFooter>
        <div class="o1-ml-auto">
          <LinkButton class="o1-mr-3" type="button" @click.prevent="$emit('close')">
            {{ __('novaMediaHub.closeButton') }}
          </LinkButton>

          <LoadingButton :disabled="loading" :processing="loading" component="LoadingButton"
                         @click.prevent="handleUpdate">
            Aktualisieren
          </LoadingButton>
        </div>
      </ModalFooter>
    </div>
  </Modal>
</template>

<script>
import HandlesCollectionActions from '../mixins/HandlesCollectionActions';
import Api from "../api";

export default {
  emits: ['confirm', 'close'],

  mixins: [HandlesCollectionActions],

  props: ['show', 'collection'],

  data: () => ({
    showRoleDropdown: false,
    loading: false,
    newCollectionName: "",
    roleName: "",
    roles: [],
    collectionData: {
      name: "",
      roles: []
    }
  }),

  watch: {
    async show(newValue) {
      if (newValue) {
        const result = await Api.getCollectionById(this.collection);
        this.collectionData.name = result.data.name;
        this.collectionData.roles = result.data.roles.map(role => role.id);
      }
    }
  },
  async mounted() {
    const result = await Api.getRoles();
    this.roles = result.data;
  },
  computed: {
    filterRoles() {
      const selectedRoles = this.collectionData.roles;
      let resultRoles = [];
      for (let i = 0; i < this.roles.length; i++) {
        if (!selectedRoles.includes(this.roles[i].id)) {
          resultRoles.push(this.roles[i]);
        }
      }
      return resultRoles;
    }
  },

  methods: {
    removeRole(id) {
      // If the selected roles array contains our id already we're gonna remove it out of the array.s
      if (this.collectionData.roles.includes(id)) {
        const roleIndex = this.collectionData.roles.findIndex((role) => role === id);
        if (roleIndex <= -1) {
          return;
        }
        this.collectionData.roles.splice(roleIndex, 1);
      }
    },
    getRoleById(id) {
      for (let i = 0; i < this.roles.length; i++) {
        if (this.roles[i].id == id) {
          return this.roles[i];
        }
      }
    },
    closeRoleDropdown() {
      setTimeout(() => {
        this.showRoleDropdown = false;
      }, 250);
    },
    addRole(id) {
      if (!this.collectionData.roles.includes(id)) {
        this.collectionData.roles.push(id);
      }
    },
    async handleUpdate() {
      this.loading = true;

      const result = await this.updateCollection(this.collection, this.collectionData);

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
