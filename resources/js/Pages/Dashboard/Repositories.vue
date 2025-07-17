<template>
  <AppLayout title="My Packages">
    <div class="max-w-7xl w-full mx-auto py-8 px-8">
      <div class="flex flex-col items-center mb-8">
        <h1 class="text-3xl font-extrabold mb-2 text-gray-900 dark:text-gray-100">My Packages</h1>
        <p class="text-gray-500 dark:text-gray-400">These are packages linked to repositories you own or have access to.</p>
      </div>
      <div class="dark:text-white">
        <div v-if="repositories.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">No packages found.</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full bg-white dark:bg-gray-800 border dark:border-gray-700 overflow-hidden shadow-xl rounded-lg">
            <thead>
              <tr>
                <th class="text-left py-2 px-4 border-b dark:border-gray-700">Name</th>
                <th class="text-left py-2 px-4 border-b dark:border-gray-700">Description</th>
                <th class="text-left py-2 px-4 border-b dark:border-gray-700">Private</th>
                <th class="text-left py-2 px-4 border-b dark:border-gray-700">Claimed as Package</th>
                <th class="text-left py-2 px-4 border-b dark:border-gray-700">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="repo in repositories" :key="repo.id" :class="{'bg-yellow-100 dark:bg-yellow-900': repo.package?.user_id}">
                <td class="py-2 px-4 border-b dark:border-gray-700">
                  <a :href="repo.html_url" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">{{ repo.full_name }}</a>
                </td>
                <td class="py-2 px-4 border-b dark:border-gray-700">{{ repo.description }}</td>
                <td class="py-2 px-4 border-b dark:border-gray-700">{{ repo.private ? 'Yes' : 'No' }}</td>
                <td class="py-2 px-4 border-b dark:border-gray-700">
                  <span v-if="repo.package?.user_id">
                    <span v-if="repo.package.user_id === currentUserId" class="text-green-700 font-semibold dark:text-green-400">Yes (You)</span>
                    <span v-else class="text-yellow-700 font-semibold dark:text-yellow-300">
                      Yes ({{ repo.package.owner?.name || 'Unknown' }})
                    </span>
                  </span>
                  <span v-else class="text-gray-500 dark:text-gray-400">No</span>
                </td>
                <td class="py-2 px-4 border-b dark:border-gray-700">
                  <button v-if="!repo.package?.user_id" @click="claimRepository(repo)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow dark:bg-blue-500 dark:hover:bg-blue-600">Claim</button>
                  <span v-else-if="repo.package.user_id === currentUserId" class="text-sm text-green-700 dark:text-green-400">Already claimed by you</span>
                  <span v-else class="text-sm text-yellow-700 dark:text-yellow-300">Claimed by {{ repo.package.owner?.name || 'another user' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  repositories: Array,
});

const flash = computed(() => usePage().props || {});
const currentUserId = computed(() => usePage().props.auth?.user?.id);

function claimRepository(repo) {
  router.post(`/repositories/${repo.id}/claim`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      repo.package = {
        owner_id: currentUserId.value,
        owner: usePage().props.auth?.user,
      };

      router.reload({
          only: ['repositories', 'flash'],
      })
    },
    onError: (errors) => {
      // Optionally handle error
    },
  });
}
</script>
