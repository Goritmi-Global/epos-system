import{_ as te,r as i,o as V,n as se,c as p,f as c,b as u,d as b,u as g,h as ae,w as L,e as t,t as n,k,v as re,l as B,F as x,g as F,T as E,m as W,i as le,x as oe,q}from"./app-CRMeeo4o.js";import{_ as ne}from"./Master-BygUFfBq.js";import{u as de}from"./useFormatters-YIkBxsX5.js";import{F as ie}from"./FilterModal-BiuGIypv.js";import{C as ce}from"./clock-CXh-6uW5.js";import{C as ue}from"./circle-check-big-DvNfvfd6.js";import{C as me}from"./circle-x-rA9M71o1.js";import{P as pe}from"./printer-grZKD3_t.js";import"./index-CZZmbNM-.js";import"./createLucideIcon-Dj58dWNk.js";import"./index-CX6W2J1w.js";import"./index-XRMo0knu.js";import"./sun-C2aSHeNn.js";const ve={class:"page-wrapper"},be={class:"row g-4"},he={class:"col-md-6 col-xl-3"},ye={class:"card border-0 shadow-sm rounded-4"},fe={class:"card-body d-flex align-items-center justify-content-between"},ge={class:"mb-0 fw-bold"},_e={class:"col-md-6 col-xl-3"},we={class:"card border-0 shadow-sm rounded-4"},xe={class:"card-body d-flex align-items-center justify-content-between"},Ce={class:"mb-0 fw-bold"},Te={class:"col-md-6 col-xl-3"},De={class:"card border-0 shadow-sm rounded-4"},$e={class:"card-body d-flex align-items-center justify-content-between"},Oe={class:"mb-0 fw-bold"},ke={class:"col-md-6 col-xl-3"},Fe={class:"card border-0 shadow-sm rounded-4"},Ie={class:"card-body d-flex align-items-center justify-content-between"},Ne={class:"mb-0 fw-bold"},Se={class:"card border-0 shadow-lg rounded-4"},je={class:"card-body"},Ke={class:"d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"},Pe={class:"d-flex flex-wrap gap-2 align-items-center"},Ae={class:"search-wrap"},Ve={key:1,class:"form-control search-input",placeholder:"Search",disabled:"",type:"text"},Le={class:"col-md-6"},Be=["onUpdate:modelValue"],Ee=["value"],We={class:"col-md-6 mt-3"},qe=["onUpdate:modelValue"],Me=["value"],Ue={class:"table-responsive"},ze={class:"table table-hover align-middle mb-0"},He={class:"d-flex justify-content-center align-items-center gap-2"},Re=["onClick"],Je=["onClick"],Ze=["onClick"],Qe=["onClick"],Xe={key:0},Ge={__name:"Index",setup(Ye){de();const m=i([]),M=async()=>{try{const r=await axios.get("/api/kots/all-orders");m.value=(r.data.data||[]).map(e=>{const s=e.pos_order_type?.order,a=s?.items||[];return{id:s?.id||e.id,created_at:s?.created_at||e.created_at,customer_name:s?.customer_name||"Walk In",total_amount:s?.total_amount||0,sub_total:s?.sub_total||0,status:e.status||"Waiting",type:{order_type:e.pos_order_type?.order_type,table_number:e.pos_order_type?.table_number},payment:s?.payment,items:(e.items||[]).map(l=>{const o=a.find(f=>f.title===l.item_name||f.product_id===l.product_id);return{...l,title:l.item_name,price:o?.price||0,quantity:l.quantity||1,variant_name:l.variant_name||"-",ingredients:l.ingredients||[]}}),orderIndex:e.id}})}catch(r){console.error("Error fetching orders:",r),m.value=[]}};V(async()=>{y.value="",I.value=Date.now(),await se(),setTimeout(()=>{N.value=!0;const r=document.getElementById(C);r&&(r.value="",y.value="")},100),M()});const y=i(""),I=i(Date.now()),C=`search-${Math.random().toString(36).substr(2,9)}`,N=i(!1),d=i({sortBy:"",orderType:"",status:"",dateFrom:"",dateTo:""});i("All"),i("All"),i(["All","Dine In","Delivery","Takeaway","Collection"]),i(["All","Waiting","Done","Cancelled"]);const S=p(()=>!m.value||m.value.length===0?[]:m.value.flatMap((e,s)=>e.items?.map((a,l)=>({...a,status:a.status,orderIndex:s,order:e,uniqueId:`${e.id}-${l}`}))||[])),T=p(()=>{const r=y.value.trim().toLowerCase();let e=[...S.value];return r&&(e=e.filter(s=>[String(s.order?.id),s.item_name??"",s.variant_name??"",s.ingredients?.join(", ")??"",s.status??""].join(" ").toLowerCase().includes(r))),d.value.orderType&&(e=e.filter(s=>(s.order?.type?.order_type??"").toLowerCase()===d.value.orderType.toLowerCase())),d.value.status&&(e=e.filter(s=>s.status?.toLowerCase()===d.value.status.toLowerCase())),d.value.dateFrom&&(e=e.filter(s=>{const a=new Date(s.order?.created_at),l=new Date(d.value.dateFrom);return a>=l})),d.value.dateTo&&(e=e.filter(s=>{const a=new Date(s.order?.created_at),l=new Date(d.value.dateTo);return l.setHours(23,59,59,999),a<=l})),e});p(()=>{const r=[...T.value];switch(d.value.sortBy){case"date_desc":return r.sort((s,a)=>new Date(a.order?.created_at)-new Date(s.order?.created_at));case"date_asc":return r.sort((s,a)=>new Date(s.order?.created_at)-new Date(a.order?.created_at));case"item_asc":return r.sort((s,a)=>(s.item_name||"").localeCompare(a.item_name||""));case"item_desc":return r.sort((s,a)=>(a.item_name||"").localeCompare(s.item_name||""));case"order_asc":return r.sort((s,a)=>(s.order?.id||0)-(a.order?.id||0));case"order_desc":return r.sort((s,a)=>(a.order?.id||0)-(s.order?.id||0));default:return r.sort((s,a)=>new Date(a.order?.created_at)-new Date(s.order?.created_at))}});const D=p(()=>({sortOptions:[{value:"date_desc",label:"Date: Newest First"},{value:"date_asc",label:"Date: Oldest First"},{value:"item_asc",label:"Item: A to Z"},{value:"item_desc",label:"Item: Z to A"},{value:"order_asc",label:"Order ID: Low to High"},{value:"order_desc",label:"Order ID: High to Low"}],orderTypeOptions:[{value:"Dine In",label:"Dine In"},{value:"Delivery",label:"Delivery"},{value:"Takeaway",label:"Takeaway"},{value:"Collection",label:"Collection"}],statusOptions:[{value:"Waiting",label:"Waiting"},{value:"Done",label:"Done"},{value:"Cancelled",label:"Cancelled"}]})),U=p(()=>new Set(m.value.map(e=>e.type?.table_number).filter(e=>e)).size),z=p(()=>S.value.length),H=p(()=>m.value.filter(r=>r.status==="Waiting").length),R=p(()=>m.value.filter(r=>r.status==="Cancelled").length),J=r=>{switch(r){case"Done":return"bg-success";case"Cancelled":return"bg-danger";case"Waiting":return"bg-warning text-dark";default:return"bg-secondary"}},$=async(r,e)=>{try{console.log(`Updating KOT item ID ${r.id} -> ${e}`);const s=await axios.put(`/api/pos/kot-item/${r.id}/status`,{status:e}),a=m.value.find(l=>l.id===r.order.id);if(a&&a.items){const l=a.items.find(o=>o.id===r.id);l&&(l.status=s.data.status||e,a.items=[...a.items])}q.success(`"${r.item_name}" marked as ${e}`)}catch(s){console.error("Failed to update KOT item status:",s),q.error(s.response?.data?.message||"Failed to update status")}},Z=r=>{const e=JSON.parse(JSON.stringify(r)),s=e?.customer_name||"Walk-in Customer",a=e?.type?.order_type||"Dine In",l=e?.type?.table_number,o=e?.payment,f=e?.items||[],P=f.reduce((h,w)=>h+Number(w.price||0)*Number(w.quantity||0),0),Q=e?.total_amount||P,A=e?.created_at?new Date(e.created_at):new Date,X=A.toISOString().split("T")[0],G=A.toTimeString().split(" ")[0],O=(o?.payment_method||"cash").toLowerCase();let _="";O==="split"?_=`Payment Type: Split (Cash: £${Number(o?.cash_amount??0).toFixed(2)}, Card: £${Number(o?.card_amount??0).toFixed(2)})`:O==="card"||O==="stripe"?_=`Payment Type: Card${o?.card_brand?` (${o.card_brand}`:""}${o?.last4?` •••• ${o.last4}`:""}${o?.card_brand?")":""}`:_=`Payment Type: ${o?.payment_method||"Cash"}`;const Y=`
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
        <div>Date: ${X}</div>
        <div>Time: ${G}</div>
        <div>Customer: ${s}</div>
        <div>Order Type: ${a}</div>
        ${l?`<div>Table: ${l}</div>`:""}
        
        <div class="payment-info">
          <div>${_}</div>
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
         ${f.map(h=>{const w=Number(h.quantity)||1,ee=Number(h.price)||0;return`
      <tr>
        <td>${h.title||h.item_name||"Unknown Item"}</td>
        <td>${w}</td>
        <td>£${ee.toFixed(2)}</td>
      </tr>
    `}).join("")}
        </tbody>
      </table>

      <div class="totals">
        <div>Subtotal: £${Number(P).toFixed(2)}</div>
        <div>Total: £${Number(Q).toFixed(2)}</div>
        ${o?.cash_received?`<div>Cash Received: £${Number(o.cash_received).toFixed(2)}</div>`:""}
        ${o?.change?`<div>Change: £${Number(o.change).toFixed(2)}</div>`:""}
      </div>

      <div class="footer">
        Kitchen Copy - Thank you!
      </div>
    </body>
    </html>
  `,v=window.open("","_blank","width=400,height=600");if(!v){alert("Please allow popups for this site to print KOT");return}v.document.open(),v.document.write(Y),v.document.close(),v.onload=()=>{v.print(),v.close()}},j=i([]),K=i(!1);return V(async()=>{K.value=!0;try{const r=await axios.get("/api/printers");console.log("Printers:",r.data.data),j.value=r.data.data.filter(e=>e.is_connected===!0||e.status==="OK").map(e=>({label:`${e.name}`,value:e.name,driver:e.driver,port:e.port}))}catch(r){console.error("Failed to fetch printers:",r)}finally{K.value=!1}}),(r,e)=>(u(),c(x,null,[b(g(ae),{title:"Kitchen Orders"}),b(ne,null,{default:L(()=>[t("div",ve,[e[21]||(e[21]=t("h4",{class:"fw-semibold mb-3"},"Kitchen Order Tickets",-1)),t("div",be,[t("div",he,[t("div",ye,[t("div",fe,[t("div",null,[t("h3",ge,n(U.value),1),e[3]||(e[3]=t("p",{class:"text-muted mb-0 small"},"Total Tables",-1))]),e[4]||(e[4]=t("div",{class:"rounded-circle p-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-table fs-4"})],-1))])])]),t("div",_e,[t("div",we,[t("div",xe,[t("div",null,[t("h3",Ce,n(z.value),1),e[5]||(e[5]=t("p",{class:"text-muted mb-0 small"},"Total Items",-1))]),e[6]||(e[6]=t("div",{class:"rounded-circle p-3 bg-success-subtle text-success d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-basket fs-4"})],-1))])])]),t("div",Te,[t("div",De,[t("div",$e,[t("div",null,[t("h3",Oe,n(H.value),1),e[7]||(e[7]=t("p",{class:"text-muted mb-0 small"},"Pending Orders",-1))]),e[8]||(e[8]=t("div",{class:"rounded-circle p-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-hourglass-split fs-4"})],-1))])])]),t("div",ke,[t("div",Fe,[t("div",Ie,[t("div",null,[t("h3",Ne,n(R.value),1),e[9]||(e[9]=t("p",{class:"text-muted mb-0 small"},"Cancelled Orders",-1))]),e[10]||(e[10]=t("div",{class:"rounded-circle p-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center",style:{width:"56px",height:"56px"}},[t("i",{class:"bi bi-x-circle fs-4"})],-1))])])])]),t("div",Se,[t("div",je,[t("div",Ke,[e[18]||(e[18]=t("h5",{class:"mb-0 fw-semibold"},"Kitchen Orders",-1)),t("div",Pe,[t("div",Ae,[e[11]||(e[11]=t("i",{class:"bi bi-search"},null,-1)),e[12]||(e[12]=t("input",{type:"email",name:"email",autocomplete:"email",style:{position:"absolute",left:"-9999px",width:"1px",height:"1px"},tabindex:"-1","aria-hidden":"true"},null,-1)),N.value?k((u(),c("input",{id:C,"onUpdate:modelValue":e[0]||(e[0]=s=>y.value=s),key:I.value,class:"form-control search-input",placeholder:"Search",type:"search",autocomplete:"new-password",name:C,role:"presentation",onFocus:e[1]||(e[1]=(...s)=>r.handleFocus&&r.handleFocus(...s))},null,32)),[[re,y.value]]):(u(),c("input",Ve))]),b(ie,{modelValue:d.value,"onUpdate:modelValue":e[2]||(e[2]=s=>d.value=s),title:"Kitchen Orders","modal-id":"kotFilterModal","modal-size":"modal-lg","sort-options":D.value.sortOptions,"show-date-range":!0,onApply:r.handleFilterApply,onClear:r.handleFilterClear},{customFilters:L(({filters:s})=>[t("div",Le,[e[14]||(e[14]=t("label",{class:"form-label fw-semibold text-dark"},[t("i",{class:"fas fa-concierge-bell me-2 text-muted"}),B("Order Type ")],-1)),k(t("select",{"onUpdate:modelValue":a=>s.orderType=a,class:"form-select"},[e[13]||(e[13]=t("option",{value:""},"All",-1)),(u(!0),c(x,null,F(D.value.orderTypeOptions,a=>(u(),c("option",{key:a.value,value:a.value},n(a.label),9,Ee))),128))],8,Be),[[E,s.orderType]])]),t("div",We,[e[16]||(e[16]=t("label",{class:"form-label fw-semibold text-dark"},[t("i",{class:"fas fa-tasks me-2 text-muted"}),B("Order Status ")],-1)),k(t("select",{"onUpdate:modelValue":a=>s.status=a,class:"form-select"},[e[15]||(e[15]=t("option",{value:""},"All",-1)),(u(!0),c(x,null,F(D.value.statusOptions,a=>(u(),c("option",{key:a.value,value:a.value},n(a.label),9,Me))),128))],8,qe),[[E,s.status]])])]),_:1},8,["modelValue","sort-options","onApply","onClear"]),e[17]||(e[17]=t("div",{class:"dropdown"},[t("button",{class:"btn btn-outline-secondary rounded-pill px-4 dropdown-toggle","data-bs-toggle":"dropdown"}," Export "),t("ul",{class:"dropdown-menu dropdown-menu-end shadow rounded-4 py-2"},[t("li",null,[t("a",{class:"dropdown-item py-2",href:"javascript:void(0)"},"Export as PDF")]),t("li",null,[t("a",{class:"dropdown-item py-2",href:"javascript:void(0)"},"Export as Excel")])])],-1))])]),t("div",Ue,[t("table",ze,[e[20]||(e[20]=t("thead",{class:"border-top small text-muted"},[t("tr",null,[t("th",null,"#"),t("th",null,"Order ID"),t("th",null,"Item Name"),t("th",null,"Variant"),t("th",null,"Order Type"),t("th",null,"Ingredients"),t("th",null,"Status"),t("th",{class:"text-center"},"Actions")])],-1)),t("tbody",null,[(u(!0),c(x,null,F(T.value,(s,a)=>(u(),c("tr",{key:s.uniqueId||a},[t("td",null,n(a+1),1),t("td",null,n(s.order?.id),1),t("td",null,n(s.item_name),1),t("td",null,n(s.variant_name),1),t("td",null,n(s.order?.type?.order_type||"-"),1),t("td",null,n(s.ingredients?.join(", ")||"-"),1),t("td",null,[t("span",{class:le(["badge","rounded-pill",J(s.status)]),style:{width:"80px",display:"inline-flex","justify-content":"center","align-items":"center",height:"25px"}},n(s.status),3)]),t("td",null,[t("div",He,[t("button",{onClick:l=>$(s,"Waiting"),title:"Waiting",class:"p-2 rounded-full text-warning hover:bg-gray-100"},[b(g(ce),{class:"w-5 h-5"})],8,Re),t("button",{onClick:l=>$(s,"Done"),title:"Done",class:"p-2 rounded-full text-success hover:bg-gray-100"},[b(g(ue),{class:"w-5 h-5"})],8,Je),t("button",{onClick:l=>$(s,"Cancelled"),title:"Cancelled",class:"p-2 rounded-full text-danger hover:bg-gray-100"},[b(g(me),{class:"w-5 h-5"})],8,Ze),j.value.length>0?(u(),c("button",{key:0,class:"p-2 rounded-full text-gray-600 hover:bg-gray-100",onClick:oe(l=>Z(s.order),["prevent"]),title:"Print"},[b(g(pe),{class:"w-5 h-5"})],8,Qe)):W("",!0)])])]))),128)),T.value.length===0?(u(),c("tr",Xe,[...e[19]||(e[19]=[t("td",{colspan:"7",class:"text-center text-muted py-4"}," No orders found. ",-1)])])):W("",!0)])])])])])])]),_:1})],64))}},bt=te(Ge,[["__scopeId","data-v-6d01ecd5"]]);export{bt as default};
