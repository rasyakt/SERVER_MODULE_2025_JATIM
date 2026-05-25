<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();

const activeTab = ref('signin'); 
const username = ref('');
const password = ref('');
const confirmPassword = ref('');


const showPassword = ref(false);
const showRegPassword = ref(false);
const showConfirmPassword = ref(false);

const errorMsg = ref('');
const successMsg = ref('');
const loading = ref(false);

const resetForm = () => {
  username.value = '';
  password.value = '';
  confirmPassword.value = '';
  errorMsg.value = '';
  successMsg.value = '';
  showPassword.value = false;
  showRegPassword.value = false;
  showConfirmPassword.value = false;
};

const handleSignIn = async () => {
  if (!username.value || !password.value) {
    errorMsg.value = 'Username and password are required.';
    return;
  }

  loading.value = true;
  errorMsg.value = '';
  successMsg.value = '';

  try {
    const response = await api.post('/auth/signin', {
      username: username.value,
      password: password.value
    });

    if (response.data.status === 'success') {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      router.push({ name: 'Home' });
    }
  } catch (error) {
    if (error.response && error.response.data) {
      errorMsg.value = error.response.data.message || 'Login failed. Please check credentials.';
    } else {
      errorMsg.value = 'Failed to connect to the backend server.';
    }
  } finally {
    loading.value = false;
  }
};

const handleSignUp = async () => {
  if (!username.value || !password.value || !confirmPassword.value) {
    errorMsg.value = 'All fields are required.';
    return;
  }

  if (password.value !== confirmPassword.value) {
    errorMsg.value = 'Passwords do not match.';
    return;
  }

  loading.value = true;
  errorMsg.value = '';
  successMsg.value = '';

  try {
    const response = await api.post('/auth/signup', {
      username: username.value,
      password: password.value
    });

    if (response.data.status === 'success') {
      successMsg.value = 'Registration successful! Directing to dashboard...';
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      
      setTimeout(() => {
        router.push({ name: 'Home' });
      }, 1500);
    }
  } catch (error) {
    if (error.response && error.response.data) {
      const data = error.response.data;
      if (data.violations) {
        
        const fields = Object.keys(data.violations);
        errorMsg.value = data.violations[fields[0]].message;
      } else {
        errorMsg.value = data.message || 'Registration failed.';
      }
    } else {
      errorMsg.value = 'Failed to connect to the backend server.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1><span class="material-symbols-outlined" style="font-size: 2.2rem; vertical-align: middle;">sports_esports</span> LKS Gaming</h1>
        <p>Manage, upload, and play amazing web games.</p>
      </div>

      
      <div class="login-tabs">
        <button 
          @click="activeTab = 'signin'; resetForm()" 
          class="tab-btn" 
          :class="{ active: activeTab === 'signin' }"
        >
          Sign In
        </button>
        <button 
          @click="activeTab = 'signup'; resetForm()" 
          class="tab-btn" 
          :class="{ active: activeTab === 'signup' }"
        >
          Sign Up
        </button>
      </div>

      
      <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>
      <div v-if="successMsg" class="alert alert-success">{{ successMsg }}</div>

      
      <form v-if="activeTab === 'signin'" @submit.prevent="handleSignIn" class="login-form">
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input 
            type="text" 
            id="username" 
            v-model="username" 
            class="form-input" 
            placeholder="Enter username" 
            required 
          />
        </div>
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <div class="password-wrapper">
            <input 
              :type="showPassword ? 'text' : 'password'" 
              id="password" 
              v-model="password" 
              class="form-input" 
              placeholder="Enter password" 
              required 
            />
            <button type="button" class="toggle-password-btn" @click="showPassword = !showPassword" aria-label="Toggle Password Visibility">
              <span class="material-symbols-outlined" style="font-size: 1.25rem;">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          {{ loading ? 'Signing In...' : 'Sign In' }}
        </button>
      </form>

      
      <form v-if="activeTab === 'signup'" @submit.prevent="handleSignUp" class="login-form">
        <div class="form-group">
          <label class="form-label" for="reg-username">Username</label>
          <input 
            type="text" 
            id="reg-username" 
            v-model="username" 
            class="form-input" 
            placeholder="Min 4, max 60 characters" 
            required 
          />
        </div>
        <div class="form-group">
          <label class="form-label" for="reg-password">Password</label>
          <div class="password-wrapper">
            <input 
              :type="showRegPassword ? 'text' : 'password'" 
              id="reg-password" 
              v-model="password" 
              class="form-input" 
              placeholder="Min 5, max 10 characters" 
              required 
            />
            <button type="button" class="toggle-password-btn" @click="showRegPassword = !showRegPassword" aria-label="Toggle Password Visibility">
              <span class="material-symbols-outlined" style="font-size: 1.25rem;">{{ showRegPassword ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm-password">Confirm Password</label>
          <div class="password-wrapper">
            <input 
              :type="showConfirmPassword ? 'text' : 'password'" 
              id="confirm-password" 
              v-model="confirmPassword" 
              class="form-input" 
              placeholder="Confirm your password" 
              required 
            />
            <button type="button" class="toggle-password-btn" @click="showConfirmPassword = !showConfirmPassword" aria-label="Toggle Password Visibility">
              <span class="material-symbols-outlined" style="font-size: 1.25rem;">{{ showConfirmPassword ? 'visibility_off' : 'visibility' }}</span>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
          {{ loading ? 'Registering...' : 'Sign Up' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 80px);
  padding: 1rem;
}

.login-card {
  background-color: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  width: 100%;
  max-width: 420px;
  padding: 2.5rem;
  box-shadow: 0 10px 30px -10px rgba(15, 23, 42, 0.05);
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.login-header h1 {
  font-size: 2rem;
  margin-bottom: 0.25rem;
}

.login-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 1.5rem;
}

.tab-btn {
  flex: 1;
  background: none;
  border: none;
  font-family: inherit;
  font-size: 0.95rem;
  font-weight: 600;
  padding: 0.75rem;
  color: var(--text-muted);
  cursor: pointer;
  transition: color 0.15s ease;
  outline: none;
}

.tab-btn.active {
  color: var(--text-main);
  border-bottom: 2px solid var(--color-primary);
}

.btn-block {
  width: 100%;
  padding: 0.75rem;
  margin-top: 0.5rem;
}

.password-wrapper {
  position: relative;
  width: 100%;
}

.password-wrapper .form-input {
  padding-right: 2.75rem;
}

.toggle-password-btn {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color 0.15s ease;
}

.toggle-password-btn:hover {
  color: var(--text-main);
}
</style>
