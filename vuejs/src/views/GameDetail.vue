<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const router = useRouter();
const slug = route.params.slug;

const game = ref(null);
const scores = ref([]);
const loading = ref(true);
const scoresLoading = ref(false);
const errorMsg = ref('');


const scoreInput = ref(null);
const submitting = ref(false);
const submitSuccess = ref(false);

const currentUser = JSON.parse(localStorage.getItem('user'));
let pollingInterval = null;

const fetchGameDetails = async () => {
  try {
    const response = await api.get(`/games/${slug}`);
    game.value = response.data;
  } catch (error) {
    console.error('Failed to load game details', error);
    errorMsg.value = 'Failed to load game details.';
  }
};

const fetchScores = async (showLoading = false) => {
  if (showLoading) scoresLoading.value = true;
  try {
    const response = await api.get(`/games/${slug}/scores`);
    scores.value = response.data.scores || [];
  } catch (error) {
    console.error('Failed to fetch scores', error);
  } finally {
    if (showLoading) scoresLoading.value = false;
  }
};

onMounted(async () => {
  loading.value = true;
  await fetchGameDetails();
  await fetchScores(true);
  loading.value = false;

  
  pollingInterval = setInterval(() => {
    fetchScores(false);
  }, 5000);
});

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});


const displayedScores = computed(() => {
  if (!scores.value.length || !currentUser) return [];

  const topTen = scores.value.slice(0, 10).map((score, index) => {
    return {
      rank: index + 1,
      username: score.username,
      score: score.score,
      timestamp: score.timestamp,
      isSelf: score.username === currentUser.username
    };
  });

  const isUserInTopTen = topTen.some(score => score.isSelf);

  
  if (!isUserInTopTen) {
    const myBestScore = scores.value.find(s => s.username === currentUser.username);
    if (myBestScore) {
      topTen.push({
        rank: '-', 
        username: myBestScore.username,
        score: myBestScore.score,
        timestamp: myBestScore.timestamp,
        isSelf: true
      });
    }
  }

  return topTen;
});

const handlePostScore = async () => {
  if (scoreInput.value === null || scoreInput.value === '') {
    alert('Please enter a valid score.');
    return;
  }

  submitting.value = true;
  submitSuccess.value = false;

  try {
    const response = await api.post(`/games/${slug}/scores`, {
      score: parseInt(scoreInput.value)
    });

    if (response.data.status === 'success') {
      submitSuccess.value = true;
      scoreInput.value = null;
      
      fetchScores(false);

      setTimeout(() => {
        submitSuccess.value = false;
      }, 3000);
    }
  } catch (error) {
    alert('Failed to submit score.');
  } finally {
    submitting.value = false;
  }
};


const isAuthor = computed(() => {
  return game.value && currentUser && game.value.author === currentUser.username;
});


const gameIframeUrl = computed(() => {
  if (game.value && game.value.gamePath) {
    return `http://127.0.0.1:8000${game.value.gamePath}`;
  }
  return null;
});
</script>

<template>
  <div class="container">
    
    <div v-if="loading" class="text-center py-12">
      <p>Loading game details and rankings...</p>
    </div>

    
    <div v-else-if="errorMsg || !game" class="card text-center py-12">
      <h2 class="text-danger">Game Not Found</h2>
      <p>{{ errorMsg || 'The requested game does not exist or has been deleted.' }}</p>
      <router-link to="/games" class="btn btn-secondary mt-4">Back to Discovery</router-link>
    </div>

    <div v-else>
      
      <div class="game-header card flex-between mb-6">
        <div class="header-content">
          <div v-if="game.thumbnail" class="game-header-thumb-wrapper">
            <img :src="'http://127.0.0.1:8000' + game.thumbnail" :alt="game.title + ' Thumbnail'" class="game-header-thumb" />
          </div>
          <div class="game-header-details">
            <h1>{{ game.title }}</h1>
            <p class="mb-2">Developed by: <strong>{{ game.author }}</strong> • {{ game.scoreCount }} total score submissions</p>
            <p>{{ game.description }}</p>
          </div>
        </div>
        <div v-if="isAuthor" class="author-actions">
          <router-link :to="'/games/' + slug + '/manage'" class="btn btn-warning"><span class="material-symbols-outlined">settings</span> Manage Game</router-link>
        </div>
      </div>

      
      <div class="grid-layout">
        
        <div class="game-player card">
          <h2>Game Screen</h2>
          
          <div v-if="gameIframeUrl" class="iframe-container">
            <iframe 
              :src="gameIframeUrl" 
              class="game-iframe"
              sandbox="allow-scripts allow-same-origin"
              title="Game Player"
            ></iframe>
          </div>

          <div v-else class="no-game-files text-center py-8">
            <p>This game has no version uploaded yet.</p>
            <p v-if="isAuthor" class="mt-4">
              <router-link :to="'/games/' + slug + '/manage'" class="btn btn-primary">Upload Game ZIP Now</router-link>
            </p>
          </div>

          
          <div class="score-simulator mt-4 pt-4">
            <h3>Post Score simulation</h3>
            <p class="mb-4">Use this form to post your game scores directly and test the leaderboard ranking system.</p>
            
            <form @submit.prevent="handlePostScore" class="flex-layout">
              <input 
                type="number" 
                v-model="scoreInput" 
                class="form-input score-input" 
                placeholder="Enter score value" 
                required 
              />
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                {{ submitting ? 'Submitting...' : 'Post Score' }}
              </button>
            </form>
            <p v-if="submitSuccess" class="text-success mt-2">Score successfully submitted!</p>
          </div>
        </div>

        
        <div class="scoreboard card">
          <h2 class="flex-align"><span class="material-symbols-outlined" style="margin-right: 0.5rem; font-size: 1.5rem;">workspace_premium</span> Top Highscores</h2>
          <p class="mb-4">leaderboard updates automatically every 5 seconds.</p>

          <div v-if="scoresLoading" class="text-center py-4">
            <p>Loading leaderboard...</p>
          </div>

          <div v-else class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Player</th>
                  <th class="text-right">Highscore</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="score in displayedScores" 
                  :key="score.username"
                  :class="{ 'self-highlight': score.isSelf }"
                >
                  <td class="font-bold">{{ score.rank }}</td>
                  <td :class="{ 'font-bold': score.isSelf }">
                    <router-link :to="'/profile/' + score.username">
                      {{ score.username }} <span v-if="score.isSelf">(You)</span>
                    </router-link>
                  </td>
                  <td class="text-right font-bold score-value">{{ score.score }}</td>
                </tr>
                <tr v-if="displayedScores.length === 0">
                  <td colspan="3" class="text-center text-muted">No highscores yet. Be the first one to post!</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.game-header {
  padding: 2rem;
}

.header-content {
  display: flex;
  gap: 2rem;
  align-items: center;
  flex-grow: 1;
}

.game-header-thumb-wrapper {
  width: 160px;
  height: 100px;
  flex-shrink: 0;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.game-header-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.game-header-details {
  flex-grow: 1;
}

@media (max-width: 600px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  .game-header-thumb-wrapper {
    width: 100%;
    height: 150px;
  }
}

.grid-layout {
  display: grid;
  grid-template-columns: 3fr 2fr;
  gap: 1.5rem;
}

@media (max-width: 900px) {
  .grid-layout {
    grid-template-columns: 1fr;
  }
}

.iframe-container {
  position: relative;
  width: 100%;
  padding-top: 56.25%; 
  border: 1px solid var(--border-color);
  border-radius: 6px;
  background-color: #000;
  overflow: hidden;
  margin-top: 1rem;
}

.game-iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: none;
}

.no-game-files {
  border: 1px dashed var(--border-color);
  background-color: var(--bg-primary);
  border-radius: 8px;
  padding: 4rem 2rem;
  margin-top: 1rem;
}

.score-simulator {
  border-top: 1px solid var(--border-color);
}

.flex-layout {
  display: flex;
  gap: 0.75rem;
}

.score-input {
  max-width: 200px;
}


.self-highlight {
  background-color: #eff6ff !important;
  border-left: 3px solid var(--color-primary);
}

.self-highlight td {
  color: var(--color-primary) !important;
}

.score-value {
  color: #1e293b;
}

.py-12 { padding: 3rem 0; }
.py-8 { padding: 2rem 0; }
</style>
