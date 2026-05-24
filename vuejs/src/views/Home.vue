<script setup>
import { ref, onMounted } from 'vue';

const username = ref('');
const role = ref('');

onMounted(() => {
  const userJson = localStorage.getItem('user');
  if (userJson) {
    const user = JSON.parse(userJson);
    username.value = user.username;
    role.value = user.role;
  }
});
</script>

<template>
  <div class="container">
    <div class="card welcome-card">
      <h1>Welcome back, <span class="accent">{{ username }}</span>!</h1>
      <p class="role-badge">Role: <strong>{{ role }}</strong></p>
      
      <hr class="divider" />

      <div v-if="role === 'admin'" class="menu-intro">
        <h2>Administrator Control Panel</h2>
        <p class="mb-4">As an administrator, you have access to administrative management operations:</p>
        <div class="grid-2">
          <router-link to="/admins" class="menu-box">
            <h3>List Admin</h3>
            <p>View all administrators registered in the gaming portal.</p>
          </router-link>
          <router-link to="/users" class="menu-box">
            <h3>List User</h3>
            <p>Manage platform users, block/unblock, edit details, or create new accounts.</p>
          </router-link>
        </div>
      </div>

      <div v-else class="menu-intro">
        <h2>User Dashboard</h2>
        <p class="mb-4">Welcome to the gaming portal! Browse games, play, or manage your creations:</p>
        <div class="grid-2">
          <router-link to="/games" class="menu-box">
            <h3>Discover Games</h3>
            <p>Explore all the browser games uploaded by other developers and play them online!</p>
          </router-link>
          <router-link :to="'/profile/' + username" class="menu-box">
            <h3>My Profile</h3>
            <p>View your statistics, authored games, upload new versions, and view your highest scores.</p>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.welcome-card {
  padding: 3rem;
  text-align: center;
  max-width: 800px;
  margin: 2rem auto;
}

.accent {
  color: var(--color-primary);
}

.role-badge {
  background-color: var(--bg-primary);
  display: inline-block;
  padding: 0.25rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  margin-top: 0.5rem;
  border: 1px solid var(--border-color);
}

.divider {
  border: none;
  border-top: 1px solid var(--border-color);
  margin: 2rem 0;
}

.menu-intro h2 {
  margin-bottom: 1.5rem;
}

.menu-box {
  display: block;
  text-align: left;
  padding: 1.5rem;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  background-color: #fff;
  transition: all 0.2s ease;
  color: inherit;
}

.menu-box:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.05);
}

.menu-box h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
  color: var(--color-primary);
}

.menu-box p {
  font-size: 0.85rem;
  line-height: 1.4;
}
</style>
