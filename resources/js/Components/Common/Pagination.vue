<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight } from '@lucide/vue'

defineProps({
    meta: { type: Object, required: true },
})
</script>

<template>
    <nav 
        aria-label="Pagination" 
        data-testid="pagination"
        class="flex items-center justify-center gap-1 sm:gap-2"
    >
        <!-- Tombol Prev -->
        <Link
            v-if="meta.prev_page_url"
            :href="meta.prev_page_url"
            preserve-scroll
            preserve-state
            aria-label="Previous page"
            data-testid="page-prev"
            class="inline-flex items-center justify-center rounded-md border border-border bg-card px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        >
            <ChevronLeft class="mr-1 size-4" />
            Prev
        </Link>

        <!-- Indikator Halaman Aktif -->
        <span class="inline-flex items-center justify-center rounded-md bg-foreground px-3 py-1.5 text-sm font-medium text-background">
            {{ meta.current_page }} / {{ meta.last_page }}
        </span>

        <!-- Tombol Next -->
        <Link
            v-if="meta.next_page_url"
            :href="meta.next_page_url"
            preserve-scroll
            preserve-state
            aria-label="Next page"
            data-testid="page-next"
            class="inline-flex items-center justify-center rounded-md border border-border bg-card px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
        >
            Next
            <ChevronRight class="ml-1 size-4" />
        </Link>
    </nav>
</template>