<template>
  <AppLayout title="Project Details">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ project.name }}
      </h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 mb-1">{{ project.name }}</h1>
            <div class="text-gray-500 dark:text-gray-400 text-sm">Project ID: <span class="font-mono">{{ project.id }}</span></div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Current Themes -->
          <div>
            <h3 class="font-semibold text-lg mb-2 dark:text-white">Themes in Project</h3>
            <ul v-if="project.themes.length" class="mb-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
              <li v-for="theme in project.themes" :key="theme.id" class="py-2 px-4 flex flex-col gap-1 relative group">
                <div class="flex items-center justify-between">
                  <span class="font-medium text-gray-800 dark:text-gray-100">{{ theme.title || theme.name }}</span>
                  <button @click="removeFromProject(theme, 'theme')" class="opacity-60 group-hover:opacity-100 transition ml-2 text-red-600 hover:text-red-800 dark:hover:text-red-400" title="Remove theme">
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
                <span class="text-gray-600 dark:text-gray-300 text-sm">{{ theme.description }}</span>
              </li>
            </ul>
            <div v-else class="text-center text-gray-400 dark:text-gray-500 italic py-6 bg-gray-50 dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 mb-4">
              No themes are associated with this project yet.
            </div>

            <div>
              <!-- Theme Search -->
              <div class="mb-2">
                <input v-model="themeSearch" type="text" placeholder="Search for themes to add..." class="w-full px-3 py-2 rounded-t border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring focus:border-blue-400" />
                <div v-if="themeSearchResults.length" class="mt-2 bg-white dark:bg-gray-800 border-t border-l border-r border-gray-200 dark:border-gray-700 rounded-b shadow">
                  <ul>
                    <li v-for="theme in themeSearchResults" :key="theme.id" class="flex items-center justify-between px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                      <div>
                        <span class="font-medium">{{ theme.title || theme.name }}</span>
                        <span class="text-xs text-gray-500 ml-2">{{ theme.description }}</span>
                      </div>
                      <button @click="addToProject(theme, 'theme')" class="ml-2 px-2 py-1 text-xs rounded bg-blue-500 text-white hover:bg-blue-600">Add</button>
                    </li>
                  </ul>
                </div>               
                 <div v-else-if="themeSearch" class="text-gray-400 dark:text-gray-500 italic py-2 border-l border-r border-b border-dashed border-gray-200 dark:border-gray-700 rounded-b px-4">
                    <span class="font-semibold">No results found</span> for "{{ themeSearch }}". Try a different search term.
                </div>
                <div v-else class="text-gray-400 dark:text-gray-500 italic py-2 border-l border-r border-b border-dashed border-gray-200 dark:border-gray-700 rounded-b px-4">
                    Start typing to search for themes to add.
                </div>

              </div>
              <!-- Theme Search Results -->
            </div>
          </div>
          <!-- Current Plugins -->
          <div>
            <h3 class="font-semibold text-lg dark:text-white mb-2">Plugins in Project</h3>
            <ul v-if="project.plugins.length" class="mb-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
              <li v-for="plugin in project.plugins" :key="plugin.id" class="py-2 px-4 flex flex-col gap-1 relative group">
                <div class="flex items-center justify-between">
                  <span class="font-medium text-gray-800 dark:text-gray-100">{{ plugin.title || plugin.name }}</span>
                  <button @click="removeFromProject(plugin, 'plugin')" class="opacity-60 group-hover:opacity-100 transition ml-2 text-red-600 hover:text-red-800 dark:hover:text-red-400" title="Remove plugin">
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
                <span class="text-gray-600 dark:text-gray-300 text-sm">{{ plugin.description }}</span>
              </li>
            </ul>
            <div v-else class="text-center text-gray-400 dark:text-gray-500 italic py-6 bg-gray-50 dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 mb-4">
              No plugins are associated with this project yet.
            </div>
            <div>
              <!-- Plugin Search -->
              <div class="mb-2">
                <input v-model="pluginSearch" type="text" placeholder="Search for plugins to add..." class="w-full px-3 py-2 rounded-t border-l border-r border-t border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring focus:border-blue-400" />
                <div v-if="pluginSearchResults?.total" class="mt-2 bg-white dark:bg-gray-800 border-l border-r border-gray-200 dark:border-gray-700 rounded shadow">
                  <ul class="flex flex-col divide-y divide-gray-200 dark:divide-gray-700">
                    <li v-for="plugin in (pluginSearchResults?.data ?? [])" :key="plugin.id" class="px-4 py-2.5 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-700">
                      <div class="flex flex-col">
                        <span class="font-medium dark:text-white text-xs">{{ plugin.name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-300">{{ plugin.description }}</span>
                      </div>
                      <button @click="addToProject(plugin, 'plugin')" class="ml-2 px-2 py-1 text-xs rounded bg-blue-500 text-white hover:bg-blue-600">Add</button>
                    </li>
                  </ul>
                </div>
                <div v-else-if="pluginSearch" class="text-gray-400 dark:text-gray-500 italic py-2 border-l border-r border-b border-dashed border-gray-200 dark:border-gray-700 rounded-b px-4">
                    <span class="font-semibold">No results found</span> for "{{ pluginSearch }}". Try a different search term.
                </div>
                <div v-else class="text-gray-400 dark:text-gray-500 italic py-2 2 border-l border-r border-b border-dashed border-gray-200 dark:border-gray-700 rounded-b px-4">
                    Start typing to search for plugins to add.
                </div>
                
              </div>
              <!-- Plugin Search Results -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, watch, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { TrashIcon } from '@heroicons/vue/24/outline';

const { project, themeQuery, pluginQuery } = defineProps({
  project: Object,
  pluginQuery: String,
  themeQuery: String,

  pluginSearchResults: Object,
  themeSearchResults: Object,
});

const themeSearch = ref(themeQuery || "");
const pluginSearch = ref(pluginQuery || "");

watch(themeSearch, (val) => {
  router.get(
    window.location.pathname,
    { pluginSearch: pluginSearch.value, themeSearch: val },
    {
      preserveState: true,
      preserveScroll: true,
      only: ["project", 'themeSearchResults'],
      replace: true,
    }
  );
});

watch(pluginSearch, (val) => {
  router.get(
    window.location.pathname,
    { pluginSearch: val, themeSearch: themeSearch.value },
    {
      preserveState: true,
      preserveScroll: true,
      only: ["project", 'pluginSearchResults'],
      replace: true,
    }
  );
});

async function addToProject(item, type) {
  const url = `/project/${project.id}/add-${type}`;
  try {
    await axios.post(url, { id: item.id });
    router.reload({ only: ['project', 'pluginSearchResults', 'themeSearchResults'] });
  } catch (e) {
    console.log(e);
    // Optionally handle error
  }
}

async function removeFromProject(item, type) {
  const url = `/project/${project.id}/remove-${type}`;
  try {
    await axios.post(url, { id: item.id });
    router.reload({
      only: ['project'],
    });
  } catch (e) {
    searchError.value = e.response?.data?.message || `Failed to remove ${type} from project.`;
  }
}
</script>
