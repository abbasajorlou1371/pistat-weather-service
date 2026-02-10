<template>
    <div v-if="selectedFarm" class="bg-white dark:bg-[#161615] rounded-lg shadow-lg p-6 mb-6">
        <label class="block text-lg font-semibold mb-4 text-[#1b1b18] dark:text-[#EDEDEC]">
            جستجوی داده‌های آب و هوا با فیلتر
        </label>

        <!-- Date Range -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    تاریخ شروع
                </label>
                <PersianDatePicker
                    :model-value="filterStartDate"
                    type="date"
                    mode="single"
                    format="YYYY-MM-DD"
                    inputFormat="YYYY-MM-DD"
                    :from="filterMinDate"
                    :to="filterMaxDate"
                    :clearable="false"
                    label="انتخاب تاریخ شروع"
                    class="w-full"
                    @update:modelValue="$emit('update:filterStartDate', $event)"
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    تاریخ پایان
                </label>
                <PersianDatePicker
                    :model-value="filterEndDate"
                    type="date"
                    mode="single"
                    format="YYYY-MM-DD"
                    inputFormat="YYYY-MM-DD"
                    :from="filterMinDate"
                    :to="filterMaxDate"
                    :clearable="false"
                    label="انتخاب تاریخ پایان"
                    class="w-full"
                    @update:modelValue="$emit('update:filterEndDate', $event)"
                />
            </div>
        </div>

        <!-- Temperature Range -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    حداقل دما (ºC)
                </label>
                <input
                    :value="filterMinTemp"
                    type="number"
                    step="0.1"
                    placeholder="حداقل دما"
                    class="w-full h-10 px-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:filterMinTemp', parseFloat($event.target.value) || 0)"
                />
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-[#1b1b18] dark:text-[#EDEDEC]">
                    حداکثر دما (ºC)
                </label>
                <input
                    :value="filterMaxTemp"
                    type="number"
                    step="0.1"
                    placeholder="حداکثر دما"
                    class="w-full h-10 px-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:filterMaxTemp', parseFloat($event.target.value) || 0)"
                />
            </div>
        </div>

        <!-- Fetch Button -->
        <button
            @click="$emit('fetch')"
            :disabled="filterLoading || !filterStartDate || !filterEndDate"
            class="w-full md:w-auto px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors duration-200"
        >
            <span v-if="filterLoading">در حال بارگذاری...</span>
            <span v-else>جستجو و دریافت داده</span>
        </button>

        <!-- Filtered Data Table -->
        <div v-if="filteredWeatherData && filteredWeatherData.length > 0" class="mt-6">
            <div class="flex justify-start gap-2 mb-3">
                <button
                    type="button"
                    @click="$emit('exportExcel')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    خروجی اکسل
                </button>
                <button
                    type="button"
                    @click="$emit('exportPdf')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    خروجی PDF
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-300 dark:border-gray-700 text-center">
                    <thead>
                        <tr class="bg-gray-600 dark:bg-gray-700">
                            <th class="border border-gray-500 dark:border-gray-600 px-4 py-3 font-bold text-white text-center">تاریخ</th>
                            <th class="border border-gray-500 dark:border-gray-600 px-4 py-3 font-bold text-white text-center">میانگین دمای هوا (ºC)</th>
                            <th class="border border-gray-500 dark:border-gray-600 px-4 py-3 font-bold text-white text-center">
                                تعداد ساعات دمایی بین {{ tempRange.min }} تا {{ tempRange.max }} درجه
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(row, index) in filteredWeatherData"
                            :key="index"
                            :class="index % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800' : 'bg-white dark:bg-[#161615]'"
                        >
                            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-center text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ formatTableDate(row.date) }}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-center text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ row.temp_c }}
                            </td>
                            <td class="border border-gray-300 dark:border-gray-700 px-4 py-2 text-center text-[#1b1b18] dark:text-[#EDEDEC]">
                                {{ row.temp_hours_count }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="filterError" class="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-200">{{ filterError }}</p>
        </div>

        <!-- No Data Message -->
        <div v-if="filteredWeatherData && filteredWeatherData.length === 0 && !filterLoading && !filterError" class="mt-4 text-center text-[#706f6c] dark:text-[#A1A09A]">
            داده‌ای یافت نشد
        </div>
    </div>
</template>

<script setup>
import PersianDatePicker from '@alireza-ab/vue3-persian-datepicker';
import { formatTableDate } from '../utils/formatters';

defineProps({
    selectedFarm: {
        type: Object,
        default: null,
    },
    filterStartDate: {
        type: [String, null],
        default: null,
    },
    filterEndDate: {
        type: [String, null],
        default: null,
    },
    filterMinDate: {
        type: String,
        required: true,
    },
    filterMaxDate: {
        type: String,
        required: true,
    },
    filterMinTemp: {
        type: Number,
        default: 0,
    },
    filterMaxTemp: {
        type: Number,
        default: 7,
    },
    filterLoading: {
        type: Boolean,
        default: false,
    },
    filteredWeatherData: {
        type: Array,
        default: null,
    },
    filterError: {
        type: [String, null],
        default: null,
    },
    tempRange: {
        type: Object,
        default: () => ({ min: 0, max: 7 }),
    },
});

defineEmits([
    'update:filterStartDate',
    'update:filterEndDate',
    'update:filterMinTemp',
    'update:filterMaxTemp',
    'fetch',
    'exportExcel',
    'exportPdf',
]);
</script>
