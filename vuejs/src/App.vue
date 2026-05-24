<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from './services/api';

const router = useRouter();
const route = useRoute();

const token = ref(localStorage.getItem('token'));
const user = ref(null);

const loadUser = () => {
  token.value = localStorage.getItem('token');
  const userJson = localStorage.getItem('user');
  user.value = userJson ? JSON.parse(userJson) : null;
};

// Listen to route changes to dynamically refresh user state
watch(() => route.path, () => {
  loadUser();
});

onMounted(() => {
  loadUser();
});

const isLoggedIn = computed(() => {
  return !!token.value && !!user.value;
});

const isAdmin = computed(() => {
  return user.value && user.value.role === 'admin';
});

const handleLogout = async () => {
  try {
    // Attempt signout on backend
    await api.post('/auth/signout');
  } catch (error) {
    console.error('Signout failed on backend', error);
  } finally {
    // Clear session storage locally in all circumstances
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    token.value = null;
    user.value = null;
    router.push({ name: 'Login' });
  }
};
</script>

<template>
  <div id="app">
    <!-- Navbar (Visible only if logged in) -->
    <nav v-if="isLoggedIn" class="navbar">
      <div class="nav-brand">
        <router-link to="/"><span class="material-symbols-outlined">sports_esports</span> LKS Gaming</router-link>
      </div>

      <!-- Navigation Links -->
      <div class="nav-links">
        <router-link to="/" class="nav-item" exact-active-class="active">Home</router-link>

        <!-- Admin Only Menu -->
        <template v-if="isAdmin">
          <router-link to="/admins" class="nav-item" exact-active-class="active">List Admin</router-link>
          <router-link to="/users" class="nav-item" exact-active-class="active">List User</router-link>
        </template>

        <!-- Player / Developer Menu -->
        <template v-else>
          <router-link to="/games" class="nav-item" exact-active-class="active">Discover Games</router-link>
          <router-link v-if="user" :to="'/profile/' + user.username" class="nav-item" exact-active-class="active">My Profile</router-link>
        </template>
      </div>

      <!-- User Info & Logout -->
      <div v-if="user" class="nav-user">
        <span>Logged in as: <span class="nav-username">{{ user.username }}</span> ({{ user.role }})</span>
        <button @click="handleLogout" class="btn btn-secondary btn-sm">Logout</button>
      </div>
    </nav>

    <!-- Main Content Area -->
    <main>
      <router-view />
    </main>
  </div>
</template>

<style>
/* Navigation Active Link Styling */
.navbar a.router-link-active {
  color: var(--text-main);
  border-bottom: 2px solid var(--color-primary);
  padding-bottom: 0.25rem;
}
</style>
