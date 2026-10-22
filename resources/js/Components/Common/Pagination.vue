<script setup>
import { Link } from '@inertiajs/vue3'

// meta = paginator Laravel (prev_page_url dst)
defineProps({
    meta: { type: Object, required: true },
})
</script>

<template>
    <nav aria-label="Pagination" data-testid="pagination">
        <Link
            v-if="meta.prev_page_url"
            :href="meta.prev_page_url"
            preserve-scroll
            preserve-state
            aria-label="Halaman sebelumnya"
            data-testid="page-prev"
        >
            Prev
        </Link>

        <span>{{ meta.current_page }} / {{ meta.last_page }}</span>

        <Link
            v-if="meta.next_page_url"
            :href="meta.next_page_url"
            preserve-scroll
            preserve-state
            aria-label="Halaman berikutnya"
            data-testid="page-next"
        >
            Next
        </Link>
    </nav>
</template>
