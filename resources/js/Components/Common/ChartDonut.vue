<script setup>
import { computed } from 'vue'
import { Chart as ChartJS, ArcElement, Legend, Tooltip } from 'chart.js'
import { Doughnut } from 'vue-chartjs'

ChartJS.register(ArcElement, Tooltip, Legend)

// objek data di atas
const props = defineProps({
    name:   { type: String, required: true }, // slug, buat data-testid
    title:  { type: String, required: true },
    labels: { type: Array, required: true },
    values: { type: Array, required: true },
    colors: { type: Array, required: true },
})

const data = computed(() => ({
    labels: props.labels,
    datasets: [{ data: props.values, backgroundColor: props.colors }],
}))

const options = { responsive: true, plugins: { legend: { position: 'bottom' } } }

const empty = computed(() => props.values.every((v) => v === 0))
</script>

<template>
    <figure :data-testid="`chart-${name}`" :aria-label="title" role="img">
        <figcaption>{{ title }}</figcaption>
        <p v-if="empty">Belum ada data</p>
        <Doughnut v-else :data="data" :options="options" />
    </figure>
</template>
