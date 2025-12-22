import{_ as f,w as x,g as r,b as n,f as t,F as y,i as _,l as w,t as d,j as k,e as C,u as $}from"./app-CCSEjIT4.js";import{P as O}from"./printer-Bj-hunYa.js";import"./createLucideIcon-bhPlb3RE.js";const P={class:"modal fade",id:"posOrdersModal",tabindex:"-1","aria-hidden":"true"},T={class:"modal-dialog modal-xl modal-dialog-scrollable"},N={class:"modal-content"},j={class:"modal-header"},L={class:"modal-body"},F={key:0,class:"d-flex flex-column justify-content-center align-items-center",style:{height:"200px"}},I={key:1,class:"d-flex flex-column justify-content-center align-items-center",style:{height:"200px"}},B={key:2,class:"row g-3"},M={class:"card shadow-sm h-100 border rounded-3",style:{"border-color":"#e5e7eb"}},E={class:"card-body"},z={class:"d-flex justify-content-between align-items-start mb-3 border-bottom pb-2"},R={class:"mb-0 fw-bold"},S={class:"text-muted"},q={class:"text-end"},D={class:"badge bg-secondary rounded-pill d-block mb-1"},U={key:0,class:"mb-3"},V={class:"table table-sm align-middle mb-0"},A={class:"text-center"},J={class:"text-end"},Q={class:"bg-light rounded p-2 mb-3"},W={class:"d-flex justify-content-between"},G={class:"d-flex justify-content-between"},H={class:"text-end text-muted small"},K={class:"d-flex justify-content-center"},X=["onClick"],Y={__name:"PosOrdersModal",props:{show:Boolean,orders:Array,loading:Boolean},emits:["close","view-details"],setup(l,{emit:Z}){const h=l,c=a=>parseFloat(a).toFixed(2);function u(a){const e=JSON.parse(JSON.stringify(a)),s=(e.pos_order_type?.order?.payment?.payment_type||"").toLowerCase();let o="";s==="split"?o=`Payment Type: Split 
      (Cash: £${Number(e.pos_order_type.order.payment.cash_amount??0).toFixed(2)}, 
       Card: £${Number(e.pos_order_type.order.payment.card_amount??0).toFixed(2)})`:s==="card"||s==="stripe"?o="Payment Type: Card":o=`Payment Type: ${e.pos_order_type.order.payment.payment_type||"Cash"}`;const b=`
    <html>
    <head>
      <title>Today Order Receipt</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        body {
          width: 80mm;
          margin: 0;
          padding: 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 4px 0; text-align: left; }
        td:last-child, th:last-child { text-align: right; }
        .totals { margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }
        .footer { text-align: center; margin-top: 10px; font-size: 11px; }
      </style>
    </head>
    <body>
      <div class="header">
        <h2>ORDER RECEIPT</h2>
      </div>
      
      <div class="order-info">
        <div><strong>Order #:</strong> ${e.id}</div>
        <div><strong>Date:</strong> ${e.order_date||""}</div>
        <div><strong>Time:</strong> ${e.order_time||""}</div>
        <div><strong>Customer:</strong> ${e.customer_name||"Walk In"}</div>
        <div><strong>Order Type:</strong> ${e.pos_order_type?.order_type||""}</div>
        <div>${o}</div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>
          ${(e.pos_order_type?.order?.items||[]).map(p=>{const m=Number(p.quantity)||0,g=Number(p.price)||0,v=m*g;return`
                <tr>
                  <td>${p.title||"Unknown Item"}</td>
                  <td>${m}</td>
                  <td>£${v.toFixed(2)}</td>
                </tr>
              `}).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><strong>Total:</strong> £${Number(e.pos_order_type?.order?.payment?.amount_received??0).toFixed(2)}</div>
        
      </div>

      <div class="footer">
        Customer Copy - Thank you!
      </div>
    </body>
    </html>
  `,i=window.open("","_blank","width=400,height=600");if(!i){alert("Please allow popups for this site to print");return}i.document.open(),i.document.write(b),i.document.close(),i.onload=()=>{i.print(),i.close()}}return x(()=>h.show,a=>{const e=document.getElementById("posOrdersModal");if(!e)return;const s=new bootstrap.Modal(e,{backdrop:"static",keyboard:!1});a?s.show():s.hide()}),(a,e)=>(n(),r("div",P,[t("div",T,[t("div",N,[t("div",j,[e[2]||(e[2]=t("h5",{class:"modal-title"},"Today's Orders",-1)),t("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110","data-bs-dismiss":"modal","aria-label":"Close",title:"Close",onClick:e[0]||(e[0]=s=>a.$emit("close"))},[...e[1]||(e[1]=[t("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[t("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])])]),t("div",L,[l.loading?(n(),r("div",F,[...e[3]||(e[3]=[t("div",{class:"spinner-border text-primary",role:"status",style:{width:"3rem",height:"3rem"}},[t("span",{class:"visually-hidden"},"Loading...")],-1),t("div",{class:"mt-3 fw-bold"},"Loading today orders...",-1)])])):!l.orders||l.orders.length===0?(n(),r("div",I,[...e[4]||(e[4]=[t("div",{class:"fw-bold text-secondary"},"No orders found for today",-1)])])):(n(),r("div",B,[(n(!0),r(y,null,_(l.orders,s=>(n(),r("div",{class:"col-md-6 col-lg-4",key:s.id},[t("div",M,[t("div",E,[t("div",z,[t("div",null,[t("h6",R,"Order #"+d(s.id),1),t("small",S,d(s.pos_order_type.order_type==="Collection"?"Walk In":s.customer_name),1)]),t("div",q,[t("span",D,d(s.pos_order_type.order_type),1),t("span",{class:k(["badge px-3 py-1 d-block rounded-pill",{"bg-success":s.pos_order_type.order.status?.toLowerCase()==="paid","bg-warning text-dark":s.pos_order_type.order.status?.toLowerCase()==="waiting","bg-danger":s.pos_order_type.order.status?.toLowerCase()==="cancelled","bg-secondary":!s.pos_order_type.order.status}])},d(s.pos_order_type.order.status||"Unknown"),3)])]),s.pos_order_type.order.items&&s.pos_order_type.order.items.length>0?(n(),r("div",U,[t("table",V,[e[5]||(e[5]=t("thead",{class:"table-light"},[t("tr",null,[t("th",null,"Item"),t("th",{class:"text-center"},"Qty"),t("th",{class:"text-end"},"Unit Price")])],-1)),t("tbody",null,[(n(!0),r(y,null,_(s.pos_order_type.order.items,o=>(n(),r("tr",{key:o.id},[t("td",null,d(o.title),1),t("td",A,d(o.quantity),1),t("td",J,"£"+d(c(o.price)),1)]))),128))])])])):w("",!0),t("div",Q,[t("div",W,[e[6]||(e[6]=t("span",{class:"fw-bold"},"Total Price:",-1)),t("span",null,"£"+d(c(s.pos_order_type.order.payment.amount_received)),1)]),t("div",G,[e[7]||(e[7]=t("span",{class:"fw-bold"},"Payment Type:",-1)),t("span",null,d(s.pos_order_type.order.payment.payment_type),1)]),t("div",H,"Order Time: "+d(s.order_time),1)]),t("div",K,[t("button",{class:"btn btn-sm btn-primary",onClick:o=>u(s)},[C($(O),{class:"w-5 h-5"})],8,X)])])])]))),128))]))])])])]))}},ot=f(Y,[["__scopeId","data-v-5bb1c0e8"]]);export{ot as default};
