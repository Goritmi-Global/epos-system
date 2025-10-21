import{B as rt,_ as it,r as i,D as T,j as Te,k as fe,q as dt,m as v,g as l,o as r,a as $,d as S,h as ct,w as ut,b as o,i as h,n as K,F as q,v as L,t as c,f as ae,x as A,y as R,K as he,e as ge,J as pt}from"./app-COVoaSpv.js";import{_ as vt}from"./Master-CU8Kbb5A.js";import mt from"./ConfirmOrderModal-C4ciRCyN.js";import ft from"./ReceiptModal-BqIE06Xv.js";import ht from"./PromoModal-Cx9jVWGt.js";import gt from"./KotModal-DsdxcMwc.js";import{u as yt}from"./useFormatters-BC277QyT.js";import bt from"./PosOrdersModal-CMizUA51.js";import{P as _t}from"./package-Drfq-FVC.js";import"./index-BQbxzkdq.js";import"./sun-DqfGktyu.js";import"./StripePayment-Be5NqtMV.js";import"./SplitPayment-C4TD_3tQ.js";import"./Receipt-DJiyGG2y.js";import"./clock-D7izT1au.js";import"./circle-check-big-BYPVIkCb.js";import"./circle-x-58lGbV50.js";import"./printer-y-B9rvQp.js";/**
 * @license lucide-vue-next v0.525.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const wt=rt("shopping-cart",[["circle",{cx:"8",cy:"21",r:"1",key:"jimo8o"}],["circle",{cx:"19",cy:"21",r:"1",key:"13723u"}],["path",{d:"M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12",key:"9zh506"}]]),xt={class:"page-wrapper"},kt={class:"container-fluid px-3 py-3"},Ct={class:"row gx-3 gy-3"},$t={key:0,class:"row g-3"},St={key:0,class:"col-12 text-center py-5"},qt=["onClick"],Nt={class:"cat-icon-wrap"},Ft={class:"cat-icon"},Tt=["src"],It={key:1},Ot={class:"cat-name"},Pt={class:"cat-pill"},Dt={key:0,class:"col-12"},Mt={key:1},Lt={class:"d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3"},zt={class:"fw-bold mb-0"},Et={class:"search-wrap ms-auto"},jt={class:"row g-3"},At={class:"position-relative",style:{flex:"0 0 40%","max-width":"40%"}},Bt=["src"],Qt={key:0,class:"position-absolute bottom-0 start-0 end-0 m-2 badge bg-danger py-2"},Ut={class:"p-3 d-flex flex-column justify-content-between",style:{flex:"1 1 60%","min-width":"0"}},Vt={class:"text-muted mb-3 small"},Kt=["onClick"],Rt=["onClick","disabled"],Jt={class:"qty-box border rounded-pill px-4 py-2 text-center fw-semibold",style:{"min-width":"55px"}},Wt=["onClick","disabled"],Yt={key:0,class:"col-12"},Ht={key:0,class:"col-lg-4"},Xt={class:"col-lg-4 d-flex align-items-center gap-2 mb-2"},Zt={class:"cart card border-0 shadow-lg rounded-4"},Gt={class:"cart-header"},es={class:"order-type"},ts=["onClick"],ss={class:"cart-body"},os={class:"mb-3"},as={key:0,class:"row g-2"},ns={class:"col-6"},ls=["value"],rs={key:0,class:"invalid-feedback d-block"},is={class:"col-6"},ds={key:1},cs={key:0,class:"invalid-feedback d-block"},us={class:"cart-lines"},ps={key:0,class:"empty"},vs={class:"line-left"},ms=["src"],fs={class:"meta"},hs=["title"],gs={key:0,class:"note"},ys={class:"line-mid"},bs=["onClick"],_s={class:"qty"},ws=["onClick","disabled"],xs={class:"line-right"},ks={class:"price"},Cs=["onClick"],$s={class:"totals"},Ss={class:"trow"},qs={class:"sub-total"},Ns={key:0,class:"trow"},Fs={key:1,class:"trow promo-discount"},Ts={class:"d-flex align-items-center gap-2"},Is={class:"text-success"},Os={class:"trow total"},Ps={class:"mb-3"},Ds={class:"mb-3"},Ms={class:"cart-footer"},Ls={class:"modal fade",id:"chooseItem",tabindex:"-1","aria-hidden":"true"},zs={class:"modal-dialog modal-lg modal-dialog-centered"},Es={class:"modal-content rounded-4 border-0 shadow"},js={class:"modal-header border-0"},As={class:"modal-title fw-bold"},Bs={class:"modal-body"},Qs={class:"row g-3"},Us={class:"col-md-5"},Vs=["src"],Ks={class:"col-md-7"},Rs={class:"h4 mb-1"},Js={class:"chips mb-3"},Ws={key:0,class:"chip chip-orange"},Ys={key:1,class:"chip chip-green"},Hs={key:2,class:"chip chip-purple"},Xs={key:3,class:"chip chip-blue"},Zs={class:"qty-group gap-1"},Gs={class:"qty-box rounded-pill"},eo=["disabled"],to={__name:"Index",props:["client_secret","order_code"],setup(ye){const{formatCurrencySymbol:N}=yt(),z=i([]),J=i(!0),Ie=async()=>{J.value=!0;try{const t=await axios.get("/api/pos/fetch-menu-categories");z.value=t.data,z.value.length>0&&(W.value=z.value[0].id)}catch(t){console.error("Error fetching categories:",t)}finally{J.value=!1}},be=i([]),Oe=async()=>{try{const t=await axios.get("/api/pos/fetch-menu-items");be.value=t.data}catch(t){console.error("Error fetching inventory:",t)}},Pe=T(()=>{const t={};return be.value.forEach(e=>{const s=e.category?.id||"uncategorized",a=e.category?.name||"Uncategorized";t[s]||(t[s]=[]),t[s].push({id:e.id,title:e.name,img:e.image_url||"/assets/img/default.png",stock:B(e),price:Number(e.price),label_color:e.label_color||"#1B1670",family:a,description:e.description,nutrition:e.nutrition,tags:e.tags,allergies:e.allergies,ingredients:e.ingredients??[]})}),t}),De=t=>{p.value=t,x.value=0,new bootstrap.Modal(document.getElementById("chooseItem")).show()},B=t=>{if(!t)return 0;if(!t.ingredients||t.ingredients.length===0)return Number(t.stock??0);let e=1/0;return t.ingredients.forEach(s=>{const a=Number(s.quantity??s.qty??1),n=Number(s.inventory_stock??s.stock??0);if(a<=0)return;const u=Math.floor(n/a);e=Math.min(e,u)}),e===1/0&&(e=0),e},ne=i(""),f=i("dine"),F=i("Walk In"),le=i(10),W=i(null),Me=t=>W.value=t,Q=i(!0),Le=t=>{Me(t.id),Q.value=!1},ze=()=>Q.value=!0,Y=i({}),H=i([]),E=i(null),Ee=async()=>{try{const t=await axios.get("/api/pos/fetch-profile-tables");Y.value=t.data,Y.value.order_types&&(H.value=Y.value.order_types.map(e=>e.charAt(0).toUpperCase()+e.slice(1)),f.value=H.value[0])}catch(t){console.error("Error fetching profile tables:",t)}},_e=T(()=>Pe.value[W.value]??[]),we=T(()=>{const t=ne.value.trim().toLowerCase();return t?_e.value.filter(e=>e.title.toLowerCase().includes(t)||(e.family||"").toLowerCase().includes(t)||(e.description||"").toLowerCase().includes(t)||(e.tags?.map(s=>s.name.toLowerCase()).join(", ")||"").includes(t)):_e.value}),d=i([]),xe=(t,e=1,s="")=>{const a=B(t),n=d.value.findIndex(u=>u.title===t.title);if(n>=0){const u=d.value[n].qty+e;u<=d.value[n].stock?(d.value[n].qty=u,s&&(d.value[n].note=s)):v.error("Not enough Ingredients stock available for this Menu.")}else{if(e>a){v.error("Not enough Ingredients stock available for this Menu.");return}d.value.push({id:t.id,title:t.title,img:t.img,price:t.price*e||0,unit_price:Number(t.price)||0,qty:e,note:s||"",stock:a,ingredients:t.ingredients??[]})}},je=async t=>{const e=d.value[t];if(!e)return;const s={};for(const a of d.value)if(a.ingredients?.length)for(const n of a.ingredients){const u=n.inventory_item_id;s[u]||(s[u]=parseFloat(n.inventory_stock)),s[u]-=parseFloat(n.quantity)*a.qty}if(e.ingredients?.length)for(const a of e.ingredients){const n=a.inventory_item_id,u=(s[n]??parseFloat(a.inventory_stock))+parseFloat(a.quantity)*e.qty,k=parseFloat(a.quantity)*(e.qty+1);if(u<k){e.outOfStock=!0,v.error(`Not enough stock for "${e.title}".`);return}}e.outOfStock=!1,e.qty++,e.price=e.unit_price*e.qty},Ae=async t=>{const e=d.value[t];if(!e||e.qty<=1){v.error("Cannot reduce below 1.");return}try{e.qty--,e.price=e.unit_price*e.qty}catch{v.error("Failed to remove item. Please try again.")}},Be=t=>d.value.splice(t,1),I=T(()=>d.value.reduce((t,e)=>t+e.price,0)),re=T(()=>f.value==="Delivery"?I.value*le.value/100:0),X=T(()=>{const t=I.value+re.value-P.value;return Math.max(0,t)}),ke=t=>`£${(Math.round(t*100)/100).toFixed(2)}`,p=i(null),x=i(0),Qe=i(""),Ue=T(()=>B(p.value)),Ve=async()=>{if(!p.value)return;if(x.value<=0){v.error("Please add at least one item.");return}const t={};for(const e of d.value)if(e.ingredients?.length)for(const s of e.ingredients){const a=s.inventory_item_id;t[a]||(t[a]=parseFloat(s.inventory_stock)),t[a]-=parseFloat(s.quantity)*e.qty}if(p.value.ingredients?.length)for(const e of p.value.ingredients){const s=e.inventory_item_id,a=t[s]??parseFloat(e.inventory_stock),n=parseFloat(e.quantity)*x.value;if(a<n){v.error(`Not enough stock for "${p.value.title}".`);return}}try{xe(p.value,x.value,Qe.value),bootstrap.Modal.getInstance(document.getElementById("chooseItem")).hide()}catch(e){alert("Failed to add item: "+(e.response?.data?.message||e.message))}},Ke=async()=>{if(!p.value||!p.value.ingredients?.length)return;const t={};for(const e of d.value)if(e.ingredients?.length)for(const s of e.ingredients){const a=s.inventory_item_id;t[a]||(t[a]=parseFloat(s.inventory_stock)),t[a]-=parseFloat(s.quantity)*e.qty}for(const e of p.value.ingredients){const s=e.inventory_item_id,a=t[s]??parseFloat(e.inventory_stock),n=parseFloat(e.quantity)*(x.value+1);if(a<n){v.error(`Not enough stock for "${p.value.title}".`);return}}x.value++},Re=async()=>{x.value>1?x.value--:console.log("Cannot decrement: minimum quantity is 1")},C=i({}),Ce=()=>{d.value=[],F.value="Walk In",E.value=null,f.value=H.value[0]||"dine_in",U.value="",j.value="",le.value=0,b.value=null};Te(f,()=>C.value={});const U=i(""),j=i(""),ie=i(!1),V=i(null),Z=i(!1),de=i(0),Je=()=>{const t={};for(const e of d.value)if(!(!e.ingredients||e.ingredients.length===0))for(const s of e.ingredients){const a=s.inventory_item_id,n=parseFloat(s.inventory_stock),u=parseFloat(s.quantity)*e.qty;if(t[a]||(t[a]={name:s.product_name,remaining:n}),t[a].remaining>=u)t[a].remaining-=u;else return v.error(`Not enough stock for "${e.title}". Please remove it from the cart to proceed.`),!1}return!0},We=()=>{if(d.value.length===0){v.error("Please add at least one item to the cart.");return}if(f.value==="Dine_in"&&!E.value){C.value.table_number=["Table number is required for dine-in orders."],v.error("Please select a table number for Dine In orders.");return}if((f.value==="Delivery"||f.value==="Takeaway")&&!F.value){C.value.customer=["Customer name is required for delivery."],v.error("Customer name is required for delivery.");return}Je()&&(de.value=X.value,Z.value=!0)};function $e(t){const e=JSON.parse(JSON.stringify(t)),s=Number(e.cashReceived??e.cash_received??0);Number(e.cardAmount??e.cardPayment??0),Number(e.changeAmount??e.change??0);const a=(e?.payment_type||"").toLowerCase();let n="";if(a==="split"){const w=Number(e.total_amount||e.sub_total||0)-s||0;n=`Split (Cash: £${s.toFixed(2)}, Card: £${w.toFixed(2)})`}else a==="card"||a==="stripe"?n=`Card${e?.card_brand?` (${e.card_brand}`:""}${e?.last4?` •••• ${e.last4}`:""}${e?.card_brand?")":""}`:n=e?.payment_method||"Cash";const u=g.props.business_info.business_name||"Business Name",k=g.props.business_info.phone||"+4477221122",_=g.props.business_info.address||"XYZ",D=g.props.business_info.image_url||"",m=`
    <html>
    <head>
      <title>Customer Receipt</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        html, body {
          width: 78mm;
          margin: 0;
          padding: 8px 10px 8px 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
          box-sizing: border-box;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; word-break: break-word; }
        .row {
          display: flex;
          justify-content: space-between;
          margin: 2px 0;
        }
        .label { text-align: left; }
        .value { text-align: right; }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 10px 0;
          table-layout: fixed;
        }
        th, td {
          padding: 4px 2px;
          text-align: left;
          word-wrap: break-word;
        }
        th {
          border-bottom: 1px solid #000;
        }
        td:last-child, th:last-child {
          text-align: right;
          padding-right: 4px;
        }
        .totals {
          margin-top: 10px;
          border-top: 1px dashed #000;
          padding-top: 8px;
        }
        .footer {
          text-align: center;
          margin-top: 10px;
          font-size: 11px;
        }
        .totals > div {
          display: flex;
          justify-content: space-between;
          margin: 3px 0;
        }
        .header img {
          max-width: 60px;
          max-height: 60px;
          object-fit: contain;
          margin-bottom: 5px;
          border-radius: 50%;
        }
        .business-name {
          font-size: 14px;
          font-weight: bold;
          text-transform: uppercase;
        }
      </style>
    </head>
    <body>
      <div class="header">
        ${D?`<img src="${D}" alt="Logo">`:""}
        <div class="business-name">${u}</div>
        <div class="business-phone">${k}</div>
        <h2>Customer Receipt</h2>
      </div>

      <!-- 🔧 Updated section -->
      <div class="order-info">
        <div class="row"><span class="label">Date:</span><span class="value">${e.order_date||new Date().toLocaleDateString()}</span></div>
        <div class="row"><span class="label">Time:</span><span class="value">${e.order_time||new Date().toLocaleTimeString()}</span></div>
        <div class="row"><span class="label">Customer:</span><span class="value">${e.customer_name||"Walk In"}</span></div>
        <div class="row"><span class="label">Order Type:</span><span class="value">${e.order_type||"In-Store"}</span></div>
        ${e.note?`<div class="row"><span class="label">Note:</span><span class="value">${e.note}</span></div>`:""}
        
        <div class="row"><span class="label">Payment Type:</span><span class="value">${n}</span></div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30%;">Item</th>
            <th style="width: 25%;">Qty</th>
            <th style="width: 25%;">Price</th>
            <th style="width: 30%;">Total</th>
          </tr>
        </thead>
        <tbody>
          ${(e.items||[]).map(w=>{const M=Number(w.quantity)||Number(w.qty)||0,oe=M>0?(Number(w.price)||0)/M:0,lt=oe*M;return`
                <tr>
                  <td style="font-size: 12px;">${w.title||"Unknown Item"}</td>
                  <td style="font-size: 12px;">${M}</td>
                  <td style="font-size: 12px;">£${oe.toFixed(2)}</td>
                  <td style="font-size: 12px;">£${lt.toFixed(2)}</td>
                </tr>
              `}).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><span>Subtotal:</span><span>£${Number(e.sub_total||0).toFixed(2)}</span></div>
        ${e.promo_discount?`<div><span>Promo Discount:</span><span>-£${Number(e.promo_discount).toFixed(2)}</span></div>`:""}
        <div><span><strong>Total:</strong></span><span><strong>£${Number(e.total_amount||e.sub_total||0).toFixed(2)}</strong></span></div>
        ${e.cash_received?`<div><span>Cash Received:</span><span>£${Number(e.cash_received).toFixed(2)}</span></div>`:""}
        ${e.change?`<div><span>Change:</span><span>£${Number(e.change).toFixed(2)}</span></div>`:""}
      </div>
      <div class="footer">
          <div>${_}</div>
       <div>Customer Copy - Thank you for your purchase!</div>
      </div>
    </body>
    </html>
  `,y=window.open("","_blank","width=400,height=600");if(!y){alert("Please allow popups for this site to print receipts");return}y.document.open(),y.document.write(m),y.document.close(),y.onload=()=>{y.focus(),y.print(),y.onafterprint=()=>y.close()}}const Ye=i("cash"),He=i(0);function Se(t){const e=JSON.parse(JSON.stringify(t)),s=(e?.payment_method||"").toLowerCase();let a="";s==="split"?a=`Split (Cash: £${Number(e?.cash_amount??0).toFixed(2)}, Card: £${Number(e?.card_amount??0).toFixed(2)})`:s==="card"||s==="stripe"?a=`Card${e?.card_brand?` (${e.card_brand}`:""}${e?.last4?` •••• ${e.last4}`:""}${e?.card_brand?")":""}`:a=e?.payment_method||"Cash";const n=g.props.business_info.business_name||"Business Name",u=g.props.business_info.phone||"+4477221122",k=g.props.business_info.address||"XYZ",_=g.props.business_info.image_url||"",D=`
    <html>
    <head>
      <title>Kitchen Order Ticket</title>
      <style>
        @page { size: 80mm auto; margin: 0; }
        html, body {
          width: 78mm;
          margin: 0;
          padding: 8px 10px 8px 8px;
          font-family: monospace, Arial, sans-serif;
          font-size: 12px;
          line-height: 1.4;
          box-sizing: border-box;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .order-info { margin: 10px 0; word-break: break-word; }
        .row {
          display: flex;
          justify-content: space-between;
          margin: 2px 0;
        }
        .label { text-align: left; }
        .value { text-align: right; }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 10px 0;
          table-layout: fixed;
        }
        th, td {
          padding: 4px 2px;
          text-align: left;
          word-wrap: break-word;
        }
        th {
          border-bottom: 1px solid #000;
        }
        td:last-child, th:last-child {
          text-align: right;
          padding-right: 4px;
        }
        .totals {
          margin-top: 10px;
          border-top: 1px dashed #000;
          padding-top: 8px;
        }
        .footer {
          text-align: center;
          margin-top: 10px;
          font-size: 11px;
        }
        .totals > div {
          display: flex;
          justify-content: space-between;
          margin: 3px 0;
        }
        .header img {
          max-width: 60px;
          max-height: 60px;
          object-fit: contain;
          margin-bottom: 5px;
          border-radius: 50%;
        }
        .business-name {
          font-size: 14px;
          font-weight: bold;
          text-transform: uppercase;
        }
      </style>
    </head>
    <body>
      <div class="header">
        ${_?`<img src="${_}" alt="Logo">`:""}
        <div class="business-name">${n}</div>
        <div class="business-phone">${u}</div>
        <h2>KITCHEN ORDER TICKET</h2>
      </div>

      <!-- Same design as Customer Receipt -->
      <div class="order-info">
        <div class="row"><span class="label">Date:</span><span class="value">${e.order_date||new Date().toLocaleDateString()}</span></div>
        <div class="row"><span class="label">Time:</span><span class="value">${e.order_time||new Date().toLocaleTimeString()}</span></div>
        <div class="row"><span class="label">Customer:</span><span class="value">${e.customer_name||"Walk In"}</span></div>
        <div class="row"><span class="label">Order Type:</span><span class="value">${e.order_type||"In-Store"}</span></div>
        ${e.note?`<div class="row"><span class="label">Note:</span><span class="value">${e.note}</span></div>`:""}
        ${e.kitchen_note?`<div class="row"><span class="label">Kitchen Note:</span><span class="value">${e.kitchen_note}</span></div>`:""}
        <div class="row"><span class="label">Payment Type:</span><span class="value">${a}</span></div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30%;">Item</th>
            <th style="width: 25%;">Qty</th>
            <th style="width: 25%;">Price</th>
            <th style="width: 30%;">Total</th>
          </tr>
        </thead>
        <tbody>
          ${(e.items||[]).map(y=>{const w=Number(y.quantity)||Number(y.qty)||0,M=w>0?(Number(y.price)||0)/w:0,oe=M*w;return`
                <tr>
                  <td style="font-size: 12px;">${y.title||"Unknown Item"}</td>
                  <td style="font-size: 12px;">${w}</td>
                  <td style="font-size: 12px;">£${M.toFixed(2)}</td>
                  <td style="font-size: 12px;">£${oe.toFixed(2)}</td>
                </tr>
              `}).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div><span>Subtotal:</span><span>£${Number(e.sub_total||0).toFixed(2)}</span></div>
        <div><span><strong>Total:</strong></span><span><strong>£${Number(e.total_amount||0).toFixed(2)}</strong></span></div>
      </div>

      <div class="footer">
        <div>${k}</div>
        <div>Kitchen Copy - Thank you!</div>
      </div>
    </body>
    </html>
  `,m=window.open("","_blank","width=400,height=600");if(!m){alert("Please allow popups for this site to print KOT");return}m.document.open(),m.document.write(D),m.document.close(),m.onload=()=>{m.focus(),m.print(),m.onafterprint=()=>m.close()}}const Xe=async({paymentMethod:t,cashReceived:e,cardAmount:s,changeAmount:a,items:n,autoPrintKot:u,done:k})=>{C.value={};try{const _={customer_name:F.value,sub_total:I.value,promo_discount:P.value,promo_id:b.value?.id||null,promo_name:b.value?.name||null,promo_type:b.value?.type||null,total_amount:X.value,tax:0,service_charges:0,delivery_charges:re.value,note:U.value,kitchen_note:j.value,order_date:new Date().toISOString().split("T")[0],order_time:new Date().toTimeString().split(" ")[0],order_type:f.value==="Dine_in"?"Dine In":f.value==="Delivery"?"Delivery":f.value==="Takeaway"?"Takeaway":"Collection",table_number:E.value?.name||null,payment_method:t,auto_print_kot:u,cash_received:e,change:a,...t==="Split"&&{payment_type:"split",cash_amount:e,card_amount:s},items:(d.value??[]).map(m=>({product_id:m.id,title:m.title,quantity:m.qty,price:m.price,note:m.note??"",kitchen_note:j.value??"",unit_price:m.unit_price}))},D=await axios.post("/pos/order",_);Ce(),Z.value=!1,v.success(D.data.message),V.value={...D.data.order,..._,items:_.items,payment_type:t==="Split"?"split":t.toLowerCase(),cash_amount:t==="Split"?e:null,card_amount:t==="Split"?s:null},u&&(O.value=await Ne(),Se(JSON.parse(JSON.stringify(V.value)))),$e(JSON.parse(JSON.stringify(V.value))),b.value=null}catch(_){console.error("Order submission error:",_),v.error(_.response?.data?.message||"Failed to place order")}finally{k&&k()}};fe(()=>{if(window.bootstrap){document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(e=>new window.bootstrap.Tooltip(e));const t=document.getElementById("chooseItem");t&&new window.bootstrap.Modal(t,{backdrop:"static"})}Ie(),Oe(),Ee()});const g=dt();function qe(){const t=g.props.flash?.success,e=g.props.flash?.error;t&&v.success(t,{autoClose:4e3}),e&&v.error(e,{autoClose:6e3})}fe(()=>qe()),fe(()=>{g.props.flash?.success;const t=g.props.flash?.error;t&&v.error(t);const e=g.props.flash?.print_payload;e&&setTimeout(()=>$e(e),250),e&&setTimeout(()=>Se(e),250)}),Te(()=>g.props.flash,()=>qe(),{deep:!0});const Ze=({id:t,status:e,message:s})=>{O.value=O.value.map(a=>a.id===t?{...a,status:e}:a)},ce=i(!1),O=i([]),ue=i(!1),Ge=async()=>{ce.value=!0,O.value=[],ue.value=!0;try{const t=await axios.get("/api/pos/orders/today");O.value=t.data.orders}catch(t){console.error("Failed to fetch today's orders:",t),v.error(t.response?.data?.message||"Failed to fetch today's orders"),O.value=[]}finally{ue.value=!1}},G=i(!1),pe=i([]),ve=i(!1),Ne=async()=>{G.value=!0,pe.value=[],ve.value=!0;try{const t=await axios.get("/api/pos/orders/today");pe.value=t.data.orders}catch(t){console.error("Failed to fetch POS orders:",t),v.error(t.response?.data?.message||"Failed to fetch POS orders")}finally{ve.value=!1}},ee=i(!1),me=i(!0),te=i([]),b=i(null),et=t=>{b.value=t,t.min_purchase&&I.value<parseFloat(t.min_purchase)?v.warning(`Minimum purchase of ${N(t.min_purchase)} required for this promo.`):v.success(`Promo "${t.name}" applied! You saved ${N(P.value)}`),ee.value=!1},tt=async()=>{me.value=!0;try{ee.value=!0;const t=await axios.get("/api/promos/today");t.data.success?te.value=t.data.data:(console.error("Failed to fetch promos",t.data),te.value=[])}catch(t){console.error("Error fetching promos",t),te.value=[]}finally{me.value=!1}},st=t=>{V.value=t,ie.value=!0,G.value=!1},P=T(()=>{if(!b.value)return 0;const t=b.value,e=parseFloat(t.discount_amount??0)||0,s=I.value;if(t.min_purchase&&s<parseFloat(t.min_purchase))return 0;if(t.type==="flat")return e;if(t.type==="percent"){const a=s*e/100,n=parseFloat(t.max_discount??0)||0;return n>0&&a>n?n:a}return 0}),se=t=>{const e=d.value.find(s=>s.id===t.id);return e?e.qty:0},ot=t=>{const e=se(t),s=B(t);return s<=0||e>=s?!1:Fe(t)},Fe=(t,e)=>{if(!t.ingredients?.length)return!0;const s={};for(const a of d.value)if(a.ingredients?.length)for(const n of a.ingredients){const u=n.inventory_item_id;s[u]||(s[u]=parseFloat(n.inventory_stock)),s[u]-=parseFloat(n.quantity)*a.qty}for(const a of t.ingredients){const n=a.inventory_item_id,u=s[n]??parseFloat(a.inventory_stock),k=parseFloat(a.quantity);if(u<k)return!1}return!0},at=t=>{const e=se(t),s=B(t);if((t.stock??0)<=0){v.error(`${t.title} is out of stock.`);return}if(e>=s){v.error(`Not enough stock for "${t.title}".`);return}if(!Fe(t)){v.error(`Not enough ingredients for "${t.title}".`);return}const a=d.value.findIndex(n=>n.id===t.id);a>=0?(d.value[a].qty++,d.value[a].price=d.value[a].unit_price*d.value[a].qty,d.value[a].outOfStock=!1):xe(t,1,"")},nt=t=>{const e=d.value.findIndex(a=>a.id===t.id);if(e<0)return;const s=d.value[e];s.qty<=1?d.value.splice(e,1):(s.qty--,s.price=s.unit_price*s.qty,s.outOfStock=!1)};return(t,e)=>(r(),l(q,null,[$(S(ct),{title:"POS Order"}),$(vt,null,{default:ut(()=>[o("div",xt,[o("div",kt,[o("div",Ct,[o("div",{class:K(Q.value?"col-md-12":"col-lg-8")},[Q.value?(r(),l("div",$t,[e[16]||(e[16]=o("div",{class:"col-12 mb-3"},[o("h5",{class:"fw-bold text-primary mb-0"},"Menu Categories"),o("hr",{class:"mt-2 mb-3"})],-1)),J.value?(r(),l("div",St,[...e[14]||(e[14]=[o("div",{class:"spinner-border",role:"status",style:{color:"#1B1670",width:"3rem",height:"3rem","border-width":"0.3em"}},null,-1),o("div",{class:"mt-2 fw-semibold text-muted"},"Loading...",-1)])])):(r(),l(q,{key:1},[(r(!0),l(q,null,L(z.value,s=>(r(),l("div",{key:s.id,class:"col-6 col-md-4 col-lg-4"},[o("div",{class:"cat-card",onClick:a=>Le(s)},[o("div",Nt,[o("span",Ft,[s.image_url?(r(),l("img",{key:0,src:s.image_url,alt:"Category Image",class:"cat-image"},null,8,Tt)):(r(),l("span",It,"🍵"))])]),o("div",Ot,c(s.name),1),o("div",Pt,c(s.menu_items_count)+" Menu ",1)],8,qt)]))),128)),!J.value&&z.value.length===0?(r(),l("div",Dt,[...e[15]||(e[15]=[o("div",{class:"alert alert-light border text-center rounded-4"}," No categories found ",-1)])])):h("",!0)],64))])):(r(),l("div",Mt,[o("div",Lt,[o("button",{class:"btn btn-primary rounded-pill shadow-sm px-3",onClick:ze},[...e[17]||(e[17]=[o("i",{class:"bi bi-arrow-left me-1"},null,-1),ae(" Back ",-1)])]),o("h5",zt,c(z.value.find(s=>s.id===W.value)?.name||"Items"),1),o("div",Et,[e[18]||(e[18]=o("i",{class:"bi bi-search"},null,-1)),A(o("input",{"onUpdate:modelValue":e[0]||(e[0]=s=>ne.value=s),class:"form-control search-input",type:"text",placeholder:"Search items..."},null,512),[[R,ne.value]])])]),o("div",jt,[(r(!0),l(q,null,L(we.value,s=>(r(),l("div",{class:"col-12 col-md-6 col-xl-6 d-flex",key:s.title},[o("div",{class:K(["card rounded-4 shadow-sm overflow-hidden border-3 w-100 d-flex flex-row align-items-stretch",{"out-of-stock":(s.stock??0)<=0}]),style:he({borderColor:s.label_color||"#1B1670"})},[o("div",At,[o("img",{src:s.img,alt:"",class:"w-100 h-100",style:{"object-fit":"cover"}},null,8,Bt),o("span",{class:"position-absolute top-0 start-0 m-2 px-3 py-1 rounded-pill text-white small fw-semibold",style:he({background:s.label_color||"#1B1670"})},c(S(N)(s.price)),5),(s.stock??0)<=0?(r(),l("span",Qt," OUT OF STOCK ")):h("",!0)]),o("div",Ut,[o("div",null,[o("div",{class:"h5 fw-bold mb-1",style:he({color:s.label_color||"#1B1670"})},c(s.title),5),o("div",Vt,c(s.family),1),o("button",{class:"btn btn-primary mb-2",onClick:a=>De(s)}," View Details ",8,Kt)]),(s.stock??0)>0?(r(),l("div",{key:0,class:"mt-3 d-flex align-items-center justify-content-start gap-2",onClick:e[1]||(e[1]=ge(()=>{},["stop"]))},[o("button",{class:"qty-btn btn btn-outline-secondary rounded-circle px-4 py-1",style:{width:"55px",height:"36px"},onClick:ge(a=>nt(s),["stop"]),disabled:se(s)<=0},[...e[19]||(e[19]=[o("strong",null,"−",-1)])],8,Rt),o("div",Jt,c(se(s)),1),o("button",{class:"qty-btn btn btn-outline-secondary rounded-circle px-4 py-1",style:{width:"55px",height:"36px"},onClick:ge(a=>at(s),["stop"]),disabled:!ot(s)},[...e[20]||(e[20]=[o("strong",null,"+",-1)])],8,Wt)])):h("",!0)])],6)]))),128)),we.value.length===0?(r(),l("div",Yt,[...e[21]||(e[21]=[o("div",{class:"alert alert-light border text-center rounded-4"}," No items found ",-1)])])):h("",!0)])]))],2),Q.value?h("",!0):(r(),l("div",Ht,[o("div",Xt,[o("button",{class:"btn btn-primary rounded-pill d-flex align-items-center gap-2 px-4",onClick:Ge},[$(S(_t),{class:"lucide-icon",width:"16",height:"16"}),e[22]||(e[22]=ae(" KOT ",-1))]),o("button",{class:"btn btn-success rounded-pill d-flex align-items-center gap-2 px-3",onClick:Ne},[$(S(wt),{class:"lucide-icon",width:"16",height:"16"}),e[23]||(e[23]=ae(" Orders ",-1))]),o("button",{class:"btn btn-warning rounded-pill",onClick:tt}," Promos ")]),o("div",Zt,[o("div",Gt,[o("div",es,[(r(!0),l(q,null,L(H.value,(s,a)=>(r(),l("button",{key:a,class:K(["ot-pill btn",{active:f.value===s}]),onClick:n=>f.value=s},c(s.replace(/_/g," ")),11,ts))),128)),e[24]||(e[24]=o("div",{class:"d-flex justify-content-between mb-3"},null,-1))])]),o("div",ss,[o("div",os,[f.value==="Dine_in"?(r(),l("div",as,[o("div",ns,[e[26]||(e[26]=o("label",{class:"form-label small"},"Table",-1)),A(o("select",{"onUpdate:modelValue":e[2]||(e[2]=s=>E.value=s),class:"form-select form-select-sm"},[e[25]||(e[25]=o("option",{value:null},"Select Table",-1)),(r(!0),l(q,null,L(Y.value.table_details,(s,a)=>(r(),l("option",{key:a,value:s},c(s.name),9,ls))),128))],512),[[pt,E.value]]),C.value.table_number?(r(),l("div",rs,c(C.value.table_number[0]),1)):h("",!0)]),o("div",is,[e[27]||(e[27]=o("label",{class:"form-label small"},"Customer",-1)),A(o("input",{"onUpdate:modelValue":e[3]||(e[3]=s=>F.value=s),class:"form-control form-control-sm",placeholder:"Walk In"},null,512),[[R,F.value]])])])):(r(),l("div",ds,[e[28]||(e[28]=o("label",{class:"form-label small"},"Customer",-1)),A(o("input",{"onUpdate:modelValue":e[4]||(e[4]=s=>F.value=s),class:K(["form-control form-control-sm",{"is-invalid":C.value.customer}]),placeholder:"Walk In"},null,2),[[R,F.value]]),C.value.customer?(r(),l("div",cs,c(C.value.customer[0]),1)):h("",!0)]))]),o("div",us,[d.value.length===0?(r(),l("div",ps," Add items from the left ")):h("",!0),(r(!0),l(q,null,L(d.value,(s,a)=>(r(),l("div",{key:s.title,class:"line"},[o("div",vs,[o("img",{src:s.img,alt:""},null,8,ms),o("div",fs,[o("div",{class:"name",title:s.title},c(s.title),9,hs),s.note?(r(),l("div",gs,c(s.note),1)):h("",!0)])]),o("div",ys,[o("button",{class:"qty-btn",onClick:n=>Ae(a)}," − ",8,bs),o("div",_s,c(s.qty),1),o("button",{class:K(["qty-btn",[s.outOfStock||s.qty>=(s.stock??0)?"bg-secondary text-white cursor-not-allowed opacity-70":""]]),onClick:n=>je(a),disabled:s.outOfStock||s.qty>=(s.stock??0)}," + ",10,ws)]),o("div",xs,[o("div",ks,c(S(N)(s.price)),1),o("button",{class:"del",onClick:n=>Be(a)},[...e[29]||(e[29]=[o("i",{class:"bi bi-trash"},null,-1)])],8,Cs)])]))),128))]),o("div",$s,[o("div",Ss,[e[30]||(e[30]=o("span",null,"Sub Total",-1)),o("b",qs,c(S(N)(I.value)),1)]),f.value==="Delivery"?(r(),l("div",Ns,[e[31]||(e[31]=o("span",null,"Delivery",-1)),o("b",null,c(le.value)+"%",1)])):h("",!0),b.value&&P.value>0?(r(),l("div",Fs,[o("span",Ts,[e[32]||(e[32]=o("i",{class:"bi bi-tag-fill text-success"},null,-1)),ae(" Promo: "+c(b.value.name),1)]),o("b",Is,"-"+c(S(N)(P.value)),1)])):h("",!0),o("div",Os,[e[33]||(e[33]=o("span",null,"Total",-1)),o("b",null,c(S(N)(X.value)),1)])]),o("div",Ps,[e[34]||(e[34]=o("label",{for:"frontNote",class:"form-label small fw-semibold"},"Front Note",-1)),A(o("textarea",{id:"frontNote","onUpdate:modelValue":e[5]||(e[5]=s=>U.value=s),rows:"3",class:"form-control form-control-sm rounded-3",placeholder:"Enter front note..."},null,512),[[R,U.value]])]),o("div",Ds,[e[35]||(e[35]=o("label",{for:"kitchenNote",class:"form-label small fw-semibold"},"Kitchen Note",-1)),A(o("textarea",{id:"kitchenNote","onUpdate:modelValue":e[6]||(e[6]=s=>j.value=s),rows:"3",class:"form-control form-control-sm rounded-3",placeholder:"Enter kitchen note..."},null,512),[[R,j.value]])])]),o("div",Ms,[o("button",{class:"btn btn-secondary btn-clear",onClick:e[7]||(e[7]=s=>Ce())}," Clear "),o("button",{class:"btn btn-primary btn-place",onClick:We}," Place Order ")])])]))])]),o("div",Ls,[o("div",zs,[o("div",Es,[o("div",js,[o("h5",As,c(p.value?.title||"Choose Item"),1),e[36]||(e[36]=o("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110","data-bs-dismiss":"modal","aria-label":"Close",title:"Close"},[o("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[o("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})])],-1))]),o("div",Bs,[o("div",Qs,[o("div",Us,[o("img",{src:p.value?.image_url||p.value?.img||"/assets/img/product/product29.jpg",class:"img-fluid rounded-3 w-100",alt:""},null,8,Vs)]),o("div",Ks,[o("div",Rs,c(S(N)(p.value?.price||0)),1),o("div",Js,[e[37]||(e[37]=o("div",{class:"mb-1"},[o("strong",null,"Nutrition:")],-1)),p.value?.nutrition?.calories?(r(),l("span",Ws," Cal: "+c(p.value.nutrition.calories),1)):h("",!0),p.value?.nutrition?.carbs?(r(),l("span",Ys," Carbs: "+c(p.value.nutrition.carbs),1)):h("",!0),p.value?.nutrition?.fat?(r(),l("span",Hs," Fat: "+c(p.value.nutrition.fat),1)):h("",!0),p.value?.nutrition?.protein?(r(),l("span",Xs," Protein: "+c(p.value.nutrition.protein),1)):h("",!0),e[38]||(e[38]=o("div",{class:"w-100 mt-2"},[o("strong",null,"Allergies:")],-1)),(r(!0),l(q,null,L(p.value?.allergies||[],(s,a)=>(r(),l("span",{key:"a-"+a,class:"chip chip-red"},c(s.name),1))),128)),e[39]||(e[39]=o("div",{class:"w-100 mt-2"},[o("strong",null,"Tags:")],-1)),(r(!0),l(q,null,L(p.value?.tags||[],(s,a)=>(r(),l("span",{key:"t-"+a,class:"chip chip-teal"},c(s.name),1))),128))]),o("div",Zs,[o("button",{class:"qty-btn",onClick:Re}," − "),o("div",Gs,c(x.value),1),o("button",{class:"qty-btn",onClick:Ke,disabled:x.value>=Ue.value}," + ",8,eo)])])])]),o("div",{class:"modal-footer border-0"},[o("button",{class:"btn btn-primary btn-sm py-2 rounded-pill px-4",onClick:Ve}," Add to Cart ")])])])]),$(gt,{show:ce.value,kot:O.value,loading:ue.value,onClose:e[8]||(e[8]=s=>ce.value=!1),onStatusUpdated:Ze},null,8,["show","kot","loading"]),$(ft,{show:ie.value,order:V.value,money:ke,onClose:e[9]||(e[9]=s=>ie.value=!1)},null,8,["show","order"]),$(mt,{show:Z.value,customer:F.value,"order-type":f.value,"selected-table":E.value,"order-items":d.value,"grand-total":X.value,money:ke,cashReceived:de.value,"onUpdate:cashReceived":e[10]||(e[10]=s=>de.value=s),client_secret:ye.client_secret,order_code:ye.order_code,"sub-total":I.value,tax:0,"service-charges":0,"delivery-charges":re.value,"promo-discount":P.value,"promo-id":b.value?.id,"promo-name":b.value?.name,"promo-type":b.value?.type,"promo-discount-amount":P.value,note:U.value,"kitchen-note":j.value,"order-date":new Date().toISOString().split("T")[0],"order-time":new Date().toTimeString().split(" ")[0],"payment-method":Ye.value,change:He.value,onClose:e[11]||(e[11]=s=>Z.value=!1),onConfirm:Xe},null,8,["show","customer","order-type","selected-table","order-items","grand-total","cashReceived","client_secret","order_code","sub-total","delivery-charges","promo-discount","promo-id","promo-name","promo-type","promo-discount-amount","note","kitchen-note","order-date","order-time","payment-method","change"]),$(bt,{show:G.value,orders:pe.value,onClose:e[12]||(e[12]=s=>G.value=!1),onViewDetails:st,loading:ve.value},null,8,["show","orders","loading"]),$(ht,{show:ee.value,loading:me.value,promos:te.value,"order-items":d.value,onApplyPromo:et,onClose:e[13]||(e[13]=s=>ee.value=!1)},null,8,["show","loading","promos","order-items"])])]),_:1})],64))}},wo=it(to,[["__scopeId","data-v-b3ad4db4"]]);export{wo as default};
