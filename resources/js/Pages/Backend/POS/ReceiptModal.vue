<!-- components/ReceiptModal.vue -->
<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content rounded-4 shadow-lg border-0">

        <!-- Header -->
        <div class="modal-header bg-gradient" style="background: linear-gradient(90deg,#0d6efd,#4e9fff);">
          <h5 class="modal-title fw-bold">Receipt</h5>
          <button type="button" class="btn-close btn-close-white" @click="$emit('close')"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <Receipt :order="order" :money="money" />
        </div>

        <!-- Footer -->
        <div class="modal-footer d-flex justify-content-between">
          <button class="btn btn-secondary px-3 py-2" @click="$emit('close')">Cancel</button>
          <button class="btn btn-primary px-3 py-2" @click="printReceipt">
            <i class="bi bi-printer me-1"></i> Print
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import Receipt from './Receipt.vue';
import { defineProps } from 'vue';

const props = defineProps({
  show: Boolean,
  order: Object,
  money: Function,
  restaurant: Object   // ✅ add restaurant info as prop
});

const printReceipt = () => {
  const order = props.order;
  const restaurant = props.restaurant ?? {};

  const printWindow = window.open("", "_blank");
  const currentDateTime = new Date().toLocaleString("en-US", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  });

  printWindow.document.write(`
    <html>
    <head>
  <title>Receipt</title>
  <style>
  @page {
    size: 80mm auto;   /* Thermal roll width, height grows with content */
    margin: 0;
  }
  html, body {
    width: 80mm;
    margin: 0;
    padding: 4px;
    font-family: monospace, Arial, sans-serif;
    font-size: 11px;
    line-height: 1.4;
  }
  .header {
    text-align: center;
    border-bottom: 1px dashed #000;
    margin-bottom: 6px;
    padding-bottom: 4px;
  }
  .header img {
    max-width: 60px;
    margin-bottom: 4px;
  }
  .title {
    font-size: 14px;
    font-weight: bold;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
  }
  th, td {
    font-size: 11px;
    padding: 2px 0;
    text-align: left;
  }
  th {
    border-bottom: 1px solid #000;
  }
  td:last-child, th:last-child {
    text-align: right;
  }
  .totals {
    margin-top: 8px;
    border-top: 1px dashed #000;
    padding-top: 4px;
  }
  .footer {
    text-align: center;
    margin-top: 6px;
    font-size: 10px;
  }
</style>

</head>
    <body>
      <div class="header">
        ${restaurant.logo ? `<img src="${restaurant.logo}" />` : ""}
        <div class="title">${restaurant.name ?? ''}</div>
        <div>${restaurant.location ?? ''}</div>
        <div>${restaurant.email ?? ''}</div>
      </div>

      <div>
        <strong>Order ID:</strong> #${order.id}<br/>
        <strong>Date:</strong> ${order.order_date} ${order.order_time}<br/>
        <strong>Customer:</strong> ${order.customer_name ?? "Walk In"}<br/>
        <strong>Payment:</strong> ${order.payment_method}<br/>
        <strong>Type:</strong> ${order.order_type}
      </div>

      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th style="text-align:right;">Price</th>
          </tr>
        </thead>
        <tbody>
          ${order.items.map(item => `
            <tr>
              <td>${item.title}</td>
              <td>${item.quantity}</td>
              <td style="text-align:right;">${Number(item.price).toFixed(2)}</td>
            </tr>
          `).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><strong>Subtotal:</strong> ${Number(order.sub_total).toFixed(2)}</div>
        <div><strong>Total:</strong> ${Number(order.total_amount).toFixed(2)}</div>
      </div>

      <div class="footer">
        Printed: ${currentDateTime}<br/>
        Thank you for your visit!
      </div>
    </body>
    </html>
  `);

  printWindow.document.close();
  printWindow.onload = () => {
    printWindow.print();
    printWindow.close();
  };
};
</script>
