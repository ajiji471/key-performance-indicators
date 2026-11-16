<script setup>
import { computed } from 'vue'
import { Chart as ChartJS, CategoryScale, LinearScale, LineElement, PointElement, Filler, Tooltip } from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, LineElement, PointElement, Filler, Tooltip)

// objek data di atas
const props = defineProps({
    name:      { type: String, required: true },
    title:     { type: String, required: true },
    platforms: { type: Array, required: true },
})

const series = [
    { key: 'logged_in',  label: 'Logged in',  color: '#22c55e' },
    { key: 'registered', label: 'Registered', color: '#eab308' },
    { key: 'recovered',  label: 'Recovered',  color: '#3b82f6' },
    { key: 'trouble',    label: 'Trouble',    color: '#ef4444' },
]

const font = 'Geist, ui-sans-serif, system-ui, sans-serif'

const rgba = (hex, alpha) => {
    const value = hex.replace('#', '')
    return `rgba(${parseInt(value.slice(0, 2), 16)}, ${parseInt(value.slice(2, 4), 16)}, ${parseInt(value.slice(4, 6), 16)}, ${alpha})`
}

// gradient area ala shadcn: pekat di atas, memudar ke bawah
const areaFill = (color) => (context) => {
    const { ctx, chartArea } = context.chart

    if (!chartArea) return rgba(color, 0.2)

    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
    gradient.addColorStop(0, rgba(color, 0.35))
    gradient.addColorStop(0.65, rgba(color, 0.1))
    gradient.addColorStop(1, rgba(color, 0.01))

    return gradient
}

const data = computed(() => ({
    labels: props.platforms.map((p) => p.name),
    datasets: series.map((s, index) => {
        const values = props.platforms.map((p) => p[s.key] ?? 0)
        const hasData = values.some((value) => value > 0)

        return {
            label: s.label,
            data: values,
            borderColor: hasData ? s.color : 'transparent',
            backgroundColor: hasData ? areaFill(s.color) : 'transparent',
            fill: index === 0 ? 'origin' : '-1',
            stack: 'status',
            tension: 0.4,
            borderWidth: hasData ? 2 : 0,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointHoverBorderWidth: 2,
            pointHoverBackgroundColor: '#0a0a0a',
            pointHoverBorderColor: s.color,
            pointStyle: 'circle',
        }
    }),
}))

const options = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#141416',
            borderColor: '#2a2a2e',
            borderWidth: 1,
            titleColor: '#a1a1aa',
            bodyColor: '#f4f4f5',
            padding: 10,
            cornerRadius: 8,
            boxPadding: 4,
            usePointStyle: true,
            titleFont: { family: font, size: 11, weight: '500' },
            bodyFont: { family: font, size: 12 },
            callbacks: {
                label: (context) => ` ${context.dataset.label}: ${context.parsed.y}`,
            },
        },
    },
    scales: {
        x: {
            stacked: true,
            grid: { display: false },
            border: { display: false },
            ticks: { color: '#a1a1aa', font: { family: font, size: 11 }, maxRotation: 0 },
        },
        y: {
            stacked: true,
            beginAtZero: true,
            grid: { color: 'rgba(255, 255, 255, 0.07)' },
            border: { display: false },
            ticks: {
                color: '#71717a',
                font: { family: font, size: 11 },
                padding: 8,
                maxTicksLimit: 5,
                precision: 0,
            },
        },
    },
}

const empty = computed(() => props.platforms.every((p) => p.logged_in === 0 && p.registered === 0 && p.recovered === 0 && p.trouble === 0))
</script>

<template>
    <figure :data-testid="`chart-${name}`" :aria-label="title" role="img" class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <figcaption class="font-medium text-foreground">{{ title }}</figcaption>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span v-for="s in series" :key="s.key" class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span class="size-2 rounded-full" :style="{ backgroundColor: s.color }" />
                    {{ s.label }}
                </span>
            </div>
        </div>

        <p v-if="empty" class="py-8 text-center text-muted-foreground">No data yet</p>
        <div v-else class="h-64 w-full">
            <Line :data="data" :options="options" />
        </div>
    </figure>
</template>
