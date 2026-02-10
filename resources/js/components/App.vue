<template>
    <div class="weather-app-container min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] p-8">
        <div class="max-w-6xl mx-auto relative">
            <ThemeToggle :is-dark="isDark" @click="handleThemeToggle" />

            <h1 class="text-4xl font-bold mb-8 text-[#1b1b18] dark:text-[#EDEDEC]">
                سرویس آب و هوا
            </h1>

            <FarmSelector
                ref="farmSelectorRef"
                :farms="farms"
                :selected-farm="selectedFarm"
                @update:selected-farm="onFarmChange"
            />

            <DateSelector
                v-model="selectedDate"
                :min-date="minDate"
                :max-date="maxDate"
                :visible="!!selectedFarm"
                @update:model-value="onDateChange"
            />

            <WeatherFilterSection
                :selected-farm="selectedFarm"
                v-model:filter-start-date="filterStartDate"
                v-model:filter-end-date="filterEndDate"
                v-model:filter-min-temp="filterMinTemp"
                v-model:filter-max-temp="filterMaxTemp"
                :filter-min-date="filterMinDate"
                :filter-max-date="filterMaxDate"
                :filter-loading="filterLoading"
                :filtered-weather-data="filteredWeatherData"
                :filter-error="filterError"
                :temp-range="tempRange"
                @fetch="fetchFilteredWeather"
                @export-excel="exportTableToExcel"
                @export-pdf="exportTableToPdf"
            />

            <WeatherDisplay
                v-if="weatherData || loading || error"
                :weather-data="weatherData"
                :loading="loading"
                :error="error"
            />

            <EmptyState v-else />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { toJalaali } from 'jalaali-js';
import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import { autoTable } from 'jspdf-autotable';

import ThemeToggle from './ThemeToggle.vue';
import FarmSelector from './FarmSelector.vue';
import DateSelector from './DateSelector.vue';
import WeatherFilterSection from './WeatherFilterSection.vue';
import WeatherDisplay from './WeatherDisplay.vue';
import EmptyState from './EmptyState.vue';

import { useTheme } from '../composables/useTheme';
import { formatTableDate } from '../utils/formatters';

const { isDark, toggleTheme } = useTheme();

const farms = ref([]);
const selectedFarm = ref(null);
const weatherData = ref(null);
const loading = ref(false);
const error = ref(null);
const selectedDate = ref(null);
const farmSelectorRef = ref(null);

// Filtered weather data
const filterStartDate = ref(null);
const filterEndDate = ref(null);
const filterMinTemp = ref(0);
const filterMaxTemp = ref(7);
const filteredWeatherData = ref(null);
const filterLoading = ref(false);
const filterError = ref(null);
const tempRange = ref({ min: 0, max: 7 });

// Calculate min and max dates (past 365 days)
const today = new Date();
const maxDate = computed(() => {
    const jToday = toJalaali(today.getFullYear(), today.getMonth() + 1, today.getDate());
    return `${jToday.jy}/${String(jToday.jm).padStart(2, '0')}/${String(jToday.jd).padStart(2, '0')}`;
});

const minDate = computed(() => {
    const oneYearAgo = new Date(today);
    oneYearAgo.setDate(oneYearAgo.getDate() - 365);
    const jMin = toJalaali(oneYearAgo.getFullYear(), oneYearAgo.getMonth() + 1, oneYearAgo.getDate());
    return `${jMin.jy}/${String(jMin.jm).padStart(2, '0')}/${String(jMin.jd).padStart(2, '0')}`;
});

const filterMaxDate = computed(() => {
    const jToday = toJalaali(today.getFullYear(), today.getMonth() + 1, today.getDate());
    return `${jToday.jy}/${String(jToday.jm).padStart(2, '0')}/${String(jToday.jd).padStart(2, '0')}`;
});

const filterMinDate = computed(() => {
    const yearAgo = new Date(today);
    yearAgo.setDate(yearAgo.getDate() - 365);
    const jMin = toJalaali(yearAgo.getFullYear(), yearAgo.getMonth() + 1, yearAgo.getDate());
    return `${jMin.jy}/${String(jMin.jm).padStart(2, '0')}/${String(jMin.jd).padStart(2, '0')}`;
});

const handleThemeToggle = () => {
    toggleTheme(() => {
        farmSelectorRef.value?.refreshSelect2Theme?.();
    });
};

const tableExportHeaders = computed(() => [
    'تاریخ',
    'میانگین دمای هوا (ºC)',
    `تعداد ساعات دمایی بین ${tempRange.value.min} تا ${tempRange.value.max} درجه`,
]);

const loadFarms = async () => {
    try {
        const response = await axios.get('/api/farms');
        farms.value = response.data;
    } catch (err) {
        console.error('Failed to load farms:', err);
        error.value = err.response?.data?.error || 'خطا در بارگذاری مزارع';
    }
};

const onFarmChange = (farm) => {
    selectedFarm.value = farm;
    if (farm && farm.lat && farm.lng) {
        fetchWeather(farm.lat, farm.lng, selectedDate.value);
    } else {
        weatherData.value = null;
        error.value = null;
        selectedDate.value = null;
    }
};

const onDateChange = (date) => {
    selectedDate.value = date;
    if (!selectedFarm.value?.lat || !selectedFarm.value?.lng) return;
    if (!date) {
        fetchWeather(selectedFarm.value.lat, selectedFarm.value.lng, null);
        return;
    }
    fetchWeather(selectedFarm.value.lat, selectedFarm.value.lng, date);
};

const fetchWeather = async (lat, lng, date = null) => {
    if (!lat || !lng) {
        error.value = 'مختصات مزرعه نامعتبر است';
        return;
    }

    loading.value = true;
    error.value = null;
    weatherData.value = null;

    try {
        const params = { lat, lng };
        if (date) params.date = date;
        const response = await axios.get('/api/weather', { params });
        weatherData.value = response.data;
    } catch (err) {
        console.error('Failed to fetch weather:', err);
        error.value = err.response?.data?.error || err.response?.data?.message || 'خطا در دریافت اطلاعات آب و هوا';
    } finally {
        loading.value = false;
    }
};

function exportTableToExcel() {
    if (!filteredWeatherData.value || filteredWeatherData.value.length === 0) return;
    const headers = tableExportHeaders.value;
    const rows = filteredWeatherData.value.map((row) => [
        formatTableDate(row.date),
        row.temp_c,
        row.temp_hours_count,
    ]);
    const data = [headers, ...rows];
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Weather');
    const fileName = `weather-data-${new Date().toISOString().slice(0, 10)}.xlsx`;
    XLSX.writeFile(wb, fileName);
}

async function exportTableToPdf() {
    if (!filteredWeatherData.value || filteredWeatherData.value.length === 0) return;

    const doc = new jsPDF({ orientation: 'landscape' });
    let fontLoaded = false;

    try {
        const fontUrl = 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/fonts/ttf/Vazirmatn-Regular.ttf';
        const fontResponse = await fetch(fontUrl);
        if (!fontResponse.ok) throw new Error(`Font fetch failed: ${fontResponse.status}`);

        const fontArrayBuffer = await fontResponse.arrayBuffer();
        const fontBytes = new Uint8Array(fontArrayBuffer);
        const chunks = [];
        const chunkSize = 8192;
        for (let i = 0; i < fontBytes.length; i += chunkSize) {
            const chunk = fontBytes.slice(i, i + chunkSize);
            chunks.push(String.fromCharCode.apply(null, Array.from(chunk)));
        }
        const fontBase64 = btoa(chunks.join(''));
        doc.addFileToVFS('Vazirmatn-Regular.ttf', fontBase64);
        doc.addFont('Vazirmatn-Regular.ttf', 'Vazirmatn', 'normal');
        doc.setFont('Vazirmatn');
        fontLoaded = true;
    } catch (err) {
        console.warn('Failed to load Persian font, using default font:', err);
    }

    const headers = [tableExportHeaders.value];
    const rows = filteredWeatherData.value.map((row) => [
        formatTableDate(row.date),
        String(row.temp_c),
        String(row.temp_hours_count),
    ]);
    const fontName = fontLoaded ? 'Vazirmatn' : 'helvetica';

    autoTable(doc, {
        head: headers,
        body: rows,
        styles: { font: fontName, fontSize: 9, fontStyle: 'normal' },
        headStyles: { fillColor: [75, 85, 99], font: fontName, fontStyle: 'normal' },
    });

    const fileName = `weather-data-${new Date().toISOString().slice(0, 10)}.pdf`;
    doc.save(fileName);
}

const fetchFilteredWeather = async () => {
    if (!selectedFarm.value || !selectedFarm.value.lat || !selectedFarm.value.lng) {
        filterError.value = 'لطفاً یک مزرعه انتخاب کنید';
        return;
    }
    if (!filterStartDate.value || !filterEndDate.value) {
        filterError.value = 'لطفاً تاریخ شروع و پایان را انتخاب کنید';
        return;
    }

    filterLoading.value = true;
    filterError.value = null;
    filteredWeatherData.value = null;

    try {
        const params = {
            lat: selectedFarm.value.lat,
            lng: selectedFarm.value.lng,
            start_date: filterStartDate.value,
            end_date: filterEndDate.value,
            min_temp: filterMinTemp.value,
            max_temp: filterMaxTemp.value,
        };
        const response = await axios.get('/api/weather/filtered', { params });
        filteredWeatherData.value = response.data.data || response.data;

        if (response.data.temp_range) {
            tempRange.value = response.data.temp_range;
        } else {
            tempRange.value = {
                min: filterMinTemp.value ?? 0,
                max: filterMaxTemp.value ?? 7,
            };
        }
    } catch (err) {
        console.error('Failed to fetch filtered weather:', err);
        filterError.value = err.response?.data?.error || err.response?.data?.message || 'خطا در دریافت اطلاعات آب و هوا';
    } finally {
        filterLoading.value = false;
    }
};

onMounted(async () => {
    const now = new Date();
    const sevenDaysAgo = new Date(now);
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    filterEndDate.value = now.toISOString().split('T')[0];
    filterStartDate.value = sevenDaysAgo.toISOString().split('T')[0];

    await loadFarms();
});
</script>

<style scoped>
/* Select2 styling adjustments - Light mode (default) */
:deep(.select2-container--default .select2-selection--single) {
    height: 42px;
    border: 1px solid #e3e3e0;
    border-radius: 0.5rem;
    direction: rtl;
    background-color: white;
}

html:not(.dark) :deep(.select2-container--default .select2-selection--single) {
    background-color: white !important;
    border-color: #e3e3e0 !important;
}

html:not(.dark) :deep(.select2-container--default .select2-selection--single:hover) {
    border-color: #1915014a !important;
}

html:not(.dark) :deep(.select2-container--default .select2-selection--single .select2-selection__rendered) {
    color: #1b1b18 !important;
}

html:not(.dark) :deep(.select2-container--default .select2-selection--single .select2-selection__placeholder) {
    color: #706f6c !important;
}

html:not(.dark) :deep(.select2-container--default .select2-selection--single .select2-selection__arrow b) {
    border-color: #1b1b18 transparent transparent transparent !important;
}

html:not(.dark) :deep(.select2-dropdown) {
    background-color: white !important;
    border-color: #e3e3e0 !important;
}

html:not(.dark) :deep(.select2-search--dropdown .select2-search__field) {
    background-color: white !important;
    border-color: #e3e3e0 !important;
    color: #1b1b18 !important;
}

html:not(.dark) :deep(.select2-results__option) {
    background-color: white !important;
    color: #1b1b18 !important;
}

html:not(.dark) :deep(.select2-results__option:hover),
html:not(.dark) :deep(.select2-results__option--highlighted) {
    background-color: #f5f5f5 !important;
    color: #1b1b18 !important;
}

html:not(.dark) :deep(.select2-results__option[aria-selected="true"]),
html:not(.dark) :deep(.select2-results__option--selected) {
    background-color: #f0f0f0 !important;
    color: #1b1b18 !important;
}

:deep(.select2-dropdown) {
    direction: rtl;
    text-align: right;
}

:deep(.select2-results__option) {
    text-align: right;
    padding-right: 12px;
    padding-left: 20px;
    font-family: 'Vazirmatn', sans-serif;
}

:deep(.select2-container--default .select2-selection--single .select2-selection__rendered) {
    line-height: 42px;
    padding-right: 12px;
    padding-left: 20px;
    color: #1b1b18;
    text-align: right;
    font-family: 'Vazirmatn', sans-serif;
}

:deep(.select2-container--default .select2-selection--single .select2-selection__arrow) {
    height: 40px;
    right: auto;
    left: 1px;
}

:deep(.select2-container--default.select2-container--focus .select2-selection--single) {
    border-color: #3b82f6;
    outline: 0;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

:deep(.select2-container--default.select2-container--open .select2-selection--single) {
    border-color: #3b82f6;
}

:deep(.select2-container--default .select2-selection--single:hover) {
    border-color: #1915014a;
}

/* Dark mode support for Select2 */
html.dark :deep(.select2-container--default .select2-selection--single) {
    background-color: #161615 !important;
    border-color: #3E3E3A !important;
}

html.dark :deep(.select2-container--default .select2-selection--single:hover) {
    border-color: #62605b !important;
}

html.dark :deep(.select2-container--default.select2-container--focus .select2-selection--single) {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

html.dark :deep(.select2-container--default.select2-container--open .select2-selection--single) {
    border-color: #3b82f6 !important;
}

html.dark :deep(.select2-container--default .select2-selection--single .select2-selection__rendered) {
    color: #EDEDEC !important;
}

html.dark :deep(.select2-container--default .select2-selection--single .select2-selection__placeholder) {
    color: #A1A09A !important;
}

html.dark :deep(.select2-container--default .select2-selection--single .select2-selection__arrow b) {
    border-color: #EDEDEC transparent transparent transparent !important;
}

html.dark :deep(.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b) {
    border-color: transparent transparent #EDEDEC transparent !important;
}

html.dark :deep(.select2-dropdown) {
    background-color: #161615 !important;
    border-color: #3E3E3A !important;
    text-align: right;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3) !important;
}

html.dark :deep(.select2-search--dropdown) {
    background-color: #161615 !important;
}

html.dark :deep(.select2-search--dropdown .select2-search__field) {
    background-color: #0a0a0a !important;
    border-color: #3E3E3A !important;
    color: #EDEDEC !important;
    padding-right: 12px;
    padding-left: 20px;
    font-family: 'Vazirmatn', sans-serif;
    border-radius: 0.5rem;
}

html.dark :deep(.select2-search--dropdown .select2-search__field:focus) {
    border-color: #3b82f6 !important;
    outline: 0;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

html.dark :deep(.select2-search--dropdown .select2-search__field::placeholder) {
    color: #A1A09A !important;
}

html.dark :deep(.select2-results__option) {
    background-color: #161615 !important;
    color: #EDEDEC !important;
    text-align: right;
    padding-right: 12px;
    padding-left: 20px;
    font-family: 'Vazirmatn', sans-serif;
}

html.dark :deep(.select2-results__option:hover) {
    background-color: #3E3E3A !important;
    color: #EDEDEC !important;
}

html.dark :deep(.select2-results__option--highlighted) {
    background-color: #3E3E3A !important;
    color: #EDEDEC !important;
}

html.dark :deep(.select2-results__option[aria-selected="true"]) {
    background-color: #3E3E3A !important;
    color: #EDEDEC !important;
}

html.dark :deep(.select2-results__option--selected) {
    background-color: #3E3E3A !important;
    color: #EDEDEC !important;
}

html.dark :deep(.select2-results__option--loading) {
    background-color: #161615 !important;
    color: #A1A09A !important;
}

html.dark :deep(.select2-results__message) {
    background-color: #161615 !important;
    color: #A1A09A !important;
    font-family: 'Vazirmatn', sans-serif;
}

html.dark :deep(.select2-results__group) {
    background-color: #161615 !important;
    color: #EDEDEC !important;
    font-weight: 600;
    font-family: 'Vazirmatn', sans-serif;
    padding-right: 12px;
    padding-left: 20px;
}

html.dark :deep(.select2-container--default .select2-selection--single.select2-selection--single-disabled) {
    background-color: #0a0a0a !important;
    border-color: #3E3E3A !important;
    opacity: 0.6;
}

html.dark :deep(.select2-container--default .select2-selection--single.select2-selection--single-disabled .select2-selection__rendered) {
    color: #706f6c !important;
}

html.dark :deep(.select2-container--default .select2-selection__clear) {
    color: #A1A09A !important;
    margin-left: 5px;
}

html.dark :deep(.select2-container--default .select2-selection__clear:hover) {
    color: #EDEDEC !important;
}

html.dark :deep(.select2-container--default .select2-selection--single .select2-selection__clear) {
    font-size: 1.2em;
    line-height: 1;
}

html.dark :deep(.select2-container) {
    color: #EDEDEC !important;
}

/* Persian Date Picker Styling */
:deep(.persian-date-picker) {
    width: 100%;
}

:deep(.persian-date-picker input) {
    width: 100%;
    height: 42px;
    padding: 0.5rem 1rem;
    border: 1px solid #e3e3e0;
    border-radius: 0.5rem;
    background-color: white;
    color: #1b1b18;
    font-family: 'Vazirmatn', sans-serif;
    direction: rtl;
    text-align: right;
    font-size: 1rem;
}

html:not(.dark) :deep(.persian-date-picker input) {
    background-color: white !important;
    border-color: #e3e3e0 !important;
    color: #1b1b18 !important;
}

html:not(.dark) :deep(.persian-date-picker input:hover) {
    border-color: #1915014a !important;
}

html:not(.dark) :deep(.persian-date-picker input:focus) {
    border-color: #3b82f6 !important;
    outline: 0;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

html.dark :deep(.persian-date-picker input) {
    background-color: #161615 !important;
    border-color: #3E3E3A !important;
    color: #EDEDEC !important;
}

html.dark :deep(.persian-date-picker input:hover) {
    border-color: #62605b !important;
}

html.dark :deep(.persian-date-picker input:focus) {
    border-color: #3b82f6 !important;
    outline: 0;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

html.dark :deep(.persian-date-picker input::placeholder) {
    color: #A1A09A !important;
}

:deep(.persian-date-picker-calendar) {
    direction: rtl;
    font-family: 'Vazirmatn', sans-serif;
}

html:not(.dark) :deep(.persian-date-picker-calendar) {
    background-color: white !important;
    border-color: #e3e3e0 !important;
    color: #1b1b18 !important;
}

html.dark :deep(.persian-date-picker-calendar) {
    background-color: #161615 !important;
    border-color: #3E3E3A !important;
    color: #EDEDEC !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3) !important;
}
</style>
