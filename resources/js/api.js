const PREFIX = '/nova-vendor/media-hub';

export default {
  async getMedia(params) {
    return Nova.request().get(`${PREFIX}/media`, {params});
  },

  async getCollections() {
    return Nova.request().get(`${PREFIX}/collections`);
  },

  async saveMediaToCollection(collectionName, formData) {
    return Nova.request().post(`${PREFIX}/media/save?collectionName=${collectionName}`, formData);
  },

  async deleteMedia(mediaId) {
    return Nova.request().delete(`${PREFIX}/media/${mediaId}`);
  },

  async moveMediaToCollection(mediaId, collection) {
    return Nova.request().post(`${PREFIX}/media/${mediaId}/move`, {collection});
  },

  async updateMediaData(mediaId, formData) {
    return Nova.request().post(`${PREFIX}/media/${mediaId}/data`, formData);
  },

  async updateCollection(oldCollectionName, collectionData) {
    return Nova.request().post(`${PREFIX}/collection/${oldCollectionName}/update`, {collectionData});
  },

  async deleteCollection(collectionId) {
    return Nova.request().delete(`${PREFIX}/collection/${collectionId}/delete`);
  },

  async createCollection(collectionName) {
    return Nova.request().post(`${PREFIX}/collection/store`, {collectionName});
  },

  async getRoles() {
    return Nova.request().get(`${PREFIX}/roles/retrieve`);
  },

  async getTags() {
    return Nova.request().get(`${PREFIX}/tags/retrieve`);
  },

  async getImage(fileName, fileId, mime_type) {
    return Nova.request().post(`${PREFIX}/image/retrieve`, {
      fileName,
      fileId,
      mime_type
    }, {
      responseType: 'blob'
    });
  },

  async downloadFile(fileId) {
    return Nova.request().post(`${PREFIX}/file/${fileId}/download`, {}, {
      responseType: 'blob'
    });
  },
  async getCollectionById(id) {
    return Nova.request().post(`${PREFIX}/collections/${id}/retrieve`, {});
  }
};
