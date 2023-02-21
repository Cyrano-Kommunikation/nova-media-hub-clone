import API from '../api';

export default {
  methods: {
    async updateCollection(collectionId, collectionData) {
      try {
        await API.updateCollection(collectionId, collectionData);

        Nova.$toasted.success('Du hast die Kollektion erfolgreich aktualisiert.');

        return true;
      } catch (e) {
        if (e && e.response && e.response.data) {
          const data = e.response.data;

          if (data.error) {
            Nova.$toasted.error(data.error);
          }

          return false;
        } else {
          Nova.$toasted.error(e.message);
          return false;
        }
      }
    },

    async createCollection(collectionName) {
      try {
        await API.createCollection(collectionName);

        Nova.$toasted.success('Die Kollektion wurde erfolgreich erstellt.');

        return true;
      } catch (e) {
        if (e && e.response && e.response.data) {
          const data = e.response.data;

          if (data.error) {
            Nova.$toasted.error(data.error);
          }

          return false;
        } else {
          Nova.$toasted.error(e.message);
          return false;
        }
      }
    },

    async deleteCollection(collectionId) {
      try {
        await API.deleteCollection(collectionId);

        Nova.$toasted.success('Die Kollektion wurde erfolgreich gelöscht. Alle darin enthaltenen Dateien wurden ebenfalls unwiderruflich gelöscht.');

        return true;
      } catch (e) {
        if (e && e.response && e.response.data) {
          const data = e.response.data;
          if (data.errors && data.errors.length) {
            data.errors.forEach(error => Nova.$toasted.error(error));
          }
        }
        return false;
      }
    }
  }
}
