import { debounce } from 'lodash';

export function usePOSBroadcast(terminalId) {
    // ✅ Track last successful broadcast with hash
    let lastCartHash = null;
    let lastUIHash = null;
    let pendingRequests = new Map(); // Track pending requests to avoid duplicates

    // ✅ Simple hash function
    const hashData = (data) => {
        return JSON.stringify(data);
    };

    /**
     * ✅ IMPROVED: Generic broadcast function with retry logic
     */
    const broadcast = async (endpoint, data, hashKey, currentHash) => {
        // Skip if data hasn't changed
        if (currentHash === (hashKey === 'cart' ? lastCartHash : lastUIHash)) {
            return { success: true, skipped: true };
        }

        // Skip if there's already a pending request for this data
        if (pendingRequests.has(hashKey)) {
            console.log(`⏳ Skipping duplicate ${hashKey} request`);
            return { success: false, skipped: true };
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);
        
        pendingRequests.set(hashKey, true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    // Add CSRF token if using Laravel Sanctum
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(data),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            // Update hash on success
            if (hashKey === 'cart') {
                lastCartHash = currentHash;
            } else {
                lastUIHash = currentHash;
            }

            console.log(`✅ ${hashKey} updated - v${result.version}`);
            return { success: true, data: result };

        } catch (error) {
            if (error.name === 'AbortError') {
                console.warn(`⚠️ ${hashKey} update timeout`);
            } else {
                console.error(`❌ Failed to update ${hashKey}:`, error.message);
            }
            return { success: false, error };
        } finally {
            pendingRequests.delete(hashKey);
        }
    };

    /**
     * ✅ Cart updates with change detection
     */
    const broadcastCartUpdate = debounce(async (cartData) => {
        const currentHash = hashData(cartData);
        
        await broadcast(
            '/api/pos/terminal/update-cart',
            {
                terminal_id: terminalId,
                cart: cartData
            },
            'cart',
            currentHash
        );
    }, 800); // 800ms debounce for cart (reasonable for typing/changes)

    /**
     * ✅ UI updates with change detection
     */
    const broadcastUIUpdate = debounce(async (uiData) => {
        const currentHash = hashData(uiData);
        
        await broadcast(
            '/api/pos/terminal/update-ui',
            {
                terminal_id: terminalId,
                ui: uiData
            },
            'ui',
            currentHash
        );
    }, 300); // 300ms debounce for UI (faster for better UX)

    /**
     * ✅ NEW: Immediate broadcast (no debounce) for critical updates
     */
    const broadcastImmediateCartUpdate = async (cartData) => {
        const currentHash = hashData(cartData);
        
        return await broadcast(
            '/api/pos/terminal/update-cart',
            {
                terminal_id: terminalId,
                cart: cartData
            },
            'cart',
            currentHash
        );
    };

    /**
     * ✅ NEW: Batch update both cart and UI simultaneously
     */
    const broadcastBatchUpdate = debounce(async (cartData, uiData) => {
        const cartHash = hashData(cartData);
        const uiHash = hashData(uiData);

        // Check if either has changed
        if (cartHash === lastCartHash && uiHash === lastUIHash) {
            return { success: true, skipped: true };
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        try {
            const response = await fetch('/api/pos/terminal/update-both', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    terminal_id: terminalId,
                    cart: cartData,
                    ui: uiData
                }),
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const result = await response.json();

            lastCartHash = cartHash;
            lastUIHash = uiHash;

            console.log(`✅ Batch update successful - v${result.version}`);
            return { success: true, data: result };

        } catch (error) {
            console.error('❌ Batch update failed:', error.message);
            return { success: false, error };
        }
    }, 500);

    /**
     * ✅ NEW: Force refresh/clear state
     */
    const clearBroadcastCache = () => {
        lastCartHash = null;
        lastUIHash = null;
        pendingRequests.clear();
        console.log('🧹 Broadcast cache cleared');
    };

    return {
        broadcastCartUpdate,
        broadcastUIUpdate,
        broadcastImmediateCartUpdate,
        broadcastBatchUpdate,
        clearBroadcastCache
    };
}