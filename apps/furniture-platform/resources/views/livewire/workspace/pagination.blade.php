<div class="records-footer customer-pagination">
    <span>{{ $records->total() }} {{ $label }} · page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
    <div aria-label="Pagination">
        <button type="button" wire:click="previousPage" wire:loading.attr="disabled" @disabled($records->onFirstPage())>Previous</button>
        <button type="button" wire:click="nextPage" wire:loading.attr="disabled" @disabled(!$records->hasMorePages())>Next</button>
    </div>
</div>
