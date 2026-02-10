<template>
    <div class="bg-white dark:bg-[#161615] rounded-lg shadow-lg p-6 mb-6">
        <label class="block text-lg font-semibold mb-4 text-[#1b1b18] dark:text-[#EDEDEC]">
            انتخاب مزرعه
        </label>
        <select
            id="farm-select"
            class="w-full"
            style="width: 100%;"
        >
            <option value="">{{ farms.length > 0 ? 'یک مزرعه انتخاب کنید...' : 'در حال بارگذاری مزارع...' }}</option>
        </select>

        <!-- Selected Farm Info -->
        <div v-if="selectedFarm" class="mt-4 p-4 bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A]">
            <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">مزرعه انتخاب شده</h3>
            <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">
                {{ selectedFarm.name }}
            </p>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                <span class="font-medium">مرکز:</span>
                {{ selectedFarm.lat }}, {{ selectedFarm.lng }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { onUnmounted, nextTick, watch } from 'vue';

const props = defineProps({
    farms: {
        type: Array,
        required: true,
    },
    selectedFarm: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:selectedFarm']);

let select2Instance = null;

const loadScript = (src) => {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
};

const refreshSelect2Theme = () => {
    if (select2Instance && typeof window.$ !== 'undefined') {
        const $select = window.$('#farm-select');
        if ($select.length > 0) {
            const isOpen = $select.data('select2')?.isOpen();
            if (isOpen) {
                $select.select2('close');
                setTimeout(() => {
                    $select.select2('open');
                }, 100);
            }
        }
    }
};

const initializeSelect2 = async () => {
    if (typeof window.$ === 'undefined') {
        await loadScript('https://code.jquery.com/jquery-3.6.0.min.js');
    }

    if (typeof window.$ === 'undefined' || !window.$.fn.select2) {
        await loadScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');
    }

    await nextTick();

    const $select = window.$('#farm-select');

    // Destroy existing instance before re-initializing
    if (select2Instance) {
        try {
            $select.select2('destroy');
        } catch (e) {
            // Ignore if not initialized
        }
        select2Instance = null;
    }

    if ($select.length === 0) {
        console.error('Select element not found');
        return;
    }

    $select.empty();
    $select.append('<option value="">یک مزرعه انتخاب کنید...</option>');

    props.farms.forEach(farm => {
        if (farm.lat && farm.lng) {
            $select.append(`<option value="${farm.id}" data-lat="${farm.lat}" data-lng="${farm.lng}">${farm.name}</option>`);
        }
    });

    select2Instance = $select.select2({
        placeholder: 'یک مزرعه انتخاب کنید...',
        allowClear: true,
        width: '100%',
        dir: 'rtl',
    });

    $select.on('change', function () {
        const selectedId = window.$(this).val();
        if (selectedId) {
            const farm = props.farms.find(f => f.id == selectedId);
            if (farm && farm.lat && farm.lng) {
                emit('update:selectedFarm', farm);
            }
        } else {
            emit('update:selectedFarm', null);
        }
    });

    // Set first farm as default
    if (props.farms.length > 0) {
        const firstFarm = props.farms.find(f => f.lat && f.lng);
        if (firstFarm) {
            $select.val(firstFarm.id).trigger('change');
        }
    }
};

watch(() => props.farms, async (newFarms) => {
    if (newFarms && newFarms.length > 0) {
        await nextTick();
        await initializeSelect2();
    }
}, { immediate: true });

onUnmounted(() => {
    if (select2Instance && typeof window.$ !== 'undefined') {
        try {
            select2Instance.destroy();
        } catch (e) {
            console.error('Error destroying Select2:', e);
        }
    }
});

defineExpose({
    refreshSelect2Theme,
});
</script>

<style scoped>
/* Select2 styling - see App.vue or global styles for full definitions */
</style>
