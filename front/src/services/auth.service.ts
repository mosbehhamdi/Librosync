import axios from 'axios';

const API_URL = 'https://librosync.tn/api/';

export default {
  async login(email: string, password: string) {
    const response = await axios.post(API_URL + 'login', {
      email,
      password
    });
    if (response.data.token) {
      localStorage.setItem('user', JSON.stringify(response.data));
    }
    return response.data;
  },

  async register(name: string, email: string, password: string, password_confirmation: string) {
    return axios.post(API_URL + 'register', {
      name,
      email,
      password,
      password_confirmation
    });
  },

  logout() {
    localStorage.removeItem('user');
  }
};
