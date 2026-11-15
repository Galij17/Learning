<script setup lang="ts">
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { useTheme } from '@/composables/useTheme';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler);

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
            borderColor: isDark.value ? '#ABD2FA' : '#7692FF',
            backgroundColor: isDark.value ? 'rgba(171, 210, 250, 0.16)' : 'rgba(118, 146, 255, 0.18)',
            pointBackgroundColor: isDark.value ? '#ABD2FA' : '#1B2CC1',
            fill: true,
            tension: 0.3,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: isDark.value ? '#CBD5E1' : '#475569' },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: isDark.value ? 'rgba(156, 163, 175, 0.14)' : 'rgba(107, 114, 128, 0.12)',
            },
            ticks: {
                color: isDark.value ? '#CBD5E1' : '#475569',
                precision: 0,
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
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
