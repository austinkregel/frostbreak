<template>
  <AppLayout title="Dashboard">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Your Projects
      </h2>
    </template>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-end">
          <button @click="showModal = true" class="bg-blue-600 text-white px-4 py-2 rounded">New Project</button>
        </div>
        <div class="bg-white dark:bg-gray-800 dark:text-white overflow-hidden shadow-xl sm:rounded-lg">
          <div v-if="props.projects.length === 0">
            <p>You have no projects yet.</p>
          </div>
          <ul v-else class="space-y-2">
            <li v-for="project in props.projects" :key="project.id" class="p-4 rounded shadow">
              <div class="font-semibold">
                <Link :href="'/project/'+project.id" class="underline">{{ project.id }}. {{project.name}}</Link>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white dark:bg-gray-900 p-6 rounded shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4 dark:text-white">Create New Project</h3>
        <form @submit.prevent="submitForm">
          <div class="mb-4">
            <label class="block mb-1 dark:text-gray-200">Project Name</label>
            <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2 bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700" required />
          </div>
          <div class="mb-4">
            <label class="block mb-1 dark:text-gray-200">Description</label>
            <textarea v-model="form.description" class="w-full border rounded px-3 py-2 bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700"></textarea>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded">Cancel</button>
            <button type="submit" :disabled="processing" class="px-4 py-2 bg-blue-600 text-white rounded">Create</button>
          </div>
          <div v-if="error" class="text-red-600 mt-2">{{ error }}</div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  projects: Array
});

const showModal = ref(false);
const form = ref({
  name: '',
  description: ''
});
const processing = ref(false);
const error = ref('');

async function submitForm() {
  processing.value = true;
  error.value = '';
  router.post('/projects', form.value, {
    onSuccess: () => {
      showModal.value = false;
      form.value.name = '';
      form.value.description = '';
      // Optionally, you can reload or refetch projects here
      window.location.reload();
    },
    onError: (errors) => {
      error.value = errors.name || errors.description || 'Failed to create project.';
    },
    onFinish: () => {
      processing.value = false;
    }
  });
}
</script>

<style scoped>
/* Add any custom styles here */
</style>
