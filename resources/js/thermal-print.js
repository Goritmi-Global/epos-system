/**
 * ThermalPrinter.js - Client-Side Thermal Printing Library
 * 
 * Supports:
 * - WebUSB API (Chrome/Edge) for direct USB printing
 * - Browser Print Dialog as fallback
 * - ESC/POS command generation
 * 
 * Usage:
 *   const printer = new ThermalPrinter();
 *   await printer.connect();
 *   await printer.printReceipt(receiptData);
 */

class ThermalPrinter {
    constructor(options = {}) {
        this.device = null;
        this.connected = false;
        this.charsPerLine = options.charsPerLine || 48;
        this.encoding = options.encoding || 'cp437';
        
        // ESC/POS Commands
        this.commands = {
            INIT: [0x1B, 0x40],                    // Initialize printer
            CUT: [0x1D, 0x56, 0x00],               // Full cut
            PARTIAL_CUT: [0x1D, 0x56, 0x01],       // Partial cut
            FEED: [0x1B, 0x64],                    // Feed n lines
            ALIGN_LEFT: [0x1B, 0x61, 0x00],        // Left align
            ALIGN_CENTER: [0x1B, 0x61, 0x01],      // Center align
            ALIGN_RIGHT: [0x1B, 0x61, 0x02],       // Right align
            BOLD_ON: [0x1B, 0x45, 0x01],           // Bold on
            BOLD_OFF: [0x1B, 0x45, 0x00],          // Bold off
            DOUBLE_ON: [0x1D, 0x21, 0x11],         // Double width/height
            DOUBLE_OFF: [0x1D, 0x21, 0x00],        // Normal size
            UNDERLINE_ON: [0x1B, 0x2D, 0x01],      // Underline on
            UNDERLINE_OFF: [0x1B, 0x2D, 0x00],     // Underline off
        };
        
        // Common thermal printer vendor IDs
        this.vendorIds = [
            0x0483, // STMicroelectronics (many Chinese printers)
            0x0416, // Winbond
            0x0419, // Samsung
            0x04B8, // Epson
            0x0525, // PLX Technology
            0x067B, // Prolific (USB-Serial adapters)
            0x1504, // Black Copper / Chinese POS printers
            0x1A86, // QinHeng Electronics (CH340)
            0x0DD4, // Custom Engineering
            0x0FE6, // ICS Electronics
            0x20D1, // Chinese printers
            0x0FE6, // Kontron
            0x154F, // SNBC
        ];
    }

    /**
     * Check if WebUSB is supported
     */
    isWebUSBSupported() {
        return 'usb' in navigator;
    }

    /**
     * Get list of connected USB printers
     */
    async getDevices() {
        if (!this.isWebUSBSupported()) {
            return [];
        }

        try {
            const devices = await navigator.usb.getDevices();
            return devices.filter(device => 
                this.vendorIds.includes(device.vendorId) ||
                device.productName?.toLowerCase().includes('printer') ||
                device.productName?.toLowerCase().includes('pos') ||
                device.productName?.toLowerCase().includes('thermal')
            );
        } catch (error) {
            console.error('Error getting USB devices:', error);
            return [];
        }
    }

    /**
     * Request permission and connect to a USB printer
     */
    async connect() {
        if (!this.isWebUSBSupported()) {
            throw new Error('WebUSB is not supported in this browser. Use Chrome or Edge.');
        }

        try {
            // Request device with filters for common printer vendor IDs
            const filters = this.vendorIds.map(vendorId => ({ vendorId }));
            
            this.device = await navigator.usb.requestDevice({ filters });
            
            await this.device.open();
            
            // Select configuration
            if (this.device.configuration === null) {
                await this.device.selectConfiguration(1);
            }
            
            // Claim interface (usually interface 0 for printers)
            const interfaceNumber = this.device.configuration.interfaces[0].interfaceNumber;
            await this.device.claimInterface(interfaceNumber);
            
            this.connected = true;
            
            return {
                success: true,
                device: {
                    name: this.device.productName || 'USB Printer',
                    manufacturer: this.device.manufacturerName || 'Unknown',
                    vendorId: this.device.vendorId,
                    productId: this.device.productId
                }
            };
        } catch (error) {
            this.connected = false;
            throw new Error('Failed to connect to printer: ' + error.message);
        }
    }

    /**
     * Disconnect from the printer
     */
    async disconnect() {
        if (this.device && this.connected) {
            try {
                await this.device.close();
            } catch (e) {
                // Ignore close errors
            }
            this.device = null;
            this.connected = false;
        }
    }

    /**
     * Send raw bytes to the printer
     */
    async sendData(data) {
        if (!this.connected || !this.device) {
            throw new Error('Printer not connected');
        }

        const endpoint = this.device.configuration.interfaces[0]
            .alternate.endpoints.find(e => e.direction === 'out');
        
        if (!endpoint) {
            throw new Error('No output endpoint found');
        }

        const buffer = data instanceof Uint8Array ? data : new Uint8Array(data);
        await this.device.transferOut(endpoint.endpointNumber, buffer);
    }

    /**
     * Convert text to bytes with proper encoding
     */
    textToBytes(text) {
        const encoder = new TextEncoder();
        return encoder.encode(text + '\n');
    }

    /**
     * Initialize the printer
     */
    async init() {
        await this.sendData(new Uint8Array(this.commands.INIT));
    }

    /**
     * Print text
     */
    async printText(text) {
        await this.sendData(this.textToBytes(text));
    }

    /**
     * Set text alignment
     */
    async setAlign(align) {
        const cmd = align === 'center' ? this.commands.ALIGN_CENTER :
                    align === 'right' ? this.commands.ALIGN_RIGHT :
                    this.commands.ALIGN_LEFT;
        await this.sendData(new Uint8Array(cmd));
    }

    /**
     * Set bold mode
     */
    async setBold(enabled) {
        await this.sendData(new Uint8Array(
            enabled ? this.commands.BOLD_ON : this.commands.BOLD_OFF
        ));
    }

    /**
     * Set double size mode
     */
    async setDoubleSize(enabled) {
        await this.sendData(new Uint8Array(
            enabled ? this.commands.DOUBLE_ON : this.commands.DOUBLE_OFF
        ));
    }

    /**
     * Feed paper
     */
    async feed(lines = 1) {
        await this.sendData(new Uint8Array([...this.commands.FEED, lines]));
    }

    /**
     * Cut paper
     */
    async cut(partial = false) {
        await this.sendData(new Uint8Array(
            partial ? this.commands.PARTIAL_CUT : this.commands.CUT
        ));
    }

    /**
     * Print a separator line
     */
    async printLine(char = '-') {
        await this.printText(char.repeat(this.charsPerLine));
    }

    /**
     * Print a formatted row (left and right aligned text on same line)
     */
    async printRow(left, right) {
        const spaces = this.charsPerLine - left.length - right.length;
        const row = left + ' '.repeat(Math.max(1, spaces)) + right;
        await this.printText(row);
    }

    /**
     * Execute ESC/POS commands from server
     */
    async executeCommands(commands) {
        for (const cmd of commands) {
            switch (cmd.type) {
                case 'raw':
                    // Convert string to bytes
                    const rawBytes = [];
                    for (let i = 0; i < cmd.data.length; i++) {
                        rawBytes.push(cmd.data.charCodeAt(i));
                    }
                    await this.sendData(new Uint8Array(rawBytes));
                    break;
                case 'text':
                    await this.printText(cmd.data);
                    break;
                case 'feed':
                    await this.feed(cmd.lines || 1);
                    break;
            }
        }
    }

    /**
     * Print a complete receipt from receipt data
     */
    async printReceipt(data) {
        if (!this.connected) {
            // Fall back to browser print
            return this.browserPrint(data);
        }

        try {
            await this.init();
            
            // Header
            await this.setAlign('center');
            
            // Logo would go here if supported
            
            // Business name
            await this.setBold(true);
            await this.setDoubleSize(true);
            await this.printText(data.business.name.toUpperCase());
            await this.setDoubleSize(false);
            await this.setBold(false);
            
            await this.printText(data.business.phone);
            await this.printText(data.business.address);
            await this.printLine('=');
            
            // Receipt type
            await this.setBold(true);
            await this.printText(data.type === 'kitchen_order_ticket' ? 'KITCHEN ORDER TICKET' : 'CUSTOMER RECEIPT');
            await this.setBold(false);
            await this.printLine('=');
            
            // Order details
            await this.setAlign('left');
            await this.printRow('Date:', data.order.date);
            await this.printRow('Time:', data.order.time);
            await this.printRow('Customer:', data.order.customer);
            await this.printRow('Order Type:', data.order.order_type);
            
            if (data.order.note) {
                await this.printRow('Note:', data.order.note);
            }
            
            await this.printLine('-');
            
            // Items header
            await this.setBold(true);
            if (data.type === 'kitchen_order_ticket') {
                await this.printRow('Item', 'Qty');
            } else {
                await this.printText('Item                     Qty    Price    Total');
            }
            await this.setBold(false);
            await this.printLine('-');
            
            // Items
            for (const item of data.items) {
                if (data.type === 'kitchen_order_ticket') {
                    await this.printRow(
                        item.title.substring(0, 40),
                        item.quantity.toString()
                    );
                    if (item.kitchen_note) {
                        await this.printText('  Note: ' + item.kitchen_note);
                    }
                } else {
                    const line = this.formatItemLine(item);
                    await this.printText(line);
                }
            }
            
            await this.printLine('-');
            
            // Totals (for receipts only)
            if (data.type !== 'kitchen_order_ticket' && data.totals) {
                await this.printRow('Subtotal:', this.formatMoney(data.totals.subtotal));
                await this.setBold(true);
                await this.printRow('Total:', this.formatMoney(data.totals.total));
                await this.setBold(false);
                
                // Payment info
                if (data.payment) {
                    await this.feed(1);
                    await this.printRow('Payment:', data.payment.type.toUpperCase());
                    
                    if (data.payment.type === 'cash' && data.payment.cash_received > 0) {
                        await this.printRow('Cash Received:', this.formatMoney(data.payment.cash_received));
                        if (data.payment.change > 0) {
                            await this.printRow('Change:', this.formatMoney(data.payment.change));
                        }
                    } else if (data.payment.type === 'split') {
                        await this.printRow('Cash:', this.formatMoney(data.payment.cash_received));
                        await this.printRow('Card:', this.formatMoney(data.payment.card_amount));
                    }
                }
            }
            
            // Footer
            await this.feed(2);
            await this.setAlign('center');
            await this.printText(data.business.footer.toUpperCase());
            await this.feed(3);
            
            // Cut
            await this.cut();
            
            return { success: true };
        } catch (error) {
            console.error('Print error:', error);
            throw error;
        }
    }

    /**
     * Format item line for receipt
     */
    formatItemLine(item) {
        const title = item.title.substring(0, 24).padEnd(24);
        const qty = item.quantity.toString().padStart(4);
        const price = this.formatMoney(item.unit_price).padStart(8);
        const total = this.formatMoney(item.total).padStart(8);
        return title + qty + price + total;
    }

    /**
     * Format money value
     */
    formatMoney(value) {
        return '£' + parseFloat(value).toFixed(2);
    }

    /**
     * Browser print fallback - creates a printable HTML receipt
     */
    browserPrint(data) {
        const html = this.generateReceiptHTML(data);
        
        const printWindow = window.open('', '_blank', 'width=400,height=600');
        printWindow.document.write(html);
        printWindow.document.close();
        
        // Trigger print after content loads
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
        }, 500);
        
        return { success: true, method: 'browser' };
    }

    /**
     * Generate HTML for browser print fallback
     */
    generateReceiptHTML(data) {
        const isKOT = data.type === 'kitchen_order_ticket';
        const self = this;
        
        let itemsHTML = '';
        for (const item of data.items) {
            if (isKOT) {
                itemsHTML += '<tr>';
                itemsHTML += '<td>' + item.title + '</td>';
                itemsHTML += '<td style="text-align:right">' + item.quantity + '</td>';
                itemsHTML += '</tr>';
                if (item.kitchen_note) {
                    itemsHTML += '<tr><td colspan="2" style="padding-left:20px;font-style:italic">Note: ' + item.kitchen_note + '</td></tr>';
                }
            } else {
                itemsHTML += '<tr>';
                itemsHTML += '<td>' + item.title + '</td>';
                itemsHTML += '<td style="text-align:center">' + item.quantity + '</td>';
                itemsHTML += '<td style="text-align:right">' + self.formatMoney(item.unit_price) + '</td>';
                itemsHTML += '<td style="text-align:right">' + self.formatMoney(item.total) + '</td>';
                itemsHTML += '</tr>';
            }
        }

        // Build logo HTML
        let logoHTML = '';
        if (data.business.logo_url) {
            logoHTML = '<img src="' + data.business.logo_url + '" style="max-width:150px;max-height:80px;margin-bottom:5px">';
        }

        // Build note HTML
        let noteHTML = '';
        if (data.order.note) {
            noteHTML = '<div class="info-row"><span>Note:</span><span>' + data.order.note + '</span></div>';
        }

        // Build items header
        let itemsHeaderHTML = '';
        if (isKOT) {
            itemsHeaderHTML = '<th style="text-align:right">Qty</th>';
        } else {
            itemsHeaderHTML = '<th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Total</th>';
        }

        // Build totals HTML
        let totalsHTML = '';
        if (!isKOT && data.totals) {
            totalsHTML = '<div class="totals">';
            totalsHTML += '<div class="total-row"><span>Subtotal:</span><span>' + self.formatMoney(data.totals.subtotal) + '</span></div>';
            totalsHTML += '<div class="total-row bold"><span>Total:</span><span>' + self.formatMoney(data.totals.total) + '</span></div>';
            
            if (data.payment) {
                totalsHTML += '<div class="divider"></div>';
                totalsHTML += '<div class="total-row"><span>Payment:</span><span>' + data.payment.type.toUpperCase() + '</span></div>';
                
                if (data.payment.type === 'cash' && data.payment.cash_received > 0) {
                    totalsHTML += '<div class="total-row"><span>Cash Received:</span><span>' + self.formatMoney(data.payment.cash_received) + '</span></div>';
                    if (data.payment.change > 0) {
                        totalsHTML += '<div class="total-row"><span>Change:</span><span>' + self.formatMoney(data.payment.change) + '</span></div>';
                    }
                }
                
                if (data.payment.type === 'split') {
                    totalsHTML += '<div class="total-row"><span>Cash:</span><span>' + self.formatMoney(data.payment.cash_received) + '</span></div>';
                    totalsHTML += '<div class="total-row"><span>Card:</span><span>' + self.formatMoney(data.payment.card_amount) + '</span></div>';
                }
            }
            totalsHTML += '</div>';
        }

        // Build the complete HTML
        let html = '<!DOCTYPE html>';
        html += '<html>';
        html += '<head>';
        html += '<meta charset="UTF-8">';
        html += '<title>' + (isKOT ? 'Kitchen Order' : 'Receipt') + '</title>';
        html += '<style>';
        html += '@page { size: 80mm auto; margin: 0; }';
        html += 'body { font-family: "Courier New", monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 5mm; box-sizing: border-box; }';
        html += '.header { text-align: center; margin-bottom: 10px; }';
        html += '.business-name { font-size: 16px; font-weight: bold; }';
        html += '.divider { border-top: 1px dashed #000; margin: 8px 0; }';
        html += '.divider-bold { border-top: 2px solid #000; margin: 8px 0; }';
        html += '.receipt-type { text-align: center; font-weight: bold; font-size: 14px; }';
        html += '.info-row { display: flex; justify-content: space-between; }';
        html += 'table { width: 100%; border-collapse: collapse; }';
        html += 'th { text-align: left; border-bottom: 1px solid #000; padding: 3px 0; }';
        html += 'td { padding: 3px 0; vertical-align: top; }';
        html += '.totals { margin-top: 10px; }';
        html += '.total-row { display: flex; justify-content: space-between; }';
        html += '.total-row.bold { font-weight: bold; font-size: 14px; }';
        html += '.footer { text-align: center; margin-top: 15px; font-weight: bold; }';
        html += '@media print { body { width: 100%; } }';
        html += '</style>';
        html += '</head>';
        html += '<body>';
        html += '<div class="header">';
        html += logoHTML;
        html += '<div class="business-name">' + data.business.name.toUpperCase() + '</div>';
        html += '<div>' + data.business.phone + '</div>';
        html += '<div>' + data.business.address + '</div>';
        html += '</div>';
        html += '<div class="divider-bold"></div>';
        html += '<div class="receipt-type">' + (isKOT ? 'KITCHEN ORDER TICKET' : 'CUSTOMER RECEIPT') + '</div>';
        html += '<div class="divider-bold"></div>';
        html += '<div class="info-row"><span>Date:</span><span>' + data.order.date + '</span></div>';
        html += '<div class="info-row"><span>Time:</span><span>' + data.order.time + '</span></div>';
        html += '<div class="info-row"><span>Customer:</span><span>' + data.order.customer + '</span></div>';
        html += '<div class="info-row"><span>Order Type:</span><span>' + data.order.order_type + '</span></div>';
        html += noteHTML;
        html += '<div class="divider"></div>';
        html += '<table>';
        html += '<thead><tr><th>Item</th>' + itemsHeaderHTML + '</tr></thead>';
        html += '<tbody>' + itemsHTML + '</tbody>';
        html += '</table>';
        html += '<div class="divider"></div>';
        html += totalsHTML;
        html += '<div class="footer">' + data.business.footer.toUpperCase() + '</div>';
        html += '</body>';
        html += '</html>';

        return html;
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThermalPrinter;
}

// Also expose globally
if (typeof window !== 'undefined') {
    window.ThermalPrinter = ThermalPrinter;
}