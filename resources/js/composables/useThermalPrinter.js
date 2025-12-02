/**
 * useThermalPrinter.js - Vue Composable for Thermal Printing
 * 
 * Usage in any component:
 *   import { useThermalPrinter } from '@/composables/useThermalPrinter'
 *   
 *   const { printReceipt, printKOT, isConnected, connectPrinter } = useThermalPrinter()
 *   
 *   // Print a receipt
 *   await printReceipt(orderData)
 */

import { ref, reactive, onMounted } from 'vue';

// Shared state across all components
const state = reactive({
    device: null,
    connected: false,
    printerName: null,
    webUSBSupported: false,
    lastError: null
});

// ESC/POS Commands
const ESC_POS = {
    INIT: [0x1B, 0x40],
    CUT: [0x1D, 0x56, 0x00],
    PARTIAL_CUT: [0x1D, 0x56, 0x01],
    FEED: [0x1B, 0x64],
    ALIGN_LEFT: [0x1B, 0x61, 0x00],
    ALIGN_CENTER: [0x1B, 0x61, 0x01],
    ALIGN_RIGHT: [0x1B, 0x61, 0x02],
    BOLD_ON: [0x1B, 0x45, 0x01],
    BOLD_OFF: [0x1B, 0x45, 0x00],
    DOUBLE_ON: [0x1D, 0x21, 0x11],
    DOUBLE_OFF: [0x1D, 0x21, 0x00],
};

// Common thermal printer vendor IDs
const VENDOR_IDS = [
    0x0483, 0x0416, 0x0419, 0x04B8, 0x0525, 0x067B,
    0x1504, 0x1A86, 0x0DD4, 0x0FE6, 0x20D1, 0x154F
];

export function useThermalPrinter() {
    const charsPerLine = 48;
    const loading = ref(false);

    // Check WebUSB support
    const checkSupport = () => {
        state.webUSBSupported = 'usb' in navigator;
        return state.webUSBSupported;
    };

    // Get paired devices
    const getPairedDevices = async () => {
        if (!state.webUSBSupported) return [];
        
        try {
            const devices = await navigator.usb.getDevices();
            return devices.filter(device =>
                VENDOR_IDS.includes(device.vendorId) ||
                device.productName?.toLowerCase().includes('printer') ||
                device.productName?.toLowerCase().includes('pos') ||
                device.productName?.toLowerCase().includes('thermal')
            );
        } catch (error) {
            console.error('Error getting USB devices:', error);
            return [];
        }
    };

    // Connect to USB printer
    const connectPrinter = async () => {
        if (!state.webUSBSupported) {
            state.lastError = 'WebUSB not supported. Use Chrome or Edge.';
            return false;
        }

        try {
            loading.value = true;
            state.lastError = null;

            const filters = VENDOR_IDS.map(vendorId => ({ vendorId }));
            state.device = await navigator.usb.requestDevice({ filters });

            await state.device.open();

            if (state.device.configuration === null) {
                await state.device.selectConfiguration(1);
            }

            const interfaceNumber = state.device.configuration.interfaces[0].interfaceNumber;
            await state.device.claimInterface(interfaceNumber);

            state.connected = true;
            state.printerName = state.device.productName || 'USB Printer';

            // Save to localStorage
            localStorage.setItem('usb_printer_connected', 'true');

            return true;
        } catch (error) {
            state.lastError = error.message;
            state.connected = false;
            return false;
        } finally {
            loading.value = false;
        }
    };

    // Disconnect from printer
    const disconnectPrinter = async () => {
        if (state.device && state.connected) {
            try {
                await state.device.close();
            } catch (e) {
                // Ignore close errors
            }
            state.device = null;
            state.connected = false;
            state.printerName = null;
            localStorage.removeItem('usb_printer_connected');
        }
    };

    // Send data to printer
    const sendData = async (data) => {
        if (!state.connected || !state.device) {
            throw new Error('Printer not connected');
        }

        const endpoint = state.device.configuration.interfaces[0]
            .alternate.endpoints.find(e => e.direction === 'out');

        if (!endpoint) {
            throw new Error('No output endpoint found');
        }

        const buffer = data instanceof Uint8Array ? data : new Uint8Array(data);
        await state.device.transferOut(endpoint.endpointNumber, buffer);
    };

    // Text to bytes
    const textToBytes = (text) => {
        const encoder = new TextEncoder();
        return encoder.encode(text + '\n');
    };

    // Print text line
    const printText = async (text) => {
        await sendData(textToBytes(text));
    };

    // Print row (left and right aligned)
    const printRow = async (left, right) => {
        const spaces = charsPerLine - left.length - right.length;
        const row = left + ' '.repeat(Math.max(1, spaces)) + right;
        await printText(row);
    };

    // Format money
    const formatMoney = (value, currency = '£') => {
        return currency + parseFloat(value).toFixed(2);
    };

    // Print separator line
    const printLine = async (char = '-') => {
        await printText(char.repeat(charsPerLine));
    };

    // === MAIN PRINT FUNCTIONS ===

    // Print Customer Receipt
    const printReceipt = async (order, business) => {
        // Check printer preference
        const config = JSON.parse(localStorage.getItem('pos_printer_config') || '{}');
        const printerType = config.customer_printer || 'browser';

        if (printerType === 'browser' || !state.connected) {
            return browserPrintReceipt(order, business);
        }

        try {
            loading.value = true;

            // Initialize
            await sendData(new Uint8Array(ESC_POS.INIT));

            // Center align
            await sendData(new Uint8Array(ESC_POS.ALIGN_CENTER));

            // Business name (bold, double size)
            await sendData(new Uint8Array(ESC_POS.BOLD_ON));
            await sendData(new Uint8Array(ESC_POS.DOUBLE_ON));
            await printText((business.name || 'Business').toUpperCase());
            await sendData(new Uint8Array(ESC_POS.DOUBLE_OFF));
            await sendData(new Uint8Array(ESC_POS.BOLD_OFF));

            // Business info
            if (business.phone) await printText(business.phone);
            if (business.address) await printText(business.address);

            await printLine('=');

            // Receipt header
            await sendData(new Uint8Array(ESC_POS.BOLD_ON));
            await printText('CUSTOMER RECEIPT');
            await sendData(new Uint8Array(ESC_POS.BOLD_OFF));

            await printLine('=');

            // Left align for details
            await sendData(new Uint8Array(ESC_POS.ALIGN_LEFT));

            // Order details
            await printRow('Date:', order.order_date || new Date().toLocaleDateString());
            await printRow('Time:', order.order_time || new Date().toLocaleTimeString());
            await printRow('Customer:', order.customer_name || 'Walk In');
            await printRow('Order Type:', order.order_type || 'Collection');

            if (order.note) {
                await printRow('Note:', order.note);
            }

            await printLine('-');

            // Items header
            await sendData(new Uint8Array(ESC_POS.BOLD_ON));
            await printText('Item                     Qty    Price    Total');
            await sendData(new Uint8Array(ESC_POS.BOLD_OFF));

            await printLine('-');

            // Items
            const currency = business.currency || '£';
            for (const item of (order.items || [])) {
                const title = (item.title || 'Item').substring(0, 24).padEnd(24);
                const qty = (item.quantity || item.qty || 1).toString().padStart(4);
                const price = formatMoney(item.unit_price || item.price || 0, currency).padStart(8);
                const total = formatMoney((item.unit_price || item.price || 0) * (item.quantity || item.qty || 1), currency).padStart(8);
                await printText(title + qty + price + total);
            }

            await printLine('-');

            // Totals
            await printRow('Subtotal:', formatMoney(order.sub_total || 0, currency));
            await sendData(new Uint8Array(ESC_POS.BOLD_ON));
            await printRow('Total:', formatMoney(order.total_amount || order.sub_total || 0, currency));
            await sendData(new Uint8Array(ESC_POS.BOLD_OFF));

            // Payment info
            if (order.payment_type) {
                await sendData(new Uint8Array([...ESC_POS.FEED, 1]));
                await printRow('Payment:', order.payment_type.toUpperCase());

                if (order.payment_type === 'cash' && order.cash_received > 0) {
                    await printRow('Cash Received:', formatMoney(order.cash_received, currency));
                    const change = order.cash_received - (order.total_amount || order.sub_total || 0);
                    if (change > 0) {
                        await printRow('Change:', formatMoney(change, currency));
                    }
                }
            }

            // Footer
            await sendData(new Uint8Array([...ESC_POS.FEED, 2]));
            await sendData(new Uint8Array(ESC_POS.ALIGN_CENTER));
            await printText((business.footer || 'Thank you!').toUpperCase());
            await sendData(new Uint8Array([...ESC_POS.FEED, 3]));

            // Cut
            await sendData(new Uint8Array(ESC_POS.CUT));

            return { success: true, method: 'usb' };
        } catch (error) {
            console.error('USB print error:', error);
            // Fall back to browser print
            return browserPrintReceipt(order, business);
        } finally {
            loading.value = false;
        }
    };

    // Print KOT (Kitchen Order Ticket)
    const printKOT = async (order, business) => {
        const config = JSON.parse(localStorage.getItem('pos_printer_config') || '{}');
        const printerType = config.kot_printer || 'browser';

        if (printerType === 'browser' || !state.connected) {
            return browserPrintKOT(order, business);
        }

        try {
            loading.value = true;

            await sendData(new Uint8Array(ESC_POS.INIT));
            await sendData(new Uint8Array(ESC_POS.ALIGN_CENTER));

            // Business name
            await sendData(new Uint8Array(ESC_POS.BOLD_ON));
            await sendData(new Uint8Array(ESC_POS.DOUBLE_ON));
            await printText((business.name || 'KITCHEN').toUpperCase());
            await sendData(new Uint8Array(ESC_POS.DOUBLE_OFF));
            await sendData(new Uint8Array(ESC_POS.BOLD_OFF));

            await printLine('=');
            await sendData(new Uint8Array(ESC_POS.BOLD_ON));
            await printText('KITCHEN ORDER TICKET');
            await sendData(new Uint8Array(ESC_POS.BOLD_OFF));
            await printLine('=');

            await sendData(new Uint8Array(ESC_POS.ALIGN_LEFT));

            await printRow('Date:', order.order_date || new Date().toLocaleDateString());
            await printRow('Time:', order.order_time || new Date().toLocaleTimeString());
            await printRow('Customer:', order.customer_name || 'Walk In');
            await printRow('Order Type:', order.order_type || 'Dine In');

            if (order.kitchen_note) {
                await printRow('Note:', order.kitchen_note);
            }

            await printLine('-');

            await sendData(new Uint8Array(ESC_POS.BOLD_ON));
            await printRow('Item', 'Qty');
            await sendData(new Uint8Array(ESC_POS.BOLD_OFF));

            await printLine('-');

            // Items (no prices on KOT)
            for (const item of (order.items || [])) {
                const title = (item.title || 'Item').substring(0, 40);
                const qty = (item.quantity || item.qty || 1).toString();
                await printRow(title, qty);

                if (item.item_kitchen_note) {
                    await printText('  Note: ' + item.item_kitchen_note);
                }
            }

            await printLine('-');

            await sendData(new Uint8Array([...ESC_POS.FEED, 2]));
            await sendData(new Uint8Array(ESC_POS.ALIGN_CENTER));
            await printText((business.footer || 'KITCHEN COPY').toUpperCase());
            await sendData(new Uint8Array([...ESC_POS.FEED, 3]));

            await sendData(new Uint8Array(ESC_POS.CUT));

            return { success: true, method: 'usb' };
        } catch (error) {
            console.error('USB KOT print error:', error);
            return browserPrintKOT(order, business);
        } finally {
            loading.value = false;
        }
    };

    // Browser print fallback for receipt
    const browserPrintReceipt = (order, business) => {
        const currency = business.currency || '£';
        
        let itemsHTML = '';
        for (const item of (order.items || [])) {
            const qty = item.quantity || item.qty || 1;
            const price = item.unit_price || item.price || 0;
            const total = price * qty;
            itemsHTML += '<tr>';
            itemsHTML += '<td>' + (item.title || 'Item') + '</td>';
            itemsHTML += '<td style="text-align:center">' + qty + '</td>';
            itemsHTML += '<td style="text-align:right">' + formatMoney(price, currency) + '</td>';
            itemsHTML += '<td style="text-align:right">' + formatMoney(total, currency) + '</td>';
            itemsHTML += '</tr>';
        }

        let html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        html += '<title>Receipt</title>';
        html += '<style>';
        html += '@page { size: 80mm auto; margin: 0; }';
        html += 'body { font-family: "Courier New", monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5mm; }';
        html += '.header { text-align: center; margin-bottom: 10px; }';
        html += '.business-name { font-size: 16px; font-weight: bold; }';
        html += '.divider { border-top: 1px dashed #000; margin: 8px 0; }';
        html += '.info-row { display: flex; justify-content: space-between; }';
        html += 'table { width: 100%; border-collapse: collapse; }';
        html += 'th, td { padding: 3px 0; }';
        html += '.footer { text-align: center; margin-top: 15px; font-weight: bold; }';
        html += '</style></head><body>';
        html += '<div class="header">';
        if (business.logo_url) {
            html += '<img src="' + business.logo_url + '" style="max-width:100px;max-height:60px;margin-bottom:5px">';
        }
        html += '<div class="business-name">' + (business.name || 'Business').toUpperCase() + '</div>';
        html += '<div>' + (business.phone || '') + '</div>';
        html += '<div>' + (business.address || '') + '</div>';
        html += '</div>';
        html += '<div class="divider"></div>';
        html += '<div style="text-align:center;font-weight:bold">CUSTOMER RECEIPT</div>';
        html += '<div class="divider"></div>';
        html += '<div class="info-row"><span>Date:</span><span>' + (order.order_date || new Date().toLocaleDateString()) + '</span></div>';
        html += '<div class="info-row"><span>Time:</span><span>' + (order.order_time || new Date().toLocaleTimeString()) + '</span></div>';
        html += '<div class="info-row"><span>Customer:</span><span>' + (order.customer_name || 'Walk In') + '</span></div>';
        html += '<div class="info-row"><span>Order Type:</span><span>' + (order.order_type || 'Collection') + '</span></div>';
        html += '<div class="divider"></div>';
        html += '<table><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>';
        html += '<tbody>' + itemsHTML + '</tbody></table>';
        html += '<div class="divider"></div>';
        html += '<div class="info-row"><span>Subtotal:</span><span>' + formatMoney(order.sub_total || 0, currency) + '</span></div>';
        html += '<div class="info-row" style="font-weight:bold"><span>Total:</span><span>' + formatMoney(order.total_amount || order.sub_total || 0, currency) + '</span></div>';
        html += '<div class="divider"></div>';
        html += '<div class="footer">' + (business.footer || 'Thank you!').toUpperCase() + '</div>';
        html += '</body></html>';

        const printWindow = window.open('', '_blank', 'width=400,height=600');
        printWindow.document.write(html);
        printWindow.document.close();
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
        }, 500);

        return { success: true, method: 'browser' };
    };

    // Browser print fallback for KOT
    const browserPrintKOT = (order, business) => {
        let itemsHTML = '';
        for (const item of (order.items || [])) {
            const qty = item.quantity || item.qty || 1;
            itemsHTML += '<tr>';
            itemsHTML += '<td>' + (item.title || 'Item') + '</td>';
            itemsHTML += '<td style="text-align:right">' + qty + '</td>';
            itemsHTML += '</tr>';
            if (item.item_kitchen_note) {
                itemsHTML += '<tr><td colspan="2" style="padding-left:15px;font-style:italic">Note: ' + item.item_kitchen_note + '</td></tr>';
            }
        }

        let html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        html += '<title>Kitchen Order</title>';
        html += '<style>';
        html += '@page { size: 80mm auto; margin: 0; }';
        html += 'body { font-family: "Courier New", monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5mm; }';
        html += '.header { text-align: center; margin-bottom: 10px; }';
        html += '.business-name { font-size: 16px; font-weight: bold; }';
        html += '.divider { border-top: 1px dashed #000; margin: 8px 0; }';
        html += '.info-row { display: flex; justify-content: space-between; }';
        html += 'table { width: 100%; border-collapse: collapse; }';
        html += 'th, td { padding: 3px 0; }';
        html += '.footer { text-align: center; margin-top: 15px; font-weight: bold; }';
        html += '</style></head><body>';
        html += '<div class="header">';
        html += '<div class="business-name">' + (business.name || 'KITCHEN').toUpperCase() + '</div>';
        html += '</div>';
        html += '<div class="divider"></div>';
        html += '<div style="text-align:center;font-weight:bold;font-size:14px">KITCHEN ORDER TICKET</div>';
        html += '<div class="divider"></div>';
        html += '<div class="info-row"><span>Date:</span><span>' + (order.order_date || new Date().toLocaleDateString()) + '</span></div>';
        html += '<div class="info-row"><span>Time:</span><span>' + (order.order_time || new Date().toLocaleTimeString()) + '</span></div>';
        html += '<div class="info-row"><span>Customer:</span><span>' + (order.customer_name || 'Walk In') + '</span></div>';
        html += '<div class="info-row"><span>Order Type:</span><span>' + (order.order_type || 'Dine In') + '</span></div>';
        if (order.kitchen_note) {
            html += '<div class="info-row"><span>Note:</span><span>' + order.kitchen_note + '</span></div>';
        }
        html += '<div class="divider"></div>';
        html += '<table><thead><tr><th>Item</th><th style="text-align:right">Qty</th></tr></thead>';
        html += '<tbody>' + itemsHTML + '</tbody></table>';
        html += '<div class="divider"></div>';
        html += '<div class="footer">KITCHEN COPY</div>';
        html += '</body></html>';

        const printWindow = window.open('', '_blank', 'width=400,height=600');
        printWindow.document.write(html);
        printWindow.document.close();
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
        }, 500);

        return { success: true, method: 'browser' };
    };

    // Auto-reconnect on mount
    const autoReconnect = async () => {
        checkSupport();
        
        if (state.webUSBSupported && localStorage.getItem('usb_printer_connected')) {
            const devices = await getPairedDevices();
            if (devices.length > 0) {
                try {
                    state.device = devices[0];
                    await state.device.open();
                    
                    if (state.device.configuration === null) {
                        await state.device.selectConfiguration(1);
                    }
                    
                    const interfaceNumber = state.device.configuration.interfaces[0].interfaceNumber;
                    await state.device.claimInterface(interfaceNumber);
                    
                    state.connected = true;
                    state.printerName = state.device.productName || 'USB Printer';
                } catch (e) {
                    console.log('Auto-reconnect failed:', e.message);
                }
            }
        }
    };

    return {
        // State
        isConnected: () => state.connected,
        printerName: () => state.printerName,
        isWebUSBSupported: () => state.webUSBSupported,
        lastError: () => state.lastError,
        loading,

        // Methods
        checkSupport,
        connectPrinter,
        disconnectPrinter,
        autoReconnect,
        getPairedDevices,

        // Print functions
        printReceipt,
        printKOT,
        browserPrintReceipt,
        browserPrintKOT
    };
}