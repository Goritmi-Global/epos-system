import{_ as v,R as g,o as b,Y as i,y as x,f as c,b as r,m as M,e as o,F as C,g as w,t as S,k as h,Z as u,i as _}from"./app-DMbHFmC5.js";const B={key:0,class:"fw-bold mb-4"},V={class:"vstack gap-2",style:{"max-width":"620px"}},j={class:"d-flex align-items-center gap-2"},z=["innerHTML"],E={class:"feat-text"},F={class:"segmented"},L=["id","onUpdate:modelValue"],O=["for"],U=["id","onUpdate:modelValue"],A=["for"],H={__name:"Step9",props:{model:Object,isOnboarding:{type:Boolean,default:!1}},emits:["save"],setup(y,{emit:m}){const a=y,d=m,t=g({feat_loyalty:a.model?.feat_loyalty??"yes",feat_inventory:a.model?.feat_inventory??"no",feat_backup:a.model?.feat_backup??"no",feat_multilocation:a.model?.feat_multilocation??"yes",feat_theme:a.model?.feat_theme??"yes"});b(()=>{console.log("Step 9 - Props received:",a.model),console.log("Step 9 - Form initialized:",i(t))}),x(()=>t,n=>{const s=i(t);console.log("Step 9 Form Updated (watch):",s),d("save",{step:9,data:s})},{deep:!0,immediate:!0});function p(){const n=i(t);console.log("Step 9 - Emitting save with data:",n),d("save",{step:9,data:n})}const k=[{key:"feat_loyalty",label:"Enable Loyalty System",icon:"gift"},{key:"feat_inventory",label:"Enable Inventory Tracking",icon:"box"},{key:"feat_backup",label:"Enable Cloud Backup",icon:"cloud"},{key:"feat_multilocation",label:"Enable Multi-Location",icon:"geo"},{key:"feat_theme",label:"Theme Preference",icon:"palette"}],f={gift:`
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="20 12 20 22 4 22 4 12"></polyline>
      <rect x="2" y="7" width="20" height="5" rx="2"></rect>
      <path d="M12 22V7"></path>
      <path d="M12 7c.8-2.2-1.6-4-3.4-2.6C6.8 5 8 7 9.6 7H12z"></path>
      <path d="M12 7c-.8-2.2 1.6-4 3.4-2.6C17.2 5 16 7 14.4 7H12z"></path>
    </svg>
  `,box:`
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      <path d="M3.3 7.5 12 12l8.7-4.5"></path>
      <path d="M12 22V12"></path>
    </svg>
  `,cloud:`
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M20 16.5a4.5 4.5 0 0 0-1.5-8.8A6 6 0 0 0 6 9.5 4.5 4.5 0 1 0 7 18h10"></path>
      <path d="M12 12v6"></path>
      <path d="M9.5 14.5 12 12l2.5 2.5"></path>
    </svg>
  `,geo:`
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 21s7-5.1 7-11.2A7 7 0 0 0 12 3a7 7 0 0 0-7 6.8C5 15.9 12 21 12 21z"></path>
      <circle cx="12" cy="10" r="2.5"></circle>
    </svg>
  `,palette:`
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M13.5 21a8.5 8.5 0 1 0-8.4-10.1 2.5 2.5 0 0 0 2.5 3.1h1.3a1.9 1.9 0 0 1 1.9 2.2c-.2 1.8.8 3.8 3 3.8h-.3z"></path>
      <circle cx="7.5" cy="8.5" r="1"></circle>
      <circle cx="12"  cy="6.5" r="1"></circle>
      <circle cx="16.5" cy="8.5" r="1"></circle>
      <circle cx="17.5" cy="13"  r="1"></circle>
    </svg>
  `};return(n,s)=>(r(),c("div",null,[a.isOnboarding?(r(),c("h5",B,"Step 9 of 9 - Optional Features")):M("",!0),o("div",V,[(r(),c(C,null,w(k,e=>o("div",{key:e.key,class:"feat-row d-flex align-items-center justify-content-between"},[o("div",j,[o("span",{class:"fi",innerHTML:f[e.icon]},null,8,z),o("span",E,S(e.label),1)]),o("div",F,[h(o("input",{class:"segmented__input",type:"radio",id:e.key+"-yes",value:"yes","onUpdate:modelValue":l=>t[e.key]=l,onChange:p},null,40,L),[[u,t[e.key]]]),o("label",{class:_(["segmented__btn",{"is-active":t[e.key]==="yes"}]),for:e.key+"-yes"},"YES",10,O),h(o("input",{class:"segmented__input",type:"radio",id:e.key+"-no",value:"no","onUpdate:modelValue":l=>t[e.key]=l,onChange:p},null,40,U),[[u,t[e.key]]]),o("label",{class:_(["segmented__btn",{"is-active":t[e.key]==="no"}]),for:e.key+"-no"},"NO",10,A)])])),64))])]))}},N=v(H,[["__scopeId","data-v-5186d5f1"]]);export{N as default};
