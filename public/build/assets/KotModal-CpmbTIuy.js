import{c as w,r as q,o as E,w as V,g as c,b as u,f as e,p as W,t as r,m as z,v as A,F as R,i as J,j as Q,e as b,u as y,y as U,q as H,s as T}from"./app-BQcLyXae.js";import{C as X}from"./clock-BJdwgiEB.js";import{C as G}from"./circle-check-big-Q7fjXgph.js";import{C as Y}from"./circle-x-BBd6AacI.js";import{P as Z}from"./printer-BOkH_Ny_.js";import"./createLucideIcon-Coc_9-2_.js";const tt={class:"modal fade",id:"kotModal",tabindex:"-1","aria-hidden":"true"},et={class:"modal-dialog modal-lg modal-dialog-centered"},ot={class:"modal-content rounded-4 border-0"},st={class:"modal-header position-relative text-black d-flex align-items-center"},nt={class:"modal-title mb-0 d-flex align-items-center"},at={class:"badge bg-primary rounded-pill ms-2 px-2 py-1"},it={class:"position-absolute start-50 translate-middle-x",style:{width:"250px"}},dt={class:"modal-body"},rt={key:0,class:"d-flex flex-column justify-content-center align-items-center",style:{height:"200px"}},lt={key:1,class:"d-flex flex-column justify-content-center align-items-center",style:{height:"200px"}},ct={key:2,class:"table-responsive"},ut={class:"table table-bordered"},pt={class:"d-flex justify-content-center align-items-center gap-2"},mt=["onClick"],ht=["onClick"],gt=["onClick"],vt=["onClick"],bt={class:"modal-footer"},Ct={__name:"KotModal",props:{show:Boolean,kot:Array,loading:Boolean},emits:["close","status-updated"],setup(p,{emit:N}){const l=p,I=w(()=>l.kot?.length||0),O=N,C=w(()=>!l.kot||l.kot.length===0?[]:l.kot.flatMap((t,o)=>t.items?.map((a,s)=>({...a,orderIndex:o,order:t,uniqueId:`${t.id}-${s}`}))||[])),m=q(""),L=w(()=>{if(!m.value)return C.value;const n=m.value.toLowerCase();return C.value.filter(t=>t.item_name.toLowerCase().includes(n)||t.variant_name?.toLowerCase().includes(n)||t.ingredients?.join(", ").toLowerCase().includes(n)||t.status.toLowerCase().includes(n))}),f=async(n,t)=>{try{const o=await H.put(`/api/pos/kot-item/${n.id}/status`,{status:t}),a=l.kot.find(s=>s.id===n.order.id);if(a){const s=a.items.find(x=>x.id===n.id);s&&(s.status=o.data.status||t)}T.success(`"${n.item_name}" marked as ${t}`),O("status-updated",{id:n.id,status:t})}catch(o){console.error(o),T.error(o.response?.data?.message||"Failed to update status")}},D=n=>{switch(n){case"Done":return"bg-success";case"Cancelled":return"bg-danger";case"Waiting":return"bg-warning text-dark";default:return"bg-secondary"}};let h=null;E(()=>{const n=document.getElementById("kotModal");h=new bootstrap.Modal(n,{backdrop:"static",keyboard:!1})}),V(()=>l.show,n=>{h&&(n?h.show():h.hide())});const F=n=>{const t=JSON.parse(JSON.stringify(n)),o=t?.pos_order_type?.order,a=t?.pos_order_type,s=o?.payment,x=o?.items||[],K=o?.customer_name||"Walk-in Customer",M=a?.order_type||"Dine In",$=a?.table_number,P=o?.sub_total||0,S=o?.total_amount||0,_=(s?.payment_method||"cash").toLowerCase();let g="";_==="split"?g=`Payment Type: Split 
      (Cash: £${Number(s?.cash_amount??0).toFixed(2)}, 
       Card: £${Number(s?.card_amount??0).toFixed(2)})`:_==="card"||_==="stripe"?g=`Payment Type: Card${s?.card_brand?` (${s.card_brand}`:""}${s?.last4?` •••• ${s.last4}`:""}${s?.card_brand?")":""}`:g=`Payment Type: ${s?.payment_method||"Cash"}`;const j=(t.items||[]).map(d=>{const k=x.find(v=>v.title===d.item_name||v.product_id===d.product_id);return{...d,price:k?.price||0}}),B=`
    <html>
    <head>
      <title>Kitchen Order Ticket</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        html, body {
          width: 80mm;
          margin: 0;
          padding: 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; }
        .order-info div { margin-bottom: 3px; }
        .payment-info { margin-top: 8px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 4px 0; text-align: left; }
        th { border-bottom: 1px solid #000; }
        td:last-child, th:last-child { text-align: right; }
        .totals { margin-top: 10px; border-top: 1px dashed #000; padding-top: 8px; }
        .footer { text-align: center; margin-top: 10px; font-size: 11px; }
      </style>
    </head>
    <body>
      <div class="header">
        <h2>KITCHEN ORDER TICKET</h2>
      </div>
      
      <div class="order-info">
        <div>KOT ID: #${t.id}</div>
        <div>Date: ${t.order_date}</div>
        <div>Time: ${t.order_time}</div>
        <div>Customer: ${K}</div>
        <div>Order Type: ${M}</div>
        ${$?`<div>Table: ${$}</div>`:""}
        
        <div class="payment-info">
          <div>${g}</div>
        </div>
        
        <div>Status: ${t.status}</div>
        ${t.note?`<div>Note: ${t.note}</div>`:""}
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
         ${j.map(d=>{const k=Number(d.quantity)||1,v=Number(d.price)||0;return`
      <tr>
        <td>${d.item_name||"Unknown Item"}</td>
        <td>${k}</td>
        <td>£${v.toFixed(2)}</td>
      </tr>
    `}).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div>Subtotal: £${Number(P).toFixed(2)}</div>
        <div>Total: £${Number(S).toFixed(2)}</div>
        ${s?.cash_received?`<div>Cash Received: £${Number(s.cash_received).toFixed(2)}</div>`:""}
        ${s?.change?`<div>Change: £${Number(s.change).toFixed(2)}</div>`:""}
      </div>

      <div class="footer">
        Kitchen Copy - Thank you!
      </div>
    </body>
    </html>
  `,i=window.open("","_blank","width=400,height=600");if(!i){alert("Please allow popups for this site to print KOT");return}i.document.open(),i.document.write(B),i.document.close(),i.onload=()=>{i.print(),i.close()}};return(n,t)=>(u(),c("div",tt,[e("div",et,[e("div",ot,[e("div",st,[e("h5",nt,[t[3]||(t[3]=W(" Kitchen Order Ticket ",-1)),e("span",at,r(I.value),1)]),e("div",it,[t[4]||(t[4]=e("i",{class:"bi bi-search position-absolute top-50 translate-middle-y ms-3"},null,-1)),z(e("input",{"onUpdate:modelValue":t[0]||(t[0]=o=>m.value=o),type:"text",class:"form-control rounded-pill ps-5",placeholder:"Search items...",style:{width:"100%",height:"38px"}},null,512),[[A,m.value]])]),e("button",{class:"ms-auto p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110","data-bs-dismiss":"modal","aria-label":"Close",title:"Close",onClick:t[1]||(t[1]=o=>n.$emit("close"))},[...t[5]||(t[5]=[e("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])])]),e("div",dt,[p.loading?(u(),c("div",rt,[...t[6]||(t[6]=[e("div",{class:"spinner-border text-primary",role:"status",style:{width:"3rem",height:"3rem"}},[e("span",{class:"visually-hidden"},"Loading...")],-1),e("div",{class:"mt-3 fw-bold"},"Loading today KOT orders...",-1)])])):!p.kot||p.kot.length===0?(u(),c("div",lt,[...t[7]||(t[7]=[e("div",{class:"fw-bold text-secondary"},"No orders found for today",-1)])])):(u(),c("div",ct,[e("table",ut,[t[8]||(t[8]=e("thead",null,[e("tr",null,[e("th",null,"#"),e("th",null,"Order ID"),e("th",null,"Item Name"),e("th",null,"Variant"),e("th",null,"Ingredients"),e("th",null,"Status"),e("th",{class:"text-center"},"Action")])],-1)),e("tbody",null,[(u(!0),c(R,null,J(L.value,(o,a)=>(u(),c("tr",{key:o.uniqueId||a},[e("td",null,r(a+1),1),e("td",null,r(o.orderIndex+1),1),e("td",null,r(o.item_name),1),e("td",null,r(o.variant_name||"-"),1),e("td",null,r(o.ingredients?.join(", ")||"-"),1),e("td",null,[e("span",{class:Q(["badge","rounded-pill",D(o.status)]),style:{width:"70px",display:"inline-flex","justify-content":"center","align-items":"center",height:"25px"}},r(o.status),3)]),e("td",null,[e("div",pt,[e("button",{onClick:s=>f(o,"Waiting"),title:"Waiting",class:"p-2 rounded-full text-warning hover:bg-gray-100"},[b(y(X),{class:"w-5 h-5"})],8,mt),e("button",{onClick:s=>f(o,"Done"),title:"Done",class:"p-2 rounded-full text-success hover:bg-gray-100"},[b(y(G),{class:"w-5 h-5"})],8,ht),e("button",{onClick:s=>f(o,"Cancelled"),title:"Cancelled",class:"p-2 rounded-full text-danger hover:bg-gray-100"},[b(y(Y),{class:"w-5 h-5"})],8,gt),e("button",{class:"p-2 rounded-full text-gray-600 hover:bg-gray-100",onClick:U(s=>F(o.order),["prevent"]),title:"Print"},[b(y(Z),{class:"w-5 h-5"})],8,vt)])])]))),128))])])]))]),e("div",bt,[e("button",{class:"btn btn-secondary rounded-pill py-2",onClick:t[2]||(t[2]=o=>n.$emit("close"))},"Close")])])])]))}};export{Ct as default};
