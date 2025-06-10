<template>
  <AppLayout title="Search">
    <div class="max-w-7xl w-full mx-auto py-8 px-8">
      <div class="flex flex-col items-center mb-8">
        <h1 class="text-3xl font-extrabold mb-2 text-gray-900 dark:text-gray-100">🔎 Search Plugins & Themes</h1>
        <p class="text-gray-500 dark:text-gray-400">Find the best plugins and themes for your project.</p>
      </div>
      <form @submit.prevent="performSearch" class="mb-8 flex flex-col md:flex-row gap-4 items-center justify-center">
        <input
            autofocus
          v-model="query"
          type="text"
          placeholder="Search for plugins or themes..."
          class="border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2 flex-1 focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm transition"
          ref="inputRef"
        />
        <select v-model="sortBy" class="border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm pr-8">
          <option value="relevance">Relevance</option>
          <option value="downloads">Downloads</option>
          <option value="git_stars">GitHub Stars</option>
          <option value="git_forks">GitHub Forks</option>
          <option value="favers">Favorites</option>
          <option value="git_watchers">Git Watchers</option>
          <option value="last_updated_at">Last Updated</option>
        </select>
        <select v-model="direction" class="border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm pr-8">
          <option value="desc">Desc</option>
          <option value="asc">Asc</option>
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 transition text-white px-6 py-2 rounded-lg font-semibold shadow">Search</button>
      </form>
      <div v-if="loading" class="flex justify-center text-blue-500 dark:text-blue-400 text-lg font-medium py-8">
        <svg class="animate-spin h-5 w-5 mr-3 inline-block" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
        Searching...
      </div>
      <div v-else>
        <div v-if="results.length === 0 && searched" class="text-center text-gray-500 dark:text-gray-400 py-8">No results found.</div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="item in results" :key="item.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-6 bg-white dark:bg-gray-800 shadow hover:shadow-lg transition">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide" :class="item.keywords.includes('theme') ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'"></span>
                <div class="flex justify-end">
                    <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                        <span class="text-sm ">{{ item.downloads.toLocaleString() }}</span> <CloudArrowDownIcon  class="w-5 h-5 stroke-current fill-none"/>
                    </div>
                </div>
            </div>
            <h2 class="font-bold text-xl mb-1 text-gray-900 dark:text-gray-100">{{ item.name }}</h2>
            <p class="text-gray-700 dark:text-gray-300 min-h-[48px]">{{ item.description }}</p>
            <div class="flex justify-end mt-4 gap-2">
              <a v-if="item.url" :href="item.url" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">View Details</a>
              <button @click="openAddToProjectModal(item)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold shadow transition">
                Add to Project
              </button>
            </div>

              <div class="flex flex-wrap gap-2 mt-3">
                  <div v-for="keyword in item.keywords ?? []" class="bg-amber-200 dark:bg-amber-800 dark:text-amber-100 px-1.5 rounded-lg shadow">{{ keyword }}</div>
              </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add to Project Modal -->
    <div v-if="showAddToProjectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white dark:bg-gray-900 p-6 rounded shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4 dark:text-white">Add to Project</h3>
        <div class="mb-4">
          <input v-model="projectSearch" type="text" placeholder="Search projects..." class="w-full border rounded px-3 py-2 bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 mb-2" />
          <p class="text-gray-700 dark:text-gray-300">Select a project to add this {{ selectedType }} to:</p>
        </div>
        <div class="grid grid-cols-1 gap-4 max-h-64 overflow-y-auto">
          <div v-for="project in filteredProjects.data" :key="project.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800 shadow hover:shadow-lg transition flex items-center justify-between">
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ project.name }}</h4>
              <p class="text-gray-500 dark:text-gray-400 text-sm">{{ project.description }}</p>
            </div>
            <button @click="addToProject(project.id)" class="bg-blue-600 hover:bg-blue-700 transition text-white px-4 py-2 rounded-lg font-semibold shadow">
              Add
            </button>
          </div>
        </div>
        <div v-if="filteredProjects.last_page > 1" class="flex justify-between items-center mt-4">
          <button
            @click="handleProjectPageChange(filteredProjects.current_page - 1)"
            class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded"
            :disabled="filteredProjects.current_page === 1 || projectLoading"
          >
            Previous
          </button>
          <button
            @click="handleProjectPageChange(filteredProjects.current_page + 1)"
            class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded"
            :disabled="filteredProjects.current_page === filteredProjects.last_page || projectLoading"
          >
            Next
          </button>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="closeAddToProjectModal" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded">Cancel</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import {ref, useTemplateRef, watch, onMounted, computed} from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import { CloudArrowDownIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
  results: [Object, Array],
  query: String,
  sortBy: String,
  direction: String,
  loading: Boolean,
  searched: Boolean,
  projects: Array
});

const query = ref(props.query || '');
const sortBy = ref(props.sortBy || 'downloads');
const direction = ref(props.direction || 'asc');
const loading = ref(false);
const results = ref(props.results?.data || []);
const inputRef = ref(null);
const showAddToProjectModal = ref(false);
const selectedItem = ref(null);
const selectedType = ref(null);
const filteredProjects = ref({ data: [], current_page: 1, last_page: 1 });
const projectSearch = ref("");
const projectLoading = ref(false);

onMounted(() => {
  if (inputRef.value) inputRef.value.focus();
});

function performSearch() {
  loading.value = true;
  router.get(
    '/search',
    { query: query.value, sortBy: sortBy.value, direction: direction.value },
    {
      preserveState: false,
      preserveScroll: true,
      only: ['results', 'query', 'sortBy', 'direction', 'projects'],
      onSuccess: () => {
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      }
    }
  );
}
watch(query, performSearch);
watch(sortBy, performSearch);
watch(direction, performSearch);

function openAddToProjectModal(item) {
  selectedItem.value = item;
  selectedType.value = item.keywords && item.keywords.includes('theme') ? 'theme' : 'plugin';
  showAddToProjectModal.value = true;
  fetchProjects();
}
function closeAddToProjectModal() {
  showAddToProjectModal.value = false;
  selectedItem.value = null;
  selectedType.value = null;
  projectSearch.value = "";
}

async function fetchProjects(page = 1) {
  projectLoading.value = true;
  try {
    const { data } = await axios.get('/api/projects/search', {
      params: {
        query: projectSearch.value,
        page,
        per_page: 10
      }
    });
    filteredProjects.value = data;
  } finally {
    projectLoading.value = false;
  }
}

function handleProjectPageChange(page) {
  fetchProjects(page);
}

async function addToProject(projectId) {
  if (!selectedItem.value || !selectedType.value) return;
  await router.post(`/project/${projectId}/add-${selectedType.value}`, { id: selectedItem.value.id }, {
    preserveState: true,
    onSuccess: () => {
      closeAddToProjectModal();
    }
  });
}
</script>

<style scoped>
/* Extra dark mode support for backgrounds and text */
:deep(.dark) .bg-white { background-color: #18181b !important; }
:deep(.dark) .text-gray-900 { color: #f3f4f6 !important; }
:deep(.dark) .border-gray-200 { border-color: #27272a !important; }
</style>
