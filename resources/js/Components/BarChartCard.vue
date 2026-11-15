<script setup lang="ts">
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';
import { useTheme } from '@/composables/useTheme';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

interface Props {
    title: string;
    subtitle?: string;
    labels: string[];
    datasetLabel: string;
    values: number[];
}

const props = defineProps<Props>();
const { isDark } = useTheme();

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.datasetLabel,
            data: props.values,
            backgroundColor: isDark.value ? '#7692FF' : '#1B2CC1',
            borderRadius: 6,
            maxBarThickness: 42,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1E293B',
            titleColor: '#F8FAFC',
            bodyColor: '#ABD2FA',
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: {
                color: isDark.value ? '#CBD5E1' : '#475569',
                font: { family: 'Plus Jakarta Sans', size: 12 },
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: isDark.value ? 'rgba(156, 163, 175, 0.14)' : 'rgba(107, 114, 128, 0.12)',
            },
            ticks: {
                color: isDark.value ? '#CBD5E1' : '#475569',
                precision: 0,
                font: { family: 'Plus Jakarta Sans', size: 12 },
            },
        },
    },
}));
</script>

<template>
    <div class="rounded-[14px] border border-[#E2E8F0] bg-white p-5 dark:border-[#3E4F69] dark:bg-[#283548]">
        <div class="mb-4">
            <h3 class="text-base font-bold text-[#1E293B] dark:text-[#F8FAFC]">{{ title }}</h3>
            <p v-if="subtitle" class="mt-0.5 text-xs text-[#475569] dark:text-[#CBD5E1]">{{ subtitle }}</p>
        </div>
        <div class="h-60 w-full">
            <Bar :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
