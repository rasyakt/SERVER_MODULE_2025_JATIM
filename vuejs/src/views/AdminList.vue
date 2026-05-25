<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const admins = ref([]);
const loading = ref(true);
const errorMsg = ref('');

const fetchAdmins = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const response = await api.get('/admins');
    admins.value = response.data.content || [];
  } catch (error) {
    console.error('Failed to fetch admins', error);
    errorMsg.value = 'Failed to load administrator data.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchAdmins();
});
</script>

<template>
  <div class="container">
    <div class="header-section mb-6">
      <h1>List Admin</h1>
      <p>Overview of all system administrators in the platform.</p>
    </div>

    
    <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>

    
    <div v-if="loading" class="text-center py-8">
      <p>Loading administrator list...</p>
    </div>

    
    <div v-else class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Created Timestamp</th>
            <th>Last Login Timestamp</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="admin in admins" :key="admin.username">
            <td class="font-bold">{{ admin.username }}</td>
            <td>{{ admin.created_at || '-' }}</td>
            <td>{{ admin.last_login_at || 'Never logged in' }}</td>
          </tr>
          <tr v-if="admins.length === 0">
            <td colspan="3" class="text-center text-muted">No administrators found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.header-section {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1rem;
}

.py-8 {
  padding: 3rem 0;
}
</style>
