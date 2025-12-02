<template>
    <div class="thermal-printer-manager">
        <!-- Printer Status Badge -->
        <div class="printer-status" :class="statusClass">
            <span class="status-indicator"></span>
            <span class="status-text">{{ statusText }}</span>
        </div>

        <!-- Printer Selection Modal -->
        <div v-if="showPrinterModal" class="printer-modal-overlay" @click.self="showPrinterModal = false">
            <div class="printer-modal">
                <div class="modal-header">
                    <h3>Printer Settings</h3>
                    <button class="close-btn" @click="showPrinterModal = false">&times;</button>
                </div>
                
                <div class="modal-body">
                    <!-- WebUSB Status -->
                    <div class="webusb-status" :class="{ supported: isWebUSBSupported }">
                        <span v-if="isWebUSBSupported">✓ WebUSB Supported</span>
                        <span v-else>✗ WebUSB Not Supported - Using Browser Print</span>
                    </div>

                    <!-- Connected Printer -->
                    <div v-if="connectedPrinter" class="connected-printer">
                        <h4>Connected Printer</h4>
                        <div class="printer-info">
                            <div class="printer-name">{{ connectedPrinter.name }}</div>
                            <div class="printer-manufacturer">{{ connectedPrinter.manufacturer }}</div>
                            <button class="btn btn-danger" @click="disconnectPrinter">Disconnect</button>
                        </div>
                    </div>

                    <!-- Connect Button -->
                    <div v-else class="connect-section">
                        <p>No printer connected. Click below to select a USB thermal printer.</p>
                        <button 
                            class="btn btn-primary" 
                            @click="connectPrinter"
                            :disabled="connecting || !isWebUSBSupported"
                        >
                            {{ connecting ? 'Connecting...' : 'Connect USB Printer' }}
                        </button>
                        
                        <div class="browser-print-note" v-if="!isWebUSBSupported">
                            <p>Your browser doesn't support WebUSB. Receipts will print using the browser's print dialog.</p>
                            <p><strong>For direct USB printing, use Chrome or Edge.</strong></p>
                        </div>
                    </div>

                    <!-- Printer Type Selection -->
                    <div class="printer-types">
                        <h4>Printer Assignment</h4>
                        <div class="printer-type-row">
                            <label>Customer Receipt Printer:</label>
                            <select v-model="printerSettings.customerPrinter">
                                <option value="default">Default (Connected USB)</option>
                                <option value="browser">Browser Print Dialog</option>
                            </select>
                        </div>
                        <div class="printer-type-row">
                            <label>Kitchen (KOT) Printer:</label>
                            <select v-model="printerSettings.kotPrinter">
                                <option value="default">Default (Connected USB)</option>
                                <option value="browser">Browser Print Dialog</option>
                            </select>
                        </div>
                    </div>

                    <!-- Test Print -->
                    <div class="test-print">
                        <h4>Test Print</h4>
                        <button class="btn btn-secondary" @click="testPrint" :disabled="printing">
                            {{ printing ? 'Printing...' : 'Print Test Receipt' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Button (for use in your UI) -->
        <slot 
            :print-receipt="printReceipt" 
            :print-kot="printKot"
            :print-z-report="printZReport"
            :is-connected="!!connectedPrinter"
            :open-settings="() => showPrinterModal = true"
        >
            <!-- Default slot content -->
            <button class="btn btn-icon" @click="showPrinterModal = true" :title="statusText">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6,9 6,2 18,2 18,9"></polyline>
                    <path d="M6,18 L4,18 C2.9,18 2,17.1 2,16 L2,11 C2,9.9 2.9,9 4,9 L20,9 C21.1,9 22,9.9 22,11 L22,16 C22,17.1 21.1,18 20,18 L18,18"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span class="status-dot" :class="statusClass"></span>
            </button>
        </slot>
    </div>
</template>

<script>
import ThermalPrinter from '../thermal-print.js';

export default {
    name: 'ThermalPrinterManager',
    
    props: {
        // API endpoints
        receiptEndpoint: {
            type: String,
            default: '/api/customer/print-receipt'
        },
        kotEndpoint: {
            type: String,
            default: '/api/kot/print-receipt'
        },
        zReportEndpoint: {
            type: String,
            default: '/api/printers/{shift}/z-report/print'
        }
    },
    
    data() {
        return {
            printer: null,
            connectedPrinter: null,
            showPrinterModal: false,
            connecting: false,
            printing: false,
            printerSettings: {
                customerPrinter: 'default',
                kotPrinter: 'default'
            }
        };
    },
    
    computed: {
        isWebUSBSupported() {
            return this.printer?.isWebUSBSupported() || false;
        },
        
        statusClass() {
            if (this.connectedPrinter) return 'connected';
            if (this.isWebUSBSupported) return 'available';
            return 'browser-only';
        },
        
        statusText() {
            if (this.connectedPrinter) return "Connected: ${this.connectedPrinter.name}";
            if (this.isWebUSBSupported) return 'No printer connected';
            return 'Browser print only';
        }
    },
    
    mounted() {
        this.printer = new ThermalPrinter();
        this.loadSettings();
        this.checkExistingDevices();
    },
    
    beforeUnmount() {
        this.printer?.disconnect();
    },
    
    methods: {
        loadSettings() {
            const saved = localStorage.getItem('printerSettings');
            if (saved) {
                this.printerSettings = JSON.parse(saved);
            }
        },
        
        saveSettings() {
            localStorage.setItem('printerSettings', JSON.stringify(this.printerSettings));
        },
        
        async checkExistingDevices() {
            if (!this.isWebUSBSupported) return;
            
            const devices = await this.printer.getDevices();
            if (devices.length > 0) {
                // Auto-connect to previously paired device
                try {
                    const result = await this.printer.connect();
                    this.connectedPrinter = result.device;
                } catch (e) {
                    // Ignore - user may need to manually connect
                }
            }
        },
        
        async connectPrinter() {
            this.connecting = true;
            try {
                const result = await this.printer.connect();
                this.connectedPrinter = result.device;
                this.$emit('printer-connected', this.connectedPrinter);
            } catch (error) {
                console.error('Failed to connect:', error);
                this.$emit('printer-error', error.message);
                alert('Failed to connect to printer: ' + error.message);
            } finally {
                this.connecting = false;
            }
        },
        
        async disconnectPrinter() {
            await this.printer.disconnect();
            this.connectedPrinter = null;
            this.$emit('printer-disconnected');
        },
        
        async testPrint() {
            this.printing = true;
            try {
                const testData = {
                    type: 'customer_receipt',
                    business: {
                        name: 'Test Business',
                        phone: '+44 123 456 7890',
                        address: '123 Test Street, London',
                        footer: 'Thank you for testing!',
                        logo_url: null
                    },
                    order: {
                        date: new Date().toISOString().split('T')[0],
                        time: new Date().toTimeString().split(' ')[0],
                        customer: 'Test Customer',
                        order_type: 'Collection',
                        note: null
                    },
                    items: [
                        { title: 'Test Item 1', quantity: 2, unit_price: 5.99, total: 11.98 },
                        { title: 'Test Item 2', quantity: 1, unit_price: 3.50, total: 3.50 }
                    ],
                    totals: {
                        subtotal: 15.48,
                        total: 15.48,
                        tax: 0
                    },
                    payment: {
                        type: 'cash',
                        cash_received: 20.00,
                        card_amount: 0,
                        change: 4.52
                    }
                };
                
                await this.printer.printReceipt(testData);
                this.$emit('print-success', 'test');
            } catch (error) {
                console.error('Test print failed:', error);
                this.$emit('printer-error', error.message);
                alert('Test print failed: ' + error.message);
            } finally {
                this.printing = false;
            }
        },
        
        async printReceipt(order) {
            this.printing = true;
            try {
                // Fetch receipt data from server
                const response = await fetch(this.receiptEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ order })
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to generate receipt');
                }
                
                // Print using the appropriate method
                if (this.printerSettings.customerPrinter === 'browser' || !this.connectedPrinter) {
                    await this.printer.browserPrint(result.data);
                } else if (result.print_commands) {
                    await this.printer.executeCommands(result.print_commands);
                } else {
                    await this.printer.printReceipt(result.data);
                }
                
                this.$emit('print-success', 'receipt');
                return { success: true };
            } catch (error) {
                console.error('Print receipt failed:', error);
                this.$emit('printer-error', error.message);
                return { success: false, error: error.message };
            } finally {
                this.printing = false;
            }
        },
        
        async printKot(order) {
            this.printing = true;
            try {
                const response = await fetch(this.kotEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ order })
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to generate KOT');
                }
                
                if (this.printerSettings.kotPrinter === 'browser' || !this.connectedPrinter) {
                    await this.printer.browserPrint(result.data);
                } else if (result.print_commands) {
                    await this.printer.executeCommands(result.print_commands);
                } else {
                    await this.printer.printReceipt(result.data);
                }
                
                this.$emit('print-success', 'kot');
                return { success: true };
            } catch (error) {
                console.error('Print KOT failed:', error);
                this.$emit('printer-error', error.message);
                return { success: false, error: error.message };
            } finally {
                this.printing = false;
            }
        },
        
        async printZReport(shiftId) {
            this.printing = true;
            try {
                const response = await fetch(`${this.zReportEndpoint}/${shiftId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Failed to generate Z Report');
                }
                
                // Z Report always uses browser print due to complexity
                await this.printer.browserPrint(result.data);
                
                this.$emit('print-success', 'zreport');
                return { success: true };
            } catch (error) {
                console.error('Print Z Report failed:', error);
                this.$emit('printer-error', error.message);
                return { success: false, error: error.message };
            } finally {
                this.printing = false;
            }
        }
    },
    
    watch: {
        printerSettings: {
            handler() {
                this.saveSettings();
            },
            deep: true
        }
    }
};
</script>

<style scoped>
.thermal-printer-manager {
    position: relative;
}

.printer-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
}

.printer-status.connected {
    background: #d4edda;
    color: #155724;
}

.printer-status.available {
    background: #fff3cd;
    color: #856404;
}

.printer-status.browser-only {
    background: #e2e3e5;
    color: #383d41;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.connected .status-indicator {
    background: #28a745;
}

.available .status-indicator {
    background: #ffc107;
}

.browser-only .status-indicator {
    background: #6c757d;
}

.printer-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.printer-modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.webusb-status {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    background: #f8d7da;
    color: #721c24;
}

.webusb-status.supported {
    background: #d4edda;
    color: #155724;
}

.connected-printer {
    margin-bottom: 20px;
}

.connected-printer h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #666;
}

.printer-info {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
}

.printer-name {
    font-weight: bold;
    font-size: 16px;
}

.printer-manufacturer {
    color: #666;
    font-size: 14px;
    margin-bottom: 12px;
}

.connect-section {
    margin-bottom: 20px;
    text-align: center;
}

.connect-section p {
    margin-bottom: 16px;
    color: #666;
}

.browser-print-note {
    margin-top: 16px;
    padding: 12px;
    background: #fff3cd;
    border-radius: 8px;
    font-size: 13px;
}

.printer-types {
    margin-bottom: 20px;
}

.printer-types h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #666;
}

.printer-type-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.printer-type-row label {
    font-size: 14px;
}

.printer-type-row select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.test-print {
    border-top: 1px solid #e0e0e0;
    padding-top: 20px;
}

.test-print h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #666;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.6;
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

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover:not(:disabled) {
    background: #c82333;
}

.btn-icon {
    position: relative;
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 8px;
    border-radius: 6px;
}

.status-dot {
    position: absolute;
    top: -3px;
    right: -3px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid white;
}

.status-dot.connected {
    background: #28a745;
}

.status-dot.available {
    background: #ffc107;
}

.status-dot.browser-only {
    background: #6c757d;
}
</style>