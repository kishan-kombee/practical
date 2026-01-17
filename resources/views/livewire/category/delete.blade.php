<div>
    <!-- Delete Confirmation Modal -->
    @if($showModal)
        <x-confirmation-modal 
            modalName="delete-user-confirmation"
            :title="$isBulkDelete ? 'Bulk Delete' : 'Delete Record'"
            :message="$message"
            :confirmText="$isBulkDelete ? 'Delete All' : 'Delete Record'"
            cancelText="Cancel"
            confirmEvent="confirmDelete"
            variant="danger"
        />
    @endif
</div>