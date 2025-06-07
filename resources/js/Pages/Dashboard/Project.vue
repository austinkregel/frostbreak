<template>
  <AppLayout title="Project Details">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ project.name }}
      </h2>
    </template>
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 dark:text-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-8">
          <div class="mb-2 gap-2 flex">
            <span class="font-bold">Name:</span>
            <span>{{ project.name || 'No name.' }}</span>
          </div>
          <div class="mb-2">
            <span class="font-bold">ID:</span>
            <span>{{ project.id }}</span>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Current Themes -->
          <div>
            <h3 class="font-semibold text-lg mb-2 dark:text-white">Themes in Project</h3>
            <ul v-if="project.themes && project.themes.length" class="mb-4 bg-gray-50 dark:bg-gray-900 rounded">
              <li v-for="theme in project.themes" :key="theme.id" class="py-1 px-2 border-b border-gray-200 dark:border-gray-700 dark:text-white last:border-b-0">
                {{ theme.title || theme.name }}
              </li>
            </ul>
            <div v-else class="text-gray-500 dark:text-gray-400 mb-4">No themes added yet.</div>
          </div>
          <!-- Current Plugins -->
          <div>
            <h3 class="font-semibold text-lg dark:text-white mb-2">Plugins in Project</h3>
            <ul v-if="project.plugins && project.plugins.length" class="mb-4 bg-gray-50 dark:bg-gray-900 rounded">
              <li v-for="plugin in project.plugins" :key="plugin.id" class="py-1 px-2 border-b border-gray-200 dark:border-gray-700 dark:text-white last:border-b-0">
                {{ plugin.title || plugin.name }}
              </li>
            </ul>
            <div v-else class="text-gray-500 dark:text-gray-400 mb-4">No plugins added yet.</div>
          </div>
        </div>
        <div class="mt-10">
          <div class="flex gap-4 mb-4">
            <button class="dark:text-white" :class="{'font-bold underline': searchType==='themes'}" @click="searchType='themes'">Add Theme</button>
            <button class="dark:text-white" :class="{'font-bold underline': searchType==='plugins'}" @click="searchType='plugins'">Add Plugin</button>
          </div>
          <input
            v-model="searchQuery"
            class="border rounded px-3 py-2 w-full mb-4 dark:bg-gray-800 dark:text-white dark:border-gray-700"
            :placeholder="`Search for ${searchType}`"
          />
          <div v-if="searching" class="mb-2 text-blue-600 dark:text-blue-400">Searching...</div>
          <div v-if="searchError" class="mb-2 text-red-600 dark:text-red-400">{{ searchError }}</div>
          <ul v-if="searchResults.length">
            <li v-for="item in searchResults" :key="item.id || item.name || item.title" class="flex justify-between items-center border-b py-2 dark:border-gray-700">
              <span class="dark:text-white">{{ item.title || item.name }}</span>
              <button class="ml-4 px-3 py-1 bg-blue-600 text-white rounded dark:bg-blue-700" @click="addToProject(item)">Add</button>
            </li>
          </ul>
          <div v-else-if="searchQuery && !searching" class="dark:text-white">No results found.</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, watch } from "vue";

const { project } = defineProps({
  project: Object
});

const searchType = ref('themes');
const searchQuery = ref("");
const searchResults = ref([]);
const searching = ref(false);
const searchError = ref("");

// Debounce search
let searchTimeout;
watch(searchQuery, (val) => {
  clearTimeout(searchTimeout);
  if (val.length < 2) {
    searchResults.value = [];
    searchError.value = "";
    return;
  }
  searching.value = true;
  searchError.value = "";
  searchTimeout = setTimeout(() => {
    performSearch();
  }, 400);
});

async function performSearch() {
  const url = searchType.value === 'themes' ? '/theme/search' : '/plugin/search';
  searching.value = true;
  searchError.value = "";
  try {
    const { data } = await axios.post('/marketplace'+url, { query: searchQuery.value });
    searchResults.value = data.results || data || [];
  } catch (e) {
    searchResults.value = [];
    searchError.value = e.response?.data?.message || 'Failed to fetch search results.';
  } finally {
    searching.value = false;
  }
}

async function addToProject(item) {
  const url = `/project/${project.id}/add-${searchType.value.slice(0, -1)}`;
  try {
    await axios.post(url, { id: item.id });
    // Optionally, refetch project data or reload
    window.location.reload();
  } catch (e) {
    searchError.value = e.response?.data?.message || 'Failed to add to project.';
  }
}
</script>
