<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import api from '../services/api';

const games = ref([]);
const loading = ref(false);
const errorMsg = ref('');

// Filter states
const sortBy = ref('title');
const sortDir = ref('asc');

// Pagination states for Infinite Scroll
const page = ref(0);
const size = ref(6); // Load 6 games per batch
const totalElements = ref(0);
const isLastPage = ref(false);

const fetchGames = async (reset = false) => {
  if (loading.value) return;

  loading.value = true;
  errorMsg.value = '';

  if (reset) {
    page.value = 0;
    games.value = [];
    isLastPage.value = false;
  }

  try {
    const response = await api.get('/games', {
      params: {
        page: page.value,
        size: size.value,
        sortBy: sortBy.value,
        sortDir: sortDir.value
      }
    });

    const newGames = response.data.content || [];
    totalElements.value = response.data.totalElements || 0;

    if (reset) {
      games.value = newGames;
    } else {
      games.value = [...games.value, ...newGames];
    }

    // Determine if we've reached the last page
    isLastPage.value = (page.value + 1) * size.value >= totalElements.value;
  } catch (error) {
    console.error('Failed to fetch games', error);
    errorMsg.value = 'Failed to load games list.';
  } finally {
    loading.value = false;
  }
};

const loadMoreGames = () => {
  if (isLastPage.value || loading.value) return;
  page.value++;
  fetchGames(false);
};

// Scroll listener for Infinite Scrolling
const handleScroll = () => {
  const scrollTop = window.scrollY || document.documentElement.scrollTop;
  const innerHeight = window.innerHeight;
  const scrollHeight = document.documentElement.scrollHeight;

  // Trigger load when scrolled close (within 150px) to the bottom
  if (scrollHeight - (scrollTop + innerHeight) < 150) {
    loadMoreGames();
  }
};

// Reset infinite scroll and fetch from start when filters change
watch([sortBy, sortDir], () => {
  fetchGames(true);
});

onMounted(() => {
  fetchGames(true);
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

// Helper for default graphic placeholder when thumbnail is null
const getThumbnail = (thumbnail) => {
  if (thumbnail) {
    return `http://127.0.0.1:8000${thumbnail}`;
  }
  // Standard minimalist default SVG thumbnail
  return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f1f5f9"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="10" fill="%2394a3b8">No Thumbnail</text></svg>';
};
</script>

<template>
  <div class="container">
    <div class="header-section flex-between mb-6">
      <div>
        <h1>Discover Games</h1>
        <p>Browse through hundreds of modern and retro browser games online!</p>
      </div>

      <!-- Simple Filter Controllers -->
      <div class="filters">
        <div class="filter-group">
          <label for="sort-by">Sort By</label>
          <select id="sort-by" v-model="sortBy" class="select-input">
            <option value="title">Title</option>
            <option value="popular">Popularity</option>
            <option value="uploaddate">Recently Updated</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="sort-dir">Direction</label>
          <select id="sort-dir" v-model="sortDir" class="select-input">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Notification Banners -->
    <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>

    <!-- Games Grid List -->
    <div class="games-grid">
      <div v-for="game in games" :key="game.slug" class="game-card">
        <!-- Game Thumbnail -->
        <div class="thumbnail-wrapper">
          <img :src="getThumbnail(game.thumbnail)" :alt="game.title + ' Thumbnail'" class="game-thumbnail" />
        </div>

        <!-- Game Info -->
        <div class="game-info">
          <h2 class="game-title">{{ game.title }}</h2>
          <span class="score-badge flex-align"><span class="material-symbols-outlined" style="font-size: 1rem; margin-right: 0.25rem;">workspace_premium</span> {{ game.scoreCount }} Scores</span>
          <p class="game-desc">{{ game.description }}</p>
          <div class="author-info">Developer: <strong>{{ game.author }}</strong></div>

          <!-- Accessible Link -->
          <router-link 
            :to="'/games/' + game.slug" 
            :aria-label="'Play ' + game.title + ' developed by ' + game.author" 
            class="btn btn-primary play-btn"
          >
            Play Game
          </router-link>
        </div>
      </div>
    </div>

    <!-- Loading Indicators -->
    <div v-if="loading" class="text-center py-6">
      <p class="loading-text">Loading more games...</p>
    </div>

    <div v-if="isLastPage && games.length > 0" class="text-center py-6 text-muted">
      <p>You have reached the end of the games list.</p>
    </div>

    <div v-if="!loading && games.length === 0" class="text-center py-12 card">
      <h2>No Games Found</h2>
      <p>Be the first one to upload a game version to make it visible to the public!</p>
    </div>
  </div>
</template>

<style scoped>
.header-section {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1.5rem;
}

.filters {
  display: flex;
  gap: 1rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-group label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--text-muted);
}

.select-input {
  padding: 0.45rem 1.5rem 0.45rem 0.75rem;
  font-family: inherit;
  font-size: 0.85rem;
  font-weight: 500;
  border: 1px solid var(--border-color);
  background-color: #fff;
  border-radius: 6px;
  outline: none;
  cursor: pointer;
}

/* Games Grid */
.games-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  margin-top: 1.5rem;
}

@media (max-width: 900px) {
  .games-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .games-grid {
    grid-template-columns: 1fr;
  }
}

.game-card {
  background-color: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.game-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
}

.thumbnail-wrapper {
  position: relative;
  width: 100%;
  padding-top: 56.25%; /* 16:9 Aspect Ratio */
  background-color: var(--bg-primary);
  border-bottom: 1px solid var(--border-color);
}

.game-thumbnail {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.game-info {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.game-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text-main);
  margin-bottom: 0.25rem;
}

.score-badge {
  align-self: flex-start;
  font-size: 0.75rem;
  font-weight: 600;
  background-color: #fef3c7;
  color: #d97706;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  margin-bottom: 0.75rem;
}

.game-desc {
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.4;
  margin-bottom: 1rem;
  flex-grow: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.author-info {
  font-size: 0.8rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border-color);
  padding-top: 0.75rem;
  margin-bottom: 0.75rem;
}

.play-btn {
  width: 100%;
}

.py-6 { padding: 1.5rem 0; }
.py-12 { padding: 3rem 0; }
.loading-text {
  font-size: 0.9rem;
  color: var(--text-muted);
}
</style>
