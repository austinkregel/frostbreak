<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 to-purple-200 dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-3xl w-full mx-auto p-8 bg-white dark:bg-gray-900 rounded-xl shadow-lg flex flex-col items-center">
      <ApplicationMark class="h-16 w-16 mb-6" />
      <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-gray-100">Privacy Policy</h1>
      <div class="prose dark:prose-invert max-w-none" v-html="renderedMarkdown"></div>
      <footer class="mt-8 text-xs text-gray-400 text-center w-full">
        &copy; {{ new Date().getFullYear() }} Frostbreak. Not affiliated with WinterCMS or OctoberCMS.<br>
        <a href="https://github.com/austinkregel/frostbreak" class="underline hover:text-blue-600">View on GitHub</a>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import { marked } from 'marked';

const props = defineProps({
  markdown: String,
});

const renderedMarkdown = computed(() => marked.parse(props.markdown || ''));
</script>
