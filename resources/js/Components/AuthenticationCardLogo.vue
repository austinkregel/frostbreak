<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationMark from './ApplicationMark.vue';
</script>

<template>
    <Link :href="'/'">
        <ApplicationMark
            class="size-24"
            xmlns="http://www.w3.org/2000/svg"
        />
        <span class="sr-only">Frostbreak</span>
    </Link>
</template>
