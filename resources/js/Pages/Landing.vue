<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 to-purple-200 dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-xl w-full mx-auto p-8 bg-white dark:bg-gray-900 rounded-xl shadow-lg flex flex-col items-center">
      <ApplicationMark class="h-16 w-16 mb-6" />
        

      <h1 class="text-4xl font-extrabold text-blue-700 dark:text-blue-300 mb-2 text-center">{{ title }}</h1>
      <h2 class="text-xl text-gray-700 dark:text-gray-200 mb-4 text-center">{{ subtitle }}</h2>
      <div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded mb-6 text-center font-semibold">
        {{ notice }}
      </div>
      <p class="text-gray-600 dark:text-gray-400 text-center mb-6">
        Welcome to <span class="font-bold">Frostbreak</span>, the open marketplace for <span class="font-bold">WinterCMS</span> plugins and themes.
        <span class="">Browse, search, and manage your WinterCMS project dependencies with ease.</span>
      </p>
      <a href="/search" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow transition">Browse Marketplace</a>
      <footer class="mt-8 text-xs text-gray-400 text-center w-full">
        &copy; {{ new Date().getFullYear() }} Frostbreak. Not affiliated with WinterCMS or OctoberCMS.<br>
        <a href="https://github.com/austinkregel/frostbreak" class="underline hover:text-blue-600">View on GitHub</a>
      </footer>
    </div>
  </div>
</template>

<script setup>
import ApplicationMark from '@/Components/ApplicationMark.vue';

const props = defineProps({
  title: String,
  subtitle: String,
  notice: String
});
</script>
