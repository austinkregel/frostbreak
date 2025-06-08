<template>
  <AppLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Welcome back, {{ user.name }}
      </h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Recent Projects Card -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4 dark:text-white">Recent Projects</h3>
            <ul v-if="recentProjects.length" class="space-y-2">
              <li v-for="project in recentProjects" :key="project.id" class="flex justify-between items-center">
                <span class="truncate">{{ project.name }}</span>
                <Link :href="`/project/${project.id}`" class="text-blue-600 dark:text-blue-400 underline">View</Link>
              </li>
            </ul>
            <div v-else class="text-gray-500 dark:text-gray-400">No recent projects.</div>
            <div class="mt-4 text-right">
              <Link href="/projects" class="text-sm text-blue-600 dark:text-blue-400 underline">View all projects</Link>
            </div>
          </div>
          <!-- Quick Actions Card -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4 dark:text-white">Quick Actions</h3>
            <div class="flex flex-col gap-3">
              <Link href="/projects" class="px-4 py-2 bg-blue-600 text-white dark:bg-blue-700 dark:text-white rounded hover:bg-blue-700">Manage Projects</Link>
              <Link href="/search" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-white rounded hover:bg-gray-300 dark:hover:bg-gray-600">Search Plugins & Themes</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const recentProjects = computed(() => (page.props.recentProjects || []).slice(0, 5));
</script>
