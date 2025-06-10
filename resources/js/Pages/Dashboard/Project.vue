<template>
  <AppLayout title="Project Details">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ project.name }}
      </h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 dark:text-white overflow-hidden shadow-xl sm:rounded-lg p-8 mb-8">
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
              <ul v-if="project.themes && project.themes.length" class="mb-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
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
            </div>
            <!-- Current Plugins -->
            <div>
              <h3 class="font-semibold text-lg dark:text-white mb-2">Plugins in Project</h3>
              <ul v-if="project.plugins && project.plugins.length" class="mb-4 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
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
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { TrashIcon } from '@heroicons/vue/24/outline';

const { project } = defineProps({
  project: Object
});

async function removeFromProject(item, type) {
  const url = `/project/${project.id}/remove-${type}`;
  try {
    await axios.post(url, { id: item.id });
      router.reload({
          only: ['project'],
      })
  } catch (e) {
    searchError.value = e.response?.data?.message || `Failed to remove ${type} from project.`;
  }
}
</script>
