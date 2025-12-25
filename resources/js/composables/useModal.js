export function useModal() {

    const closeModal = (modalId) => {
        const modalEl = document.getElementById(modalId);

        if (!modalEl) {
            console.warn(`Modal with id "${modalId}" not found`);
            return;
        }

        const modalInstance = bootstrap.Modal.getInstance(modalEl)
            || new bootstrap.Modal(modalEl);

        modalInstance.hide();
    };

    return {
        closeModal
    };
}
