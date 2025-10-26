<template>
  <div class="p-6 max-w-3xl mx-auto bg-white rounded shadow">
    <div class="flex items-center gap-6 mb-6">
      <img
        v-if="user.avatar"
        :src="user.avatar"
        alt="Avatar"
        class="w-24 h-24 rounded-full object-cover border"
      />
      <div v-else class="w-24 h-24 rounded-full bg-blue-300 flex items-center justify-center text-xl font-bold text-gray-700 border">
        {{ userInitials }}
      </div>
      <div>
        <h1 class="text-2xl font-bold">{{ user.name || 'N/A' }}</h1>
        <p class="text-gray-600">{{ user.role || 'Manager PPIC' }}</p>
      </div>
    </div>

    <div v-if="loading" class="text-center py-10 text-gray-500">
      Memuat data...
    </div>

    <div v-else>
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="font-semibold text-gray-700">Email:</label>
          <p>{{ user.email || '-' }}</p>
        </div>
        <div>
          <label class="font-semibold text-gray-700">Jabatan:</label>
          <p>{{ user.role || '-' }}</p>
        </div>
        <div>
          <label class="font-semibold text-gray-700">Bergabung Pada:</label>
          <p>{{ formatDate(user.created_at) || '-' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ProfileView',
  data() {
    return {
      user: {},
      loading: false
    };
  },
  computed: {
    userInitials() {
      if (!this.user.name) return '?';
      return this.user.name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase();
    }
  },
  mounted() {
    this.loadUser();
  },
  methods: {

async loadUser() {
  this.loading = true;
  try {
    const token = localStorage.getItem('token'); 
    const response = await axios.get('/ppic/me', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
    this.user = response.data.user || {};
  } catch (error) {
    console.error('Gagal memuat data user:', error);
    this.user = {};
  } finally {
    this.loading = false;
  }
}
,
    formatDate(date) {
      if (!date) return '-';
      return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      });
    }
  }
};
</script>
