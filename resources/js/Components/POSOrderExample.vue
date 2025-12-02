<template>
    <div class="pos-order-screen">
        <!-- Your existing POS UI -->
        <div class="order-header">
            <h2>New Order</h2>
            
            <!-- Printer Manager Component -->
            <ThermalPrinterManager
                ref="printerManager"
                receipt-endpoint="/api/customer/print-receipt"
                kot-endpoint="/api/kot/print-receipt"
                z-report-endpoint="/api/printers/{shift}/z-report/print"
                @printer-connected="onPrinterConnected"
                @printer-disconnected="onPrinterDisconnected"
                @print-success="onPrintSuccess"
                @printer-error="onPrinterError"
            >
                <!-- Custom slot content for printer button -->
                <template #default="{ printReceipt, printKot, isConnected, openSettings }">
                    <div class="printer-controls">
                        <button 
                            class="btn btn-settings" 
                            @click="openSettings"
                            :title="isConnected ? 'Printer Connected' : 'Configure Printer'"
                        >
                            <span class="printer-icon">🖨</span>
                            <span class="status" :class="{ connected: isConnected }"></span>
                        </button>
                    </div>
                </template>
            </ThermalPrinterManager>
        </div>

        <!-- Order Items -->
        <div class="order-items">
            <div v-for="item in orderItems" :key="item.id" class="order-item">
                <span class="item-name">{{ item.title }}</span>
                <span class="item-qty">x{{ item.quantity }}</span>
                <span class="item-price">£{{ item.total.toFixed(2) }}</span>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>£{{ subtotal.toFixed(2) }}</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span>£{{ total.toFixed(2) }}</span>
            </div>
        </div>

        <!-- Payment & Print Buttons -->
        <div class="order-actions">
            <button class="btn btn-secondary" @click="sendToKitchen" :disabled="!orderItems.length">
                Send to Kitchen
            </button>
            
            <button class="btn btn-primary" @click="completeOrder" :disabled="!orderItems.length">
                Complete Order
            </button>
        </div>

        <!-- Toast Notifications -->
        <div v-if="toast.show" class="toast" :class="toast.type">
            {{ toast.message }}
        </div>
    </div>
</template>

<script>
import ThermalPrinterManager from './ThermalPrinterManager.vue';

export default {
    name: 'POSOrderExample',
    
    components: {
        ThermalPrinterManager
    },
    
    data() {
        return {
            orderItems: [
                { id: 1, title: 'Chicken Burger', quantity: 2, unit_price: 8.99, total: 17.98 },
                { id: 2, title: 'Large Fries', quantity: 2, unit_price: 3.50, total: 7.00 },
                { id: 3, title: 'Cola', quantity: 2, unit_price: 2.50, total: 5.00 }
            ],
            customer: {
                name: 'Walk In',
                phone: '',
            },
            orderType: 'Collection',
            paymentType: 'cash',
            cashReceived: 0,
            toast: {
                show: false,
                message: '',
                type: 'success'
            }
        };
    },
    
    computed: {
        subtotal() {
            return this.orderItems.reduce((sum, item) => sum + item.total, 0);
        },
        
        total() {
            return this.subtotal; // Add tax calculation if needed
        },
        
        orderData() {
            return {
                order_date: new Date().toISOString().split('T')[0],
                order_time: new Date().toTimeString().split(' ')[0],
                customer_name: this.customer.name,
                order_type: this.orderType,
                payment_type: this.paymentType,
                cash_received: this.cashReceived,
                sub_total: this.subtotal,
                total_amount: this.total,
                items: this.orderItems.map(item => ({
                    title: item.title,
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                    price: item.unit_price
                })),
                note: '',
                kitchen_note: ''
            };
        }
    },
    
    methods: {
        async sendToKitchen() {
            try {
                const result = await this.$refs.printerManager.printKot(this.orderData);
                
                if (result.success) {
                    this.showToast('Kitchen order sent!', 'success');
                }
            } catch (error) {
                this.showToast('Failed to send to kitchen', 'error');
            }
        },
        
        async completeOrder() {
            try {
                // First, save the order to your backend
                // await this.saveOrder();
                
                // Then print the receipt
                const result = await this.$refs.printerManager.printReceipt(this.orderData);
                
                if (result.success) {
                    this.showToast('Order completed & receipt printed!', 'success');
                    // Clear order or navigate to next screen
                    // this.clearOrder();
                }
            } catch (error) {
                this.showToast('Failed to print receipt', 'error');
            }
        },
        
        onPrinterConnected(printer) {
            this.showToast('Printer connected: $printer.name', 'success');
        },
        
        onPrinterDisconnected() {
            this.showToast('Printer disconnected', 'info');
        },
        
        onPrintSuccess(type) {
            console.log('Print success: ${type}');
        },
        
        onPrinterError(message) {
            this.showToast(message, 'error');
        },
        
        showToast(message, type = 'info') {
            this.toast = { show: true, message, type };
            setTimeout(() => {
                this.toast.show = false;
            }, 3000);
        }
    }
};
</script>

<style scoped>
.pos-order-screen {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.order-header h2 {
    margin: 0;
}

.printer-controls {
    position: relative;
}

.btn-settings {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.printer-icon {
    font-size: 20px;
}

.status {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #dc3545;
}

.status.connected {
    background: #28a745;
}

.order-items {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.order-item:last-child {
    border-bottom: none;
}

.item-name {
    flex: 1;
    font-weight: 500;
}

.item-qty {
    color: #666;
    margin: 0 20px;
}

.item-price {
    font-weight: 600;
}

.order-summary {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.summary-row.total {
    font-size: 20px;
    font-weight: bold;
    border-top: 2px solid #e9ecef;
    padding-top: 16px;
    margin-top: 8px;
}

.order-actions {
    display: flex;
    gap: 12px;
}

.btn {
    flex: 1;
    padding: 16px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover:not(:disabled) {
    background: #545b62;
}

.toast {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 24px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    animation: slideUp 0.3s ease;
}

.toast.success {
    background: #28a745;
}

.toast.error {
    background: #dc3545;
}

.toast.info {
    background: #17a2b8;
}

@keyframes slideUp {
    from {
        transform: translateX(-50%) translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}
</style>