import{_ as ae,r as i,k as L,p as re,D as v,g as c,o as u,a as h,d as _,h as oe,w as B,b as t,t as n,x as k,y as le,f as E,F as C,v as I,J as W,i as q,n as ne,e as de,m as M}from"./app-COVoaSpv.js";import{_ as ie}from"./Master-CU8Kbb5A.js";import{u as ce}from"./useFormatters-BC277QyT.js";import{F as ue}from"./FilterModal-TUcCt8rr.js";import{C as me}from"./clock-D7izT1au.js";import{C as pe}from"./circle-check-big-BYPVIkCb.js";import{C as ve}from"./circle-x-58lGbV50.js";import{P as be}from"./printer-y-B9rvQp.js";import"./index-BQbxzkdq.js";import"./sun-DqfGktyu.js";const he={class:"page-wrapper"},ge={class:"row g-3 mb-0"},fe={class:"col-6 col-md-3"},ye={class:"card border-0 shadow-sm rounded-4"},_e={class:"card-body d-flex align-items-center justify-content-between"},we={class:"mb-0 fw-bold"},xe={class:"col-6 col-md-3"},Ce={class:"card border-0 shadow-sm rounded-4"},De={class:"card-body d-flex align-items-center justify-content-between"},Te={class:"mb-0 fw-bold"},$e={class:"col-6 col-md-3"},Oe={class:"card border-0 shadow-sm rounded-4"},Fe={class:"card-body d-flex align-items-center justify-content-between"},ke={class:"mb-0 fw-bold"},Ie={class:"col-6 col-md-3"},Ne={class:"card border-0 shadow-sm rounded-4"},Se={class:"card-body d-flex align-items-center justify-content-between"},je={class:"mb-0 fw-bold"},Pe={class:"card border-0 shadow-lg rounded-4"},Ke={class:"card-body"},Ae={class:"d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"},Ve={class:"d-flex flex-wrap gap-2 align-items-center"},Le={class:"search-wrap"},Be={key:1,class:"form-control search-input",placeholder:"Search",disabled:"",type:"text"},Ee={class:"col-md-6"},We=["onUpdate:modelValue"],qe=["value"],Me={class:"col-md-6 mt-3"},Ue=["onUpdate:modelValue"],ze=["value"],He={class:"table-responsive"},Re={class:"table table-hover align-middle mb-0"},Je={class:"d-flex justify-content-center align-items-center gap-2"},Ze=["onClick"],Qe=["onClick"],Xe=["onClick"],Ge=["onClick"],Ye={key:0},et={__name:"Index",setup(tt){ce();const m=i([]),U=async()=>{try{const r=await axios.get("/api/kots/all-orders");m.value=(r.data.data||[]).map(e=>{const s=e.pos_order_type?.order,a=s?.items||[];return{id:s?.id||e.id,created_at:s?.created_at||e.created_at,customer_name:s?.customer_name||"Walk In",total_amount:s?.total_amount||0,sub_total:s?.sub_total||0,status:e.status||"Waiting",type:{order_type:e.pos_order_type?.order_type,table_number:e.pos_order_type?.table_number},payment:s?.payment,items:(e.items||[]).map(o=>{const l=a.find(g=>g.title===o.item_name||g.product_id===o.product_id);return{...o,title:o.item_name,price:l?.price||0,quantity:o.quantity||1,variant_name:o.variant_name||"-",ingredients:o.ingredients||[]}}),orderIndex:e.id}}),console.log("Transformed orders:",m.value)}catch(r){console.error("Error fetching orders:",r),m.value=[]}};L(async()=>{y.value="",N.value=Date.now(),await re(),setTimeout(()=>{S.value=!0;const r=document.getElementById(D);r&&(r.value="",y.value="")},100),U()});const y=i(""),N=i(Date.now()),D=`search-${Math.random().toString(36).substr(2,9)}`,S=i(!1),d=i({sortBy:"",orderType:"",status:"",dateFrom:"",dateTo:""});i("All"),i("All"),i(["All","Dine In","Delivery","Takeaway","Collection"]),i(["All","Waiting","Done","Cancelled"]);const j=v(()=>!m.value||m.value.length===0?[]:m.value.flatMap((e,s)=>e.items?.map((a,o)=>({...a,status:a.status,orderIndex:s,order:e,uniqueId:`${e.id}-${o}`}))||[])),T=v(()=>{const r=y.value.trim().toLowerCase();let e=[...j.value];return r&&(e=e.filter(s=>[String(s.order?.id),s.item_name??"",s.variant_name??"",s.ingredients?.join(", ")??"",s.status??""].join(" ").toLowerCase().includes(r))),d.value.orderType&&(e=e.filter(s=>(s.order?.type?.order_type??"").toLowerCase()===d.value.orderType.toLowerCase())),d.value.status&&(e=e.filter(s=>s.status?.toLowerCase()===d.value.status.toLowerCase())),d.value.dateFrom&&(e=e.filter(s=>{const a=new Date(s.order?.created_at),o=new Date(d.value.dateFrom);return a>=o})),d.value.dateTo&&(e=e.filter(s=>{const a=new Date(s.order?.created_at),o=new Date(d.value.dateTo);return o.setHours(23,59,59,999),a<=o})),e});v(()=>{const r=[...T.value];switch(d.value.sortBy){case"date_desc":return r.sort((s,a)=>new Date(a.order?.created_at)-new Date(s.order?.created_at));case"date_asc":return r.sort((s,a)=>new Date(s.order?.created_at)-new Date(a.order?.created_at));case"item_asc":return r.sort((s,a)=>(s.item_name||"").localeCompare(a.item_name||""));case"item_desc":return r.sort((s,a)=>(a.item_name||"").localeCompare(s.item_name||""));case"order_asc":return r.sort((s,a)=>(s.order?.id||0)-(a.order?.id||0));case"order_desc":return r.sort((s,a)=>(a.order?.id||0)-(s.order?.id||0));default:return r.sort((s,a)=>new Date(a.order?.created_at)-new Date(s.order?.created_at))}});const $=v(()=>({sortOptions:[{value:"date_desc",label:"Date: Newest First"},{value:"date_asc",label:"Date: Oldest First"},{value:"item_asc",label:"Item: A to Z"},{value:"item_desc",label:"Item: Z to A"},{value:"order_asc",label:"Order ID: Low to High"},{value:"order_desc",label:"Order ID: High to Low"}],orderTypeOptions:[{value:"Dine In",label:"Dine In"},{value:"Delivery",label:"Delivery"},{value:"Takeaway",label:"Takeaway"},{value:"Collection",label:"Collection"}],statusOptions:[{value:"Waiting",label:"Waiting"},{value:"Done",label:"Done"},{value:"Cancelled",label:"Cancelled"}]})),z=r=>{console.log("Filters applied:",r)},H=()=>{console.log("Filters cleared")},R=v(()=>new Set(m.value.map(e=>e.type?.table_number).filter(e=>e)).size),J=v(()=>j.value.length),Z=v(()=>m.value.filter(r=>r.status==="Waiting").length),Q=v(()=>m.value.filter(r=>r.status==="Cancelled").length),X=r=>{switch(r){case"Done":return"bg-success";case"Cancelled":return"bg-danger";case"Waiting":return"bg-warning text-dark";default:return"bg-secondary"}},O=async(r,e)=>{try{console.log(`Updating KOT item ID ${r.id} -> ${e}`);const s=await axios.put(`/api/pos/kot-item/${r.id}/status`,{status:e}),a=m.value.find(o=>o.id===r.order.id);if(a&&a.items){const o=a.items.find(l=>l.id===r.id);o&&(o.status=s.data.status||e,a.items=[...a.items])}M.success(`"${r.item_name}" marked as ${e}`)}catch(s){console.error("Failed to update KOT item status:",s),M.error(s.response?.data?.message||"Failed to update status")}},G=r=>{const e=JSON.parse(JSON.stringify(r)),s=e?.customer_name||"Walk-in Customer",a=e?.type?.order_type||"Dine In",o=e?.type?.table_number,l=e?.payment,g=e?.items||[],A=g.reduce((p,f)=>p+Number(f.price||0)*Number(f.quantity||0),0),Y=e?.total_amount||A,V=e?.created_at?new Date(e.created_at):new Date,ee=V.toISOString().split("T")[0],te=V.toTimeString().split(" ")[0],F=(l?.payment_method||"cash").toLowerCase();let w="";F==="split"?w=`Payment Type: Split (Cash: £${Number(l?.cash_amount??0).toFixed(2)}, Card: £${Number(l?.card_amount??0).toFixed(2)})`:F==="card"||F==="stripe"?w=`Payment Type: Card${l?.card_brand?` (${l.card_brand}`:""}${l?.last4?` •••• ${l.last4}`:""}${l?.card_brand?")":""}`:w=`Payment Type: ${l?.payment_method||"Cash"}`,(e.items||[]).map(p=>{const f=g.find(x=>x.title===p.item_name||x.product_id===p.product_id);return{...p,price:f?.price||0}});const se=`
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
        <div>KOT ID: #${e.id}</div>
        <div>Date: ${ee}</div>
        <div>Time: ${te}</div>
        <div>Customer: ${s}</div>
        <div>Order Type: ${a}</div>
        ${o?`<div>Table: ${o}</div>`:""}
        
        <div class="payment-info">
          <div>${w}</div>
        </div>
        
        <div>Status: ${e.status}</div>
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
         ${g.map(p=>{const f=Number(p.quantity)||1,x=Number(p.price)||0;return`
      <tr>
        <td>${p.title||p.item_name||"Unknown Item"}</td>
        <td>${f}</td>
        <td>£${x.toFixed(2)}</td>
      </tr>
    `}).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div>Subtotal: £${Number(A).toFixed(2)}</div>
        <div>Total: £${Number(Y).toFixed(2)}</div>
        ${l?.cash_received?`<div>Cash Received: £${Number(l.cash_received).toFixed(2)}</div>`:""}
        ${l?.change?`<div>Change: £${Number(l.change).toFixed(2)}</div>`:""}
      </div>

      <div class="footer">
        Kitchen Copy - Thank you!
      </div>
    </body>
    </html>
  `,b=window.open("","_blank","width=400,height=600");if(!b){alert("Please allow popups for this site to print KOT");return}b.document.open(),b.document.write(se),b.document.close(),b.onload=()=>{b.print(),b.close()}},P=i([]),K=i(!1);return L(async()=>{K.value=!0;try{const r=await axios.get("/api/printers");console.log("Printers:",r.data.data),P.value=r.data.data.filter(e=>e.is_connected===!0||e.status==="OK").map(e=>({label:`${e.name}`,value:e.name,driver:e.driver,port:e.port}))}catch(r){console.error("Failed to fetch printers:",r)}finally{K.value=!1}}),(r,e)=>(u(),c(C,null,[h(_(oe),{title:"Kitchen Orders"}),h(ie,null,{default:B(()=>[t("div",he,[e[21]||(e[21]=t("h4",{class:"fw-semibold mb-3"},"Kitchen Order Tickets",-1)),t("div",ge,[t("div",fe,[t("div",ye,[t("div",_e,[t("div",null,[t("h3",we,n(R.value),1),e[3]||(e[3]=t("p",{class:"text-muted mb-0 small"},"Total Tables",-1))]),e[4]||(e[4]=t("div",{class:"rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-table fs-4"})],-1))])])]),t("div",xe,[t("div",Ce,[t("div",De,[t("div",null,[t("h3",Te,n(J.value),1),e[5]||(e[5]=t("p",{class:"text-muted mb-0 small"},"Total Items",-1))]),e[6]||(e[6]=t("div",{class:"rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-basket fs-4"})],-1))])])]),t("div",$e,[t("div",Oe,[t("div",Fe,[t("div",null,[t("h3",ke,n(Z.value),1),e[7]||(e[7]=t("p",{class:"text-muted mb-0 small"},"Pending Orders",-1))]),e[8]||(e[8]=t("div",{class:"rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-hourglass-split fs-4"})],-1))])])]),t("div",Ie,[t("div",Ne,[t("div",Se,[t("div",null,[t("h3",je,n(Q.value),1),e[9]||(e[9]=t("p",{class:"text-muted mb-0 small"},"Cancelled Orders",-1))]),e[10]||(e[10]=t("div",{class:"rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-x-circle fs-4"})],-1))])])])]),t("div",Pe,[t("div",Ke,[t("div",Ae,[e[18]||(e[18]=t("h5",{class:"mb-0 fw-semibold"},"Kitchen Orders",-1)),t("div",Ve,[t("div",Le,[e[11]||(e[11]=t("i",{class:"bi bi-search"},null,-1)),e[12]||(e[12]=t("input",{type:"email",name:"email",autocomplete:"email",style:{position:"absolute",left:"-9999px",width:"1px",height:"1px"},tabindex:"-1","aria-hidden":"true"},null,-1)),S.value?k((u(),c("input",{id:D,"onUpdate:modelValue":e[0]||(e[0]=s=>y.value=s),key:N.value,class:"form-control search-input",placeholder:"Search",type:"search",autocomplete:"new-password",name:D,role:"presentation",onFocus:e[1]||(e[1]=(...s)=>r.handleFocus&&r.handleFocus(...s))},null,32)),[[le,y.value]]):(u(),c("input",Be))]),h(ue,{modelValue:d.value,"onUpdate:modelValue":e[2]||(e[2]=s=>d.value=s),title:"Kitchen Orders","modal-id":"kotFilterModal","modal-size":"modal-lg","sort-options":$.value.sortOptions,"show-date-range":!0,onApply:z,onClear:H},{customFilters:B(({filters:s})=>[t("div",Ee,[e[14]||(e[14]=t("label",{class:"form-label fw-semibold text-dark"},[t("i",{class:"fas fa-concierge-bell me-2 text-muted"}),E("Order Type ")],-1)),k(t("select",{"onUpdate:modelValue":a=>s.orderType=a,class:"form-select"},[e[13]||(e[13]=t("option",{value:""},"All",-1)),(u(!0),c(C,null,I($.value.orderTypeOptions,a=>(u(),c("option",{key:a.value,value:a.value},n(a.label),9,qe))),128))],8,We),[[W,s.orderType]])]),t("div",Me,[e[16]||(e[16]=t("label",{class:"form-label fw-semibold text-dark"},[t("i",{class:"fas fa-tasks me-2 text-muted"}),E("Order Status ")],-1)),k(t("select",{"onUpdate:modelValue":a=>s.status=a,class:"form-select"},[e[15]||(e[15]=t("option",{value:""},"All",-1)),(u(!0),c(C,null,I($.value.statusOptions,a=>(u(),c("option",{key:a.value,value:a.value},n(a.label),9,ze))),128))],8,Ue),[[W,s.status]])])]),_:1},8,["modelValue","sort-options"]),e[17]||(e[17]=t("div",{class:"dropdown"},[t("button",{class:"btn btn-outline-secondary rounded-pill px-4 dropdown-toggle","data-bs-toggle":"dropdown"}," Export "),t("ul",{class:"dropdown-menu dropdown-menu-end shadow rounded-4 py-2"},[t("li",null,[t("a",{class:"dropdown-item py-2",href:"javascript:void(0)"},"Export as PDF")]),t("li",null,[t("a",{class:"dropdown-item py-2",href:"javascript:void(0)"},"Export as Excel")])])],-1))])]),t("div",He,[t("table",Re,[e[20]||(e[20]=t("thead",{class:"border-top small text-muted"},[t("tr",null,[t("th",null,"#"),t("th",null,"Order ID"),t("th",null,"Item Name"),t("th",null,"Order Type"),t("th",null,"Ingredients"),t("th",null,"Status"),t("th",{class:"text-center"},"Actions")])],-1)),t("tbody",null,[(u(!0),c(C,null,I(T.value,(s,a)=>(u(),c("tr",{key:s.uniqueId||a},[t("td",null,n(a+1),1),t("td",null,n(s.order?.id),1),t("td",null,n(s.item_name),1),t("td",null,n(s.order?.type?.order_type||"-"),1),t("td",null,n(s.ingredients?.join(", ")||"-"),1),t("td",null,[t("span",{class:ne(["badge","rounded-pill",X(s.status)]),style:{width:"80px",display:"inline-flex","justify-content":"center","align-items":"center",height:"25px"}},n(s.status),3)]),t("td",null,[t("div",Je,[t("button",{onClick:o=>O(s,"Waiting"),title:"Waiting",class:"p-2 rounded-full text-warning hover:bg-gray-100"},[h(_(me),{class:"w-5 h-5"})],8,Ze),t("button",{onClick:o=>O(s,"Done"),title:"Done",class:"p-2 rounded-full text-success hover:bg-gray-100"},[h(_(pe),{class:"w-5 h-5"})],8,Qe),t("button",{onClick:o=>O(s,"Cancelled"),title:"Cancelled",class:"p-2 rounded-full text-danger hover:bg-gray-100"},[h(_(ve),{class:"w-5 h-5"})],8,Xe),P.value.length>0?(u(),c("button",{key:0,class:"p-2 rounded-full text-gray-600 hover:bg-gray-100",onClick:de(o=>G(s.order),["prevent"]),title:"Print"},[h(_(be),{class:"w-5 h-5"})],8,Ge)):q("",!0)])])]))),128)),T.value.length===0?(u(),c("tr",Ye,[...e[19]||(e[19]=[t("td",{colspan:"7",class:"text-center text-muted py-4"}," No orders found. ",-1)])])):q("",!0)])])])])])])]),_:1})],64))}},vt=ae(et,[["__scopeId","data-v-b64b10a9"]]);export{vt as default};
