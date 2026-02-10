<template>
    <div v-if="weatherData || loading || error" class="bg-white dark:bg-[#161615] rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6 text-[#1b1b18] dark:text-[#EDEDEC]">
            اطلاعات آب و هوا
        </h2>

        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            <p class="mt-4 text-[#706f6c] dark:text-[#A1A09A]">در حال بارگذاری اطلاعات آب و هوا...</p>
        </div>

        <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>

        <div v-else-if="weatherData && weatherData.location && weatherInfo" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Date Info (for historical data) -->
            <div v-if="isHistorical" class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] md:col-span-2 lg:col-span-3">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">تاریخ</h3>
                <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ formatDate(weatherInfo.date) }}
                </p>
            </div>

            <!-- Location Info -->
            <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">موقعیت</h3>
                <p class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">{{ weatherData.location.name }}</p>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">
                    {{ weatherData.location.region }}، {{ weatherData.location.country }}
                </p>
                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-2">
                    {{ weatherData.location.lat }}°N، {{ weatherData.location.lon }}°E
                </p>
            </div>

            <!-- Temperature -->
            <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">
                    {{ isHistorical ? 'میانگین دما' : 'دما' }}
                </h3>
                <div class="flex items-baseline">
                    <span class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                        {{ weatherInfo.temp_c }}°
                    </span>
                    <span class="text-xl text-[#706f6c] dark:text-[#A1A09A] ml-2">C</span>
                </div>
                <p v-if="isHistorical && weatherInfo.maxtemp_c && weatherInfo.mintemp_c" class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-2">
                    حداکثر {{ weatherInfo.maxtemp_c }}°C / حداقل {{ weatherInfo.mintemp_c }}°C
                </p>
                <p v-else-if="!isHistorical" class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-2">
                    احساس می‌شود {{ weatherInfo.feelslike_c }}°C
                </p>
            </div>

            <!-- Condition -->
            <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">وضعیت</h3>
                <div class="flex items-center">
                    <img
                        :src="weatherInfo.condition.icon"
                        :alt="weatherInfo.condition.text"
                        class="w-12 h-12 ml-3"
                    />
                    <p class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                        {{ weatherInfo.condition.text }}
                    </p>
                </div>
            </div>

            <!-- Wind -->
            <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">باد</h3>
                <p class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ weatherInfo.wind_kph }} کیلومتر بر ساعت
                </p>
                <p v-if="weatherInfo.wind_dir" class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-2">
                    جهت {{ weatherInfo.wind_dir }}
                </p>
            </div>

            <!-- Humidity -->
            <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">رطوبت</h3>
                <p class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ weatherInfo.humidity }}%
                </p>
            </div>

            <!-- Pressure -->
            <div v-if="weatherInfo.pressure_mb" class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">فشار</h3>
                <p class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ weatherInfo.pressure_mb }} میلی‌بار
                </p>
            </div>

            <!-- Precipitation (for historical data) -->
            <div v-if="isHistorical && weatherInfo.totalprecip_mm !== null" class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">بارش</h3>
                <p class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ weatherInfo.totalprecip_mm }} میلی‌متر
                </p>
            </div>

            <!-- Visibility -->
            <div v-if="weatherInfo.vis_km" class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">دید</h3>
                <p class="text-xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ weatherInfo.vis_km }} کیلومتر
                </p>
            </div>

            <!-- UV Index -->
            <div v-if="weatherInfo.uv" class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A]">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">شاخص UV</h3>
                <p class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ weatherInfo.uv }}
                </p>
            </div>

            <!-- Last Updated / Date -->
            <div class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] md:col-span-2 lg:col-span-3">
                <h3 class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A] mb-2">
                    {{ isHistorical ? 'تاریخ داده' : 'آخرین بروزرسانی' }}
                </h3>
                <p class="text-lg text-[#1b1b18] dark:text-[#EDEDEC]">
                    {{ formatDate(weatherInfo.last_updated) }}
                </p>
            </div>

            <!-- Hourly Weather Accordion (for historical data) -->
            <div v-if="isHistorical && hourlyData && hourlyData.length > 0" class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] md:col-span-2 lg:col-span-3">
                <h3 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">اطلاعات ساعتی</h3>
                <div class="space-y-2">
                    <div
                        v-for="(hour, index) in hourlyData"
                        :key="index"
                        class="border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-lg overflow-hidden"
                    >
                        <!-- Accordion Header -->
                        <button
                            @click="toggleHour(index)"
                            class="w-full flex items-center justify-between p-4 bg-white dark:bg-[#161615] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors text-right"
                            :aria-expanded="expandedHours.includes(index)"
                        >
                            <div class="flex items-center gap-4 flex-row-reverse">
                                <div class="flex items-center gap-2 flex-row-reverse">
                                    <img
                                        :src="hour.condition.icon"
                                        :alt="hour.condition.text"
                                        class="w-8 h-8"
                                    />
                                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        {{ hour.condition.text }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ formatHourTime(hour.time) }}
                                    </p>
                                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                        {{ hour.temp_c }}°C
                                    </p>
                                </div>
                            </div>
                            <svg
                                class="w-5 h-5 text-[#706f6c] dark:text-[#A1A09A] transition-transform flex-shrink-0"
                                :class="{ 'rotate-180': expandedHours.includes(index) }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Accordion Content -->
                        <div
                            v-show="expandedHours.includes(index)"
                            class="p-4 bg-[#FDFDFC] dark:bg-[#0a0a0a] border-t border-[#e3e3e0] dark:border-[#3E3E3A]"
                        >
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">دما</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.temp_c }}°C
                                    </p>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                        احساس می‌شود {{ hour.feelslike_c }}°C
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">باد</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.wind_kph }} کیلومتر بر ساعت
                                    </p>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mt-1">
                                        {{ hour.wind_dir }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">رطوبت</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.humidity }}%
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">فشار</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.pressure_mb }} میلی‌بار
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">دید</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.vis_km }} کیلومتر
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">شاخص UV</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.uv }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">بارش</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.precip_mm }} میلی‌متر
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] mb-1">پوشش ابر</p>
                                    <p class="text-lg font-bold text-[#1b1b18] dark:text-[#EDEDEC]">
                                        {{ hour.cloud }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { formatDate, formatHourTime } from '../utils/formatters';

const props = defineProps({
    weatherData: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: [String, null],
        default: null,
    },
});

const expandedHours = ref([]);

const isHistorical = computed(() => {
    return props.weatherData &&
        props.weatherData.forecast &&
        Array.isArray(props.weatherData.forecast.forecastday) &&
        props.weatherData.forecast.forecastday.length > 0;
});

const weatherInfo = computed(() => {
    if (!props.weatherData) return null;

    if (isHistorical.value) {
        const forecastDay = props.weatherData.forecast.forecastday[0];
        if (!forecastDay || !forecastDay.day) return null;

        return {
            temp_c: forecastDay.day.avgtemp_c,
            feelslike_c: forecastDay.day.avgtemp_c,
            condition: forecastDay.day.condition,
            wind_kph: forecastDay.day.maxwind_kph,
            wind_dir: null,
            humidity: forecastDay.day.avghumidity,
            pressure_mb: null,
            vis_km: forecastDay.day.avgvis_km,
            uv: forecastDay.day.uv,
            last_updated: forecastDay.date,
            date: forecastDay.date,
            maxtemp_c: forecastDay.day.maxtemp_c,
            mintemp_c: forecastDay.day.mintemp_c,
            totalprecip_mm: forecastDay.day.totalprecip_mm,
        };
    } else if (props.weatherData.current) {
        return {
            temp_c: props.weatherData.current.temp_c,
            feelslike_c: props.weatherData.current.feelslike_c,
            condition: props.weatherData.current.condition,
            wind_kph: props.weatherData.current.wind_kph,
            wind_dir: props.weatherData.current.wind_dir,
            humidity: props.weatherData.current.humidity,
            pressure_mb: props.weatherData.current.pressure_mb,
            vis_km: props.weatherData.current.vis_km,
            uv: props.weatherData.current.uv,
            last_updated: props.weatherData.current.last_updated,
            date: null,
            maxtemp_c: null,
            mintemp_c: null,
            totalprecip_mm: null,
        };
    }

    return null;
});

const hourlyData = computed(() => {
    if (!isHistorical.value || !props.weatherData) return null;
    const forecastDay = props.weatherData.forecast?.forecastday?.[0];
    if (!forecastDay || !Array.isArray(forecastDay.hour)) return null;
    return forecastDay.hour;
});

const toggleHour = (index) => {
    const idx = expandedHours.value.indexOf(index);
    if (idx > -1) {
        expandedHours.value.splice(idx, 1);
    } else {
        expandedHours.value.push(index);
    }
};

watch(() => props.weatherData, () => {
    expandedHours.value = [];
});
</script>
