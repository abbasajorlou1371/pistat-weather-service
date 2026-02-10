<template>
    <button
        @click="$emit('click')"
        class="fixed top-4 left-4 z-50 p-3 rounded-full bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110 focus:outline-none"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        :title="isDark ? 'روشن' : 'تاریک'"
    >
        <!-- Sun Icon (Light Mode) -->
        <svg
            v-if="isDark"
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 text-yellow-500"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>
        <!-- Moon Icon (Dark Mode) -->
        <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 text-blue-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
        </svg>
    </button>
</template>

<script setup>
defineProps({
    isDark: {
        type: Boolean,
        required: true,
    },
});

defineEmits(['click']);
</script>
