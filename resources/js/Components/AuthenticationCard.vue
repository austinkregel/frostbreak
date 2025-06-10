<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div>
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <slot />
        </div>
        <div class="mt-4" v-if="props.fortify.includes('registration')">
            <Link href="/register" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 underline">
                Don't have an account? Register
            </Link>
        </div>        

        <div class="mt-6 text-center text-gray-600 dark:text-gray-400">
            <slot name="footer">
                &copy; {{ new Date().getFullYear() }} Frostbreak. Not affiliated with WinterCMS or OctoberCMS.
            </slot>
        </div>
    </div>
</template>

<script setup>
import { Link , usePage} from '@inertiajs/vue3';

const props = usePage().props

</script>