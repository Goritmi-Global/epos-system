// composables/useSumUp.js
import { ref, reactive } from 'vue';
import axios from 'axios';

export function useSumUp() {
    const state = reactive({
        connected: false,
        readers: [],
        selectedReader: localStorage.getItem('sumup_reader_id') || null,
        currentCheckout: null,
        error: null
    });

    const loading = ref(false);
    const processing = ref(false);

    // Get paired readers
    const getReaders = async () => {
        try {
            loading.value = true;
            const response = await axios.get('/api/sumup/readers');
            
            if (response.data.success) {
                state.readers = response.data.data || [];
                state.connected = state.readers.length > 0;
                
                // Auto-select first reader
                if (!state.selectedReader && state.readers.length > 0) {
                    state.selectedReader = state.readers[0].id;
                    localStorage.setItem('sumup_reader_id', state.selectedReader);
                }
            }
            
            return state.readers;
        } catch (error) {
            state.error = error.message;
            state.connected = false;
            throw error;
        } finally {
            loading.value = false;
        }
    };

    // Pair new reader
    const pairReader = async (pairingCode) => {
        try {
            loading.value = true;
            const response = await axios.post('/api/sumup/pair-reader', {
                pairing_code: pairingCode
            });
            
            if (response.data.success) {
                await getReaders();
                return response.data.data;
            }
            
            throw new Error(response.data.message);
        } catch (error) {
            state.error = error.message;
            throw error;
        } finally {
            loading.value = false;
        }
    };

    // Process card payment
    const processPayment = async (amount, description = 'POS Payment', orderId = null) => {
        try {
            processing.value = true;
            state.error = null;

            console.log('🔵 Processing SumUp payment:', { amount, description, orderId });

            // 1. Create checkout and send to reader
            const response = await axios.post('/api/sumup/process-payment', {
                amount: parseFloat(amount),
                currency: 'GBP',
                description,
                order_id: orderId,
                reader_id: state.selectedReader
            });

            if (!response.data.success) {
                throw new Error(response.data.message);
            }

            const checkoutId = response.data.checkout_id;
            state.currentCheckout = checkoutId;

            console.log('✅ Payment sent to reader, checkout:', checkoutId);

            // 2. Wait for payment completion (long polling)
            const statusResponse = await axios.get(`/api/sumup/wait/${checkoutId}`);

            if (statusResponse.data.success && statusResponse.data.status === 'PAID') {
                console.log('✅ Payment completed successfully');
                state.currentCheckout = null;
                return {
                    success: true,
                    checkout_id: checkoutId,
                    transaction: statusResponse.data.data
                };
            } else {
                throw new Error('Payment failed or was cancelled');
            }

        } catch (error) {
            console.error('❌ SumUp payment error:', error);
            state.error = error.message;
            throw error;
        } finally {
            processing.value = false;
            state.currentCheckout = null;
        }
    };

    // Check status (manual)
    const checkStatus = async (checkoutId) => {
        try {
            const response = await axios.get(`/api/sumup/status/${checkoutId}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    };

    // Set selected reader
    const setReader = (readerId) => {
        state.selectedReader = readerId;
        localStorage.setItem('sumup_reader_id', readerId);
    };

    return {
        state,
        loading,
        processing,
        getReaders,
        pairReader,
        processPayment,
        checkStatus,
        setReader
    };
}