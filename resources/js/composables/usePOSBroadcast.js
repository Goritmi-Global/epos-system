// resources/js/composables/usePOSBroadcast.js

export function usePOSBroadcast(terminalId) {
    // Broadcast cart updates to customer display
    const broadcastCartUpdate = (cartData) => {
        axios.post('/api/pos/broadcast-cart', {
            terminal_id: terminalId,
            cart: cartData
        }).catch(err => {
            console.error('Failed to broadcast cart update:', err);
        });
    };

    const broadcastUIUpdate = async (uiData) => {
        try {
            await axios.post('/api/pos/broadcast-ui', {
                terminal_id: terminalId,
                ui: uiData
            });
        } catch (error) {
            console.error('Failed to broadcast UI update:', error);
        }
    };

    return {
        broadcastCartUpdate,
        broadcastUIUpdate
    };
}