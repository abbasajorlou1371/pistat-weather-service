<template>
    <div v-if="visible" class="bg-white dark:bg-[#161615] rounded-lg shadow-lg p-6 mb-6">
        <label class="block text-lg font-semibold mb-4 text-[#1b1b18] dark:text-[#EDEDEC]">
            انتخاب تاریخ (اختیاری)
        </label>
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-4">
            برای مشاهده اطلاعات آب و هوای تاریخی، یک تاریخ انتخاب کنید. در غیر این صورت، اطلاعات آب و هوای فعلی نمایش داده می‌شود.
        </p>
        <PersianDatePicker
            :model-value="modelValue"
            type="date"
            mode="single"
            format="YYYY-MM-DD"
            inputFormat="YYYY-MM-DD"
            :from="minDate"
            :to="maxDate"
            :clearable="true"
            label="انتخاب تاریخ (حداکثر 365 روز گذشته)"
            class="w-full"
            @update:modelValue="$emit('update:modelValue', $event)"
        />
    </div>
</template>

<script setup>
import PersianDatePicker from '@alireza-ab/vue3-persian-datepicker';

defineProps({
    modelValue: {
        type: [String, null],
        default: null,
    },
    minDate: {
        type: String,
        required: true,
    },
    maxDate: {
        type: String,
        required: true,
    },
    visible: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['update:modelValue']);
</script>
