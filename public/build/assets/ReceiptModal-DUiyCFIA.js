import l from"./Receipt-Bb4LkUzJ.js";import{g as m,l as c,b as g,f as o,e as p,p as b}from"./app-V2s1lVwu.js";const h={key:0,class:"modal fade show d-block",tabindex:"-1",style:{background:"rgba(0,0,0,0.5)"}},u={class:"modal-dialog modal-sm modal-dialog-centered"},y={class:"modal-content rounded-4 shadow-lg border-0"},x={class:"modal-header bg-gradient",style:{background:"linear-gradient(90deg,#0d6efd,#4e9fff)"}},v={class:"modal-body"},f={class:"modal-footer d-flex justify-content-between"},k={__name:"ReceiptModal",props:{show:Boolean,order:Object,money:Function,restaurant:Object},setup(i){const r=i,a=()=>{const e=r.order,t=r.restaurant??{},d=window.open("","_blank"),s=new Date().toLocaleString("en-US",{day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit",hour12:!0});d.document.write(`
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
        ${t.logo?`<img src="${t.logo}" />`:""}
        <div class="title">${t.name??""}</div>
        <div>${t.location??""}</div>
        <div>${t.email??""}</div>
      </div>

      <div>
        <strong>Order ID:</strong> #${e.id}<br/>
        <strong>Date:</strong> ${e.order_date} ${e.order_time}<br/>
        <strong>Customer:</strong> ${e.customer_name??"Walk In"}<br/>
        <strong>Payment:</strong> ${e.payment_method}<br/>
        <strong>Type:</strong> ${e.order_type}
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
          ${e.items.map(n=>`
            <tr>
              <td>${n.title}</td>
              <td>${n.quantity}</td>
              <td style="text-align:right;">${Number(n.price).toFixed(2)}</td>
            </tr>
          `).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><strong>Subtotal:</strong> ${Number(e.sub_total).toFixed(2)}</div>
        <div><strong>Total:</strong> ${Number(e.total_amount).toFixed(2)}</div>
      </div>

      <div class="footer">
        Printed: ${s}<br/>
        Thank you for your visit!
      </div>
    </body>
    </html>
  `),d.document.close(),d.onload=()=>{d.print(),d.close()}};return(e,t)=>i.show?(g(),m("div",h,[o("div",u,[o("div",y,[o("div",x,[t[2]||(t[2]=o("h5",{class:"modal-title fw-bold"},"Receipt",-1)),o("button",{type:"button",class:"btn-close btn-close-white",onClick:t[0]||(t[0]=d=>e.$emit("close"))})]),o("div",v,[p(l,{order:i.order,money:i.money},null,8,["order","money"])]),o("div",f,[o("button",{class:"btn btn-secondary px-3 py-2",onClick:t[1]||(t[1]=d=>e.$emit("close"))},"Cancel"),o("button",{class:"btn btn-primary px-3 py-2",onClick:a},[...t[3]||(t[3]=[o("i",{class:"bi bi-printer me-1"},null,-1),b(" Print ",-1)])])])])])])):c("",!0)}};export{k as default};
