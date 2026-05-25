<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const router = useRouter();
const slug = route.params.slug;

const game = ref(null);
const loading = ref(true);
const saving = ref(false);
const uploading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');


const titleInput = ref('');
const descInput = ref('');


const zipFileInput = ref(null);
const uploadError = ref('');
const uploadSuccess = ref('');

const currentUser = JSON.parse(localStorage.getItem('user'));

const fetchGameDetails = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const response = await api.get(`/games/${slug}`);
    game.value = response.data;
    
    
    if (currentUser && game.value.author !== currentUser.username) {
      router.push(`/games/${slug}`);
      return;
    }

    titleInput.value = game.value.title;
    descInput.value = game.value.description;
  } catch (error) {
    console.error('Failed to load game details', error);
    errorMsg.value = 'Failed to load game details.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchGameDetails();
});

const handleUpdateGame = async () => {
  if (!titleInput.value.trim() || !descInput.value.trim()) {
    alert('All fields are required.');
    return;
  }

  saving.value = true;
  errorMsg.value = '';
  successMsg.value = '';

  try {
    const response = await api.put(`/games/${slug}`, {
      title: titleInput.value.trim(),
      description: descInput.value.trim()
    });

    if (response.data.status === 'success') {
      successMsg.value = 'Game details updated successfully!';
      
      
      game.value.title = titleInput.value.trim();
      game.value.description = descInput.value.trim();
    }
  } catch (error) {
    if (error.response && error.response.data) {
      const data = error.response.data;
      if (data.violations) {
        const fields = Object.keys(data.violations);
        errorMsg.value = data.violations[fields[0]].message;
      } else {
        errorMsg.value = data.message || 'Failed to update game details.';
      }
    } else {
      errorMsg.value = 'Failed to connect to backend server.';
    }
  } finally {
    saving.value = false;
  }
};

const handleFileChange = (event) => {
  const files = event.target.files;
  if (files && files.length > 0) {
    zipFileInput.value = files[0];
  }
};

const handleUploadVersion = async () => {
  if (!zipFileInput.value) {
    uploadError.value = 'Please select a ZIP file to upload.';
    return;
  }

  uploading.value = true;
  uploadError.value = '';
  uploadSuccess.value = '';

  
  const formData = new FormData();
  formData.append('zipfile', zipFileInput.value);
  formData.append('token', localStorage.getItem('token')); 

  try {
    
    const response = await api.post(`/games/${slug}/upload`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.data.status === 'success') {
      uploadSuccess.value = `Version ${response.data.version} uploaded and extracted successfully!`;
      zipFileInput.value = null;
      
      const fileInputElement = document.getElementById('zip-file');
      if (fileInputElement) fileInputElement.value = '';
    }
  } catch (error) {
    
    
    if (error.response && typeof error.response.data === 'string') {
      uploadError.value = error.response.data;
    } else if (error.response && error.response.data && error.response.data.message) {
      uploadError.value = error.response.data.message;
    } else {
      uploadError.value = 'Failed to upload and extract ZIP file.';
    }
  } finally {
    uploading.value = false;
  }
};

const handleDeleteGame = async () => {
  const confirmation = confirm(`WARNING: Are you sure you want to permanently delete game "${game.value.title}"?\nThis will delete all versions and score leaderboards!`);
  if (!confirmation) return;

  try {
    await api.delete(`/games/${slug}`);
    alert('Game successfully deleted.');
    router.push('/games');
  } catch (error) {
    alert('Failed to delete game.');
  }
};
</script>

<template>
  <div class="container">
    
    <div v-if="loading" class="text-center py-12">
      <p>Loading developer settings...</p>
    </div>

    <div v-else-if="errorMsg || !game" class="card text-center py-12">
      <h2 class="text-danger">Failed to Load Settings</h2>
      <p>{{ errorMsg }}</p>
      <router-link to="/games" class="btn btn-secondary mt-4">Back to Games</router-link>
    </div>

    <div v-else>
      <div class="header-section flex-between mb-6">
        <div>
          <h1>Manage Game: <span class="accent">{{ game.title }}</span></h1>
          <p>Update credentials, release new versions, or delete this game.</p>
        </div>
        <router-link :to="'/games/' + slug" class="btn btn-secondary"><span class="material-symbols-outlined">sports_esports</span> Play/Preview Game</router-link>
      </div>

      <div class="grid-layout">
        
        <div class="card">
          <h2>Update Game Details</h2>
          <p class="mb-4">Change the public title and description of your game.</p>

          <div v-if="errorMsg" class="alert alert-danger mb-4">{{ errorMsg }}</div>
          <div v-if="successMsg" class="alert alert-success mb-4">{{ successMsg }}</div>

          <form @submit.prevent="handleUpdateGame">
            <div class="form-group">
              <label class="form-label" for="game-title">Game Title</label>
              <input 
                type="text" 
                id="game-title" 
                v-model="titleInput" 
                class="form-input" 
                placeholder="Min 3, max 60 characters" 
                required 
              />
            </div>

            <div class="form-group">
              <label class="form-label" for="game-desc">Description</label>
              <textarea 
                id="game-desc" 
                v-model="descInput" 
                class="form-input" 
                rows="4" 
                placeholder="Min 0, max 200 characters" 
                required
              ></textarea>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Saving...' : 'Update Details' }}
            </button>
          </form>
        </div>

        
        <div class="right-column">
          
          <div class="card mb-4">
            <h2>Release New Version</h2>
            <p class="mb-4">Upload a ZIP package containing the game files (index.html, assets, etc.). It will be extracted automatically on the server.</p>

            <div v-if="uploadError" class="alert alert-danger mb-4">{{ uploadError }}</div>
            <div v-if="uploadSuccess" class="alert alert-success mb-4">{{ uploadSuccess }}</div>

            <form @submit.prevent="handleUploadVersion">
              <div class="form-group">
                <label class="form-label" for="zip-file">Select ZIP File</label>
                <input 
                  type="file" 
                  id="zip-file" 
                  accept=".zip" 
                  @change="handleFileChange" 
                  class="form-input" 
                  required 
                />
              </div>

              <button type="submit" class="btn btn-primary" :disabled="uploading">
                {{ uploading ? 'Uploading & Extracting...' : 'Upload ZIP Package' }}
              </button>
            </form>
          </div>

          
          <div class="card danger-card">
            <h2>Danger Zone</h2>
            <p class="mb-4">Permanently delete this game, including all submitted scores and all versions. This action is irreversible.</p>

            <button @click="handleDeleteGame" class="btn btn-danger"><span class="material-symbols-outlined">delete</span> Permanently Delete Game</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.accent {
  color: var(--color-primary);
}

.header-section {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1.5rem;
}

.grid-layout {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 900px) {
  .grid-layout {
    grid-template-columns: 1fr;
  }
}

textarea.form-input {
  resize: vertical;
}

.danger-card {
  border-color: #fee2e2;
  background-color: #fff8f8;
}

.danger-card h2 {
  color: var(--color-danger);
}

.py-12 { padding: 3rem 0; }
</style>
