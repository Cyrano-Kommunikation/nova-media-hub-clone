import API from '../api';

export default {
    methods: {
        async renameCollection(collectionId, newCollectionName) {
            try {
                await API.renameCollection(collectionId, newCollectionName);

                Nova.$toasted.success('Du hast die Kollektion erfolgreich umbenannt');

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
            }
        }
    }
}