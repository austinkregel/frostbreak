<template>
  <AppLayout title="Projects">
    <div v-if="props.projects.links">
        <div class="max-w-7xl w-full mx-auto py-8 px-8">
        <div class="flex flex-col items-center mb-8">
          <h1 class="text-3xl font-extrabold mb-2 text-gray-900 dark:text-gray-100">📁 Projects</h1>
          <p class="text-gray-500 dark:text-gray-400">Manage and search your projects.</p>
        </div>
        <!-- Search Bar -->
        <form @submit.prevent="searchProjects" class="mb-8 flex flex-col md:flex-row gap-4 items-center justify-center">
          <input
            v-model="search"
            type="text"
            placeholder="Search your projects..."
            class="border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg px-4 py-2 flex-1 focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm transition"
          />
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 transition text-white px-6 py-2 rounded-lg font-semibold shadow">Search</button>
          <button v-if="search" type="button" @click="clearSearch" class="px-3 py-2 rounded bg-gray-300 dark:bg-gray-700 dark:text-white">Clear</button>
          <button @click="showModal = true" type="button" class="bg-green-600 hover:bg-green-700 transition text-white px-6 py-2 rounded-lg font-semibold shadow">New Project</button>
        </form>
        <div class="bg-white dark:bg-gray-800 dark:text-white overflow-hidden shadow-xl sm:rounded-lg">
          <div v-if="props.projects.data.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">No projects found.</div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
            <div v-for="project in props.projects.data" :key="project.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-6 bg-white dark:bg-gray-800 shadow hover:shadow-lg transition flex flex-col justify-between">
              <div class="flex items-center justify-between gap-2 mb-2">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Project</span>
                <span class="text-xs bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-300 px-2 py-1 rounded">{{ project.created_at ? new Date(project.created_at).toLocaleDateString() : '' }}</span>
              </div>
              <h2 class="font-bold text-xl mb-1 text-gray-900 dark:text-gray-100">{{ project.name }}</h2>
              <p class="text-gray-700 dark:text-gray-300 min-h-[48px]">{{ project.description }}</p>
              <div class="flex justify-end mt-4">
                <Link :href="'/project/' + project.id" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">View Project</Link>
              </div>
            </div>
          </div>
          <!-- Pagination Controls -->
          <div v-if="props.projects.links.length > 3" class="flex justify-center items-center gap-2 py-4">
            <button
              v-for="link in props.projects.links"
              :key="link.label"
              v-html="link.label"
              :disabled="!link.url"
              @click="goToPage(link.url)"
              class="px-3 py-1 rounded border text-sm"
              :class="[
                link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600',
                !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-100 dark:hover:bg-blue-800'
              ]"
            />
          </div>
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
    <!-- Add to Project Modal -->
    <div v-if="showAddToProjectModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white dark:bg-gray-900 p-6 rounded shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4 dark:text-white">Add to Project</h3>
        <div class="mb-4">
          <p class="text-gray-700 dark:text-gray-300">Select a project to add this {{ selectedType }} to:</p>
        </div>
        <div class="grid grid-cols-1 gap-4">
          <div v-for="project in props.projects.data" :key="project.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800 shadow hover:shadow-lg transition flex items-center justify-between">
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ project.name }}</h4>
              <p class="text-gray-500 dark:text-gray-400 text-sm">{{ project.description }}</p>
            </div>
            <button @click="addToProject(project.id)" class="bg-blue-600 hover:bg-blue-700 transition text-white px-4 py-2 rounded-lg font-semibold shadow">
              Add
            </button>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="closeAddToProjectModal" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded">Cancel</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  projects: Object
});

const showModal = ref(false);
const form = ref({
  name: '',
  description: ''
});
const processing = ref(false);
const error = ref('');
const search = ref(props.filters?.search || '');

// Add modal state and selected plugin/theme state
const showAddToProjectModal = ref(false);
const selectedItem = ref(null);
const selectedType = ref(null);

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

function goToPage(url) {
  if (url) {
    window.location.href = url;
  }
}

function searchProjects() {
  router.get(
    '/projects',
    { query: search.value },
    { preserveState: true, replace: true }
  );
}

function clearSearch() {
  search.value = '';
  router.get(
    '/projects',
    {},
    { preserveState: true, replace: true }
  );
}

function openAddToProjectModal(item, type) {
  selectedItem.value = item;
  selectedType.value = type;
  showAddToProjectModal.value = true;
}
function closeAddToProjectModal() {
  showAddToProjectModal.value = false;
  selectedItem.value = null;
  selectedType.value = null;
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
/* Add any custom styles here */
</style>
