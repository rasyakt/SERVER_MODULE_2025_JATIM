<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';

const users = ref([]);
const loading = ref(true);
const errorMsg = ref('');
const successMsg = ref('');

// CRUD modal variables
const isModalOpen = ref(false);
const modalMode = ref('create'); // 'create' or 'edit'
const userIdToEdit = ref(null);
const usernameInput = ref('');
const passwordInput = ref('');
const modalErrorMsg = ref('');

// Block popup variables
const isBlockModalOpen = ref(false);
const userIdToBlock = ref(null);
const blockReasonInput = ref('');

const fetchUsers = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const response = await api.get('/users');
    users.value = response.data.content || [];
  } catch (error) {
    console.error('Failed to fetch users', error);
    errorMsg.value = 'Failed to load platform users.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchUsers();
});

const openCreateModal = () => {
  modalMode.value = 'create';
  userIdToEdit.value = null;
  usernameInput.value = '';
  passwordInput.value = '';
  modalErrorMsg.value = '';
  isModalOpen.value = true;
};

const openEditModal = (user) => {
  modalMode.value = 'edit';
  userIdToEdit.value = user.id;
  usernameInput.value = user.username;
  passwordInput.value = ''; // Leave password input blank for edit
  modalErrorMsg.value = '';
  isModalOpen.value = true;
};

const handleSaveUser = async () => {
  modalErrorMsg.value = '';
  
  if (!usernameInput.value || !passwordInput.value) {
    modalErrorMsg.value = 'All fields are required.';
    return;
  }

  try {
    if (modalMode.value === 'create') {
      const response = await api.post('/users', {
        username: usernameInput.value,
        password: passwordInput.value
      });

      if (response.data.status === 'success') {
        successMsg.value = `User ${usernameInput.value} created successfully!`;
        isModalOpen.value = false;
        fetchUsers();
      }
    } else {
      const response = await api.put(`/users/${userIdToEdit.value}`, {
        username: usernameInput.value,
        password: passwordInput.value
      });

      if (response.data.status === 'success') {
        successMsg.value = `User ${usernameInput.value} updated successfully!`;
        isModalOpen.value = false;
        fetchUsers();
      }
    }
  } catch (error) {
    if (error.response && error.response.data) {
      const data = error.response.data;
      if (data.violations) {
        const fields = Object.keys(data.violations);
        modalErrorMsg.value = data.violations[fields[0]].message;
      } else {
        modalErrorMsg.value = data.message || 'Operation failed.';
      }
    } else {
      modalErrorMsg.value = 'Failed to connect to backend server.';
    }
  }
};

const handleDeleteUser = async (user) => {
  if (!confirm(`Are you sure you want to delete user ${user.username}?`)) {
    return;
  }

  errorMsg.value = '';
  successMsg.value = '';

  try {
    await api.delete(`/users/${user.id}`);
    successMsg.value = `User ${user.username} deleted successfully.`;
    fetchUsers();
  } catch (error) {
    if (error.response && error.response.data) {
      errorMsg.value = error.response.data.message || 'Failed to delete user.';
    } else {
      errorMsg.value = 'Failed to connect to backend server.';
    }
  }
};

const handleUnblock = async (user) => {
  errorMsg.value = '';
  successMsg.value = '';

  try {
    const response = await api.put(`/users/${user.id}`, {
      is_blocked: 0,
      block_reason: null
    });

    if (response.data.status === 'success') {
      successMsg.value = `User ${user.username} unblocked successfully.`;
      fetchUsers();
    }
  } catch (error) {
    errorMsg.value = 'Failed to unblock user.';
  }
};

const openBlockModal = (user) => {
  userIdToBlock.value = user.id;
  blockReasonInput.value = '';
  isBlockModalOpen.value = true;
};

const handleBlockSubmit = async () => {
  if (!blockReasonInput.value.trim()) {
    alert('Block reason is required.');
    return;
  }

  isBlockModalOpen.value = false;
  errorMsg.value = '';
  successMsg.value = '';

  try {
    const response = await api.put(`/users/${userIdToBlock.value}`, {
      is_blocked: 1,
      block_reason: blockReasonInput.value.trim()
    });

    if (response.data.status === 'success') {
      successMsg.value = 'User blocked successfully.';
      fetchUsers();
    }
  } catch (error) {
    errorMsg.value = 'Failed to block user.';
  }
};
</script>

<template>
  <div class="container">
    <div class="header-section flex-between mb-6">
      <div>
        <h1>List User</h1>
        <p>Manage, CRUD, block and unblock platform users and developers.</p>
      </div>
      <button @click="openCreateModal" class="btn btn-primary"><span class="material-symbols-outlined">add</span> Create User</button>
    </div>

    <!-- Notification Banners -->
    <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>
    <div v-if="successMsg" class="alert alert-success">{{ successMsg }}</div>

    <!-- Loading Screen -->
    <div v-if="loading" class="text-center py-8">
      <p>Loading users list...</p>
    </div>

    <!-- Users Table -->
    <div v-else class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Role</th>
            <th>Registered At</th>
            <th>Last Login At</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td class="font-bold">
              <router-link :to="'/profile/' + user.username">{{ user.username }}</router-link>
            </td>
            <td><span class="badge">{{ user.role }}</span></td>
            <td>{{ user.created_at || '-' }}</td>
            <td>{{ user.last_login_at || 'Never' }}</td>
            <td>
              <span v-if="user.is_blocked" class="text-danger font-bold flex-align" :title="user.block_reason">
                <span class="material-symbols-outlined" style="font-size: 1.1rem; margin-right: 0.25rem;">block</span> Blocked
              </span>
              <span v-else class="text-success font-bold flex-align">
                <span class="material-symbols-outlined" style="font-size: 1.1rem; margin-right: 0.25rem;">check_circle</span> Active
              </span>
            </td>
            <td class="text-right action-buttons">
              <!-- Block / Unblock Toggle -->
              <button 
                v-if="user.is_blocked" 
                @click="handleUnblock(user)" 
                class="btn btn-secondary btn-sm"
              >
                Unblock
              </button>
              <button 
                v-else 
                @click="openBlockModal(user)" 
                class="btn btn-secondary btn-sm"
              >
                Block
              </button>

              <!-- Edit & Delete -->
              <button @click="openEditModal(user)" class="btn btn-secondary btn-sm">Edit</button>
              <button @click="handleDeleteUser(user)" class="btn btn-danger btn-sm">Delete</button>
            </td>
          </tr>
          <tr v-if="users.length === 0">
            <td colspan="6" class="text-center text-muted">No users found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- CRUD Modal Dialog (Create/Edit) -->
    <div v-if="isModalOpen" class="modal-overlay">
      <div class="modal-content">
        <h2>{{ modalMode === 'create' ? 'Create User' : 'Edit User' }}</h2>
        <div v-if="modalErrorMsg" class="alert alert-danger mb-4">{{ modalErrorMsg }}</div>

        <form @submit.prevent="handleSaveUser">
          <div class="form-group">
            <label class="form-label" for="modal-username">Username</label>
            <input 
              type="text" 
              id="modal-username" 
              v-model="usernameInput" 
              class="form-input" 
              placeholder="Min 4 characters" 
              required 
            />
          </div>
          <div class="form-group">
            <label class="form-label" for="modal-password">Password</label>
            <input 
              type="password" 
              id="modal-password" 
              v-model="passwordInput" 
              class="form-input" 
              placeholder="Min 5 characters" 
              required 
            />
          </div>

          <div class="flex-between mt-4">
            <button @click="isModalOpen = false" type="button" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary">Save User</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Block Reason Prompt Modal -->
    <div v-if="isBlockModalOpen" class="modal-overlay">
      <div class="modal-content">
        <h2>Block User</h2>
        <p class="mb-4">Please provide a reason for blocking this user account:</p>
        
        <form @submit.prevent="handleBlockSubmit">
          <div class="form-group">
            <label class="form-label" for="block-reason">Block Reason</label>
            <textarea 
              id="block-reason" 
              v-model="blockReasonInput" 
              class="form-input" 
              rows="3" 
              placeholder="e.g. Violating community terms" 
              required
            ></textarea>
          </div>

          <div class="flex-between mt-4">
            <button @click="isBlockModalOpen = false" type="button" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-danger">Confirm Block</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.header-section {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1rem;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.badge {
  background-color: var(--bg-primary);
  border: 1px solid var(--border-color);
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  text-transform: uppercase;
  font-size: 0.75rem;
  font-weight: 600;
}

.py-8 {
  padding: 3rem 0;
}

textarea.form-input {
  resize: vertical;
}
</style>
