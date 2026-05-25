<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const username = ref(route.params.username);

const profile = ref(null);
const loading = ref(true);
const errorMsg = ref('');

const fetchProfile = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const response = await api.get(`/users/${username.value}`);
    profile.value = response.data;
  } catch (error) {
    console.error('Failed to load user profile', error);
    errorMsg.value = 'Failed to load user profile details.';
  } finally {
    loading.value = false;
  }
};

// Re-fetch profile if username parameter in URL changes
watch(() => route.params.username, (newUsername) => {
  if (newUsername) {
    username.value = newUsername;
    fetchProfile();
  }
});

onMounted(() => {
  fetchProfile();
});

// Helper for default graphic placeholder when thumbnail is null
const getThumbnail = (thumbnail) => {
  if (thumbnail) {
    return `http://127.0.0.1:8000${thumbnail}`;
  }
  return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f1f5f9"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="10" fill="%2394a3b8">No Thumbnail</text></svg>';
};

// Game Creation State
const isGameModalOpen = ref(false);
const titleInput = ref('');
const descInput = ref('');
const gameErrorMsg = ref('');
const creatingGame = ref(false);

const currentUser = JSON.parse(localStorage.getItem('user'));
const isDeveloper = computed(() => {
  return currentUser && currentUser.role === 'dev';
});
const isMyProfile = computed(() => {
  return currentUser && profile.value && currentUser.username === profile.value.username;
});

const openCreateGameModal = () => {
  titleInput.value = '';
  descInput.value = '';
  gameErrorMsg.value = '';
  isGameModalOpen.value = true;
};

const handleCreateGame = async () => {
  if (!titleInput.value.trim() || !descInput.value.trim()) {
    gameErrorMsg.value = 'All fields are required.';
    return;
  }

  creatingGame.value = true;
  gameErrorMsg.value = '';

  try {
    const response = await api.post('/games', {
      title: titleInput.value.trim(),
      description: descInput.value.trim()
    });

    if (response.data.status === 'success') {
      isGameModalOpen.value = false;
      // Refresh user profile to show newly created game
      fetchProfile();
    }
  } catch (error) {
    if (error.response && error.response.data) {
      const data = error.response.data;
      if (data.violations) {
        const fields = Object.keys(data.violations);
        gameErrorMsg.value = data.violations[fields[0]].message;
      } else {
        gameErrorMsg.value = data.message || 'Failed to create game.';
      }
    } else {
      gameErrorMsg.value = 'Failed to connect to backend server.';
    }
  } finally {
    creatingGame.value = false;
  }
};
</script>

<template>
  <div class="container">
    <!-- Loading Screen -->
    <div v-if="loading" class="text-center py-12">
      <p>Loading profile details...</p>
    </div>

    <!-- Error Screen -->
    <div v-else-if="errorMsg || !profile" class="card text-center py-12">
      <h2 class="text-danger">Profile Not Found</h2>
      <p>{{ errorMsg || 'The requested user profile does not exist.' }}</p>
      <router-link to="/" class="btn btn-secondary mt-4">Back to Dashboard</router-link>
    </div>

    <div v-else>
      <!-- User Profile Header -->
      <div class="profile-header card flex-between mb-6">
        <div class="flex-align" style="gap: 2rem;">
          <div class="profile-avatar"><span class="material-symbols-outlined" style="font-size: 3.5rem; color: var(--text-muted);">person</span></div>
          <div>
            <h1>{{ profile.username }}</h1>
            <p>Registered at: <strong>{{ new Date(profile.registeredTimestamp).toLocaleDateString() }}</strong></p>
          </div>
        </div>
        <div v-if="isDeveloper && isMyProfile" class="profile-actions">
          <button @click="openCreateGameModal" class="btn btn-primary">
            <span class="material-symbols-outlined">add</span> Create New Game
          </button>
        </div>
      </div>

      <!-- Welcome Developer Card (Shown only when developer has no games created yet) -->
      <div v-if="isDeveloper && isMyProfile && (!profile.authoredGames || profile.authoredGames.length === 0)" class="mb-6 card text-center py-8">
        <h2>No Games Created Yet</h2>
        <p class="mb-4">As a developer, you can create and upload browser games to the portal!</p>
        <button @click="openCreateGameModal" class="btn btn-primary">
          <span class="material-symbols-outlined">add</span> Create Your First Game
        </button>
      </div>

      <!-- Authored Games Section (Omitted if user has not uploaded any games) -->
      <div v-if="profile.authoredGames && profile.authoredGames.length > 0" class="mb-6">
        <h2 class="section-title">Authored Games</h2>
        <p class="section-subtitle">Games developed and updated by {{ profile.username }}.</p>

        <div class="games-list mt-4">
          <div v-for="game in profile.authoredGames" :key="game.slug" class="authored-game-card card">
            <div class="game-item-layout">
              <!-- Thumbnail -->
              <div class="game-thumb-wrapper">
                <img :src="getThumbnail(game.thumbnail)" :alt="game.title + ' Thumbnail'" class="game-thumb" />
              </div>

              <!-- Info -->
              <div class="game-details">
                <h3 class="game-title">{{ game.title }}</h3>
                <span v-if="game.scoreCount !== undefined" class="score-badge flex-align"><span class="material-symbols-outlined" style="font-size: 0.9rem; margin-right: 0.25rem;">workspace_premium</span> {{ game.scoreCount }} scores submitted</span>
                <p class="game-desc">{{ game.description }}</p>
                
                <div class="game-actions">
                  <router-link :to="'/games/' + game.slug" class="btn btn-primary btn-sm">Play</router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Highscores Section -->
      <div class="highscores-section mb-6">
        <h2 class="section-title">Personal Best Highscores</h2>
        <p class="section-subtitle">Highest scores achieved by {{ profile.username }} grouped by game played (Sorted alphabetically by game title).</p>

        <div class="table-responsive mt-4">
          <table class="table">
            <thead>
              <tr>
                <th>Game</th>
                <th>Description</th>
                <th class="text-right">Highest Score</th>
                <th>Achievement Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in profile.highscores" :key="item.game.slug">
                <td class="font-bold">
                  <router-link :to="'/games/' + item.game.slug">{{ item.game.title }}</router-link>
                </td>
                <td class="text-muted text-sm">{{ item.game.description }}</td>
                <td class="text-right font-bold text-success">{{ item.score }}</td>
                <td>{{ new Date(item.timestamp).toLocaleDateString() }}</td>
              </tr>
              <tr v-if="profile.highscores.length === 0">
                <td colspan="4" class="text-center text-muted">No highscore achievements yet. Try playing some games first!</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Create Game Modal Overlay -->
    <div v-if="isGameModalOpen" class="modal-overlay">
      <div class="modal-content">
        <h2>Create New Game</h2>
        <div v-if="gameErrorMsg" class="alert alert-danger mb-4">{{ gameErrorMsg }}</div>

        <form @submit.prevent="handleCreateGame">
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
              rows="3" 
              placeholder="Min 0, max 200 characters" 
              required
            ></textarea>
          </div>

          <div class="flex-between mt-4">
            <button @click="isGameModalOpen = false" type="button" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="creatingGame">
              {{ creatingGame ? 'Creating...' : 'Create Game' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-header {
  padding: 2.5rem;
  display: flex;
  align-items: center;
  gap: 2rem;
}

@media (max-width: 600px) {
  .profile-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
  }
  
  .profile-actions {
    width: 100%;
  }
  
  .profile-actions button {
    width: 100%;
  }
}

.profile-avatar {
  font-size: 3.5rem;
  background-color: var(--bg-primary);
  border: 1px solid var(--border-color);
  width: 90px;
  height: 90px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.section-subtitle {
  font-size: 0.85rem;
  color: var(--text-muted);
}

/* Authored Games List Layout */
.game-item-layout {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

@media (max-width: 600px) {
  .game-item-layout {
    flex-direction: column;
    align-items: stretch;
  }
}

.game-thumb-wrapper {
  width: 140px;
  height: 90px;
  flex-shrink: 0;
  border-radius: 6px;
  border: 1px solid var(--border-color);
  overflow: hidden;
  background-color: var(--bg-primary);
}

.game-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.game-details {
  flex-grow: 1;
}

.game-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-main);
  margin-bottom: 0.25rem;
}

.score-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  background-color: #ecfdf5;
  color: var(--color-success);
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  margin-bottom: 0.5rem;
}

.game-desc {
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.4;
  margin-bottom: 0.75rem;
}

.text-sm {
  font-size: 0.8rem;
}

.py-12 { padding: 3rem 0; }
</style>
