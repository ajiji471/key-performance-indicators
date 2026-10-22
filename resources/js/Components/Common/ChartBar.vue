<script setup>
import { computed } from 'vue'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

// objek data di atas
const props = defineProps({
    name:      { type: String, required: true },
    title:     { type: String, required: true },
    platforms: { type: Array, required: true }, 
})

const data = computed(() => ({
    labels: props.platforms.map((p) => p.name),
    datasets: [
        {
            label: 'Logged in',
            data: props.platforms.map((p) => p.logged_in),
            backgroundColor: '#22c55e',
            stack: 'Stack 0',
        },
        {
            label: 'Registered',
            data: props.platforms.map((p) => p.registered),
            backgroundColor: '#eab308',
            stack: 'Stack 0',
        },
        {
            label: 'Trouble',
            data: props.platforms.map((p) => p.trouble),
            backgroundColor: '#ef4444',
            stack: 'Stack 0',
        },
    ],
}))

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' },
        tooltip: {
            callbacks: {
                label: (context) => `${context.dataset.label}: ${context.parsed.y}`,
            },
        },
    },
    scales: {
        x: { stacked: true },
        y: { 
            stacked: true,
            beginAtZero: true,
        },
    },
}

const empty = computed(() => props.platforms.every((p) => p.logged_in === 0 && p.registered === 0 && p.trouble === 0))
</script>

<template>
    <figure :data-testid="`chart-${name}`" :aria-label="title" role="img" class="flex flex-col gap-2">
        <figcaption class="font-medium text-neutral-900">{{ title }}</figcaption>
        <p v-if="empty" class="py-8 text-center text-neutral-400">Belum ada data</p>
        <div v-else class="h-64 w-full">
            <Bar :data="data" :options="options" />
        </div>
    </figure>
</template>