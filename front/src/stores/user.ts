export const useUserStore = defineStore('user', {
  actions: {
    async getProfile() {
      try {
        const response = await api.get('/profile');
        return response.data;
      } catch (error: any) {
        throw error;
      }
    },

    async updateProfile(data: any) {
      try {
        const response = await api.put('/profile', data);
        return response.data;
      } catch (error: any) {
        throw error;
      }
    }
  }
}); 