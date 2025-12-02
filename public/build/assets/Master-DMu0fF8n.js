import{_ as Nt,a as j,b as l,w as J,f as d,m as h,e as n,t as f,V as Et,d as gt,u as R,o as st,z as ue,a0 as V,a1 as yt,a2 as qt,a3 as Ht,H as F,l as vt,a4 as w,E as mt,a5 as Yt,k as U,i as m,j as Zt,a6 as ce,a7 as lt,a8 as at,a9 as be,aa as At,ab as pe,ac as Gt,ad as fe,ae as he,F as _,A as ve,r as g,c as X,y as ge,n as Ot,P as me,I as ye,B as zt,x as Dt,g as W,Z as we,v as tt,U as Rt,q as P}from"./app-CtUM9wQF.js";import{u as ke,a as xe}from"./index-Yi4DTr5Y.js";import{c as _e}from"./createLucideIcon-XTnGKXOB.js";import{R as Ce,s as Se,B as $e,a as Le,b as Pe,x as Lt}from"./index-BcwoPGio.js";import{s as Bt,f as it}from"./index-DlKEzyjw.js";import{S as Ee,M as Be}from"./sun-B2mq1kk5.js";/**
 * @license lucide-vue-next v0.525.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const Ie=_e("refresh-ccw",[["path",{d:"M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8",key:"14sxne"}],["path",{d:"M3 3v5h5",key:"1xhq8a"}],["path",{d:"M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16",key:"1hlbsb"}],["path",{d:"M16 16h5v5",key:"ccwih5"}]]),Fe={key:0,class:"fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"},je={class:"bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative"},Me={class:"text-center text-lg font-medium text-gray-800 mb-2"},Ae={class:"text-center text-sm text-gray-500 mb-6"},Oe={__name:"LogoutModal",props:{title:{type:String,default:"Confirm Logout"},message:{type:String,default:"Are you sure you want to log out?"},show:{type:Boolean,default:!1}},emits:["confirm","cancel"],setup(t,{emit:e}){const o=e,a=()=>o("confirm"),p=()=>o("cancel");return(c,v)=>(l(),j(Et,{name:"fade-slide"},{default:J(()=>[t.show?(l(),d("div",Fe,[n("div",je,[n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110",onClick:p,title:"Close"},[...v[0]||(v[0]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])]),v[1]||(v[1]=n("div",{class:"flex justify-center mb-4"},[n("div",{class:"bg-red-100 p-3 rounded-full"},[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"w-8 h-8 text-red-600",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"},[n("path",{d:"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"}),n("polyline",{points:"16 17 21 12 16 7"}),n("line",{x1:"21",y1:"12",x2:"9",y2:"12"})])])],-1)),n("h3",Me,f(t.title),1),n("p",Ae,f(t.message),1),n("div",{class:"flex justify-center gap-3"},[n("button",{onClick:a,class:"btn btn-danger d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"}," Yes, Logout "),n("button",{onClick:p,class:"btn btn-secondary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"}," Cancel ")])])])):h("",!0)]),_:1}))}},ze=Nt(Oe,[["__scopeId","data-v-00f86d7b"]]),De={key:0,class:"fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"},Re={class:"bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative"},Te={class:"flex justify-center mb-4"},Ue={class:"bg-orange-100 p-3 rounded-full"},Ve={class:"text-center text-lg font-medium text-gray-800 mb-2"},Ke={class:"text-center text-sm text-gray-500 mb-6"},Ne={class:"flex justify-center gap-3"},qe={__name:"RestoreSystemModal",props:{show:{type:Boolean,required:!0},title:{type:String,default:"Confirm System Restore"},message:{type:String,default:"Are you sure you want to restore the system? This action cannot be undone."},confirmLabel:{type:String,default:"Yes, Restore"}},emits:["confirm","cancel"],setup(t,{emit:e}){const o=e,a=()=>{o("confirm")},p=()=>{o("cancel")};return(c,v)=>(l(),j(Et,{name:"fade-slide"},{default:J(()=>[t.show?(l(),d("div",De,[n("div",Re,[n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110",onClick:p,title:"Close"},[...v[0]||(v[0]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])]),n("div",Te,[n("div",Ue,[gt(R(Ie),{class:"w-8 h-8 text-orange-600"})])]),n("h3",Ve,f(t.title),1),n("p",Ke,f(t.message),1),n("div",Ne,[n("button",{onClick:a,class:"btn btn-primary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"},f(t.confirmLabel),1),n("button",{onClick:p,class:"btn btn-secondary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"}," Cancel ")])])])):h("",!0)]),_:1}))}},Tt=Nt(qe,[["__scopeId","data-v-60cf45eb"]]);function He(){let t=null;const e=async()=>{try{const o=await fetch("/check-auto-logout",{method:"GET",headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"},credentials:"same-origin"}),a=o.headers.get("content-type");if(a&&a.includes("text/html")){console.warn("Received HTML response - likely session expired"),t&&clearInterval(t),V.visit("/login",{method:"get",replace:!0});return}if(o.status===401){t&&clearInterval(t),V.visit("/login",{method:"get",replace:!0});return}if(!a||!a.includes("application/json")){const c=await o.text();console.warn("Non-JSON response received:",c.substring(0,200)),t&&clearInterval(t);return}(await o.json()).should_logout&&(t&&clearInterval(t),V.visit("/login",{method:"get",replace:!0}))}catch(o){console.error("Auto-logout check failed:",o)}};return st(()=>{e(),t=setInterval(e,1e4)}),ue(()=>{t&&clearInterval(t)}),{checkAutoLogout:e}}var Ye=`
    .p-badge {
        display: inline-flex;
        border-radius: dt('badge.border.radius');
        align-items: center;
        justify-content: center;
        padding: dt('badge.padding');
        background: dt('badge.primary.background');
        color: dt('badge.primary.color');
        font-size: dt('badge.font.size');
        font-weight: dt('badge.font.weight');
        min-width: dt('badge.min.width');
        height: dt('badge.height');
    }

    .p-badge-dot {
        width: dt('badge.dot.size');
        min-width: dt('badge.dot.size');
        height: dt('badge.dot.size');
        border-radius: 50%;
        padding: 0;
    }

    .p-badge-circle {
        padding: 0;
        border-radius: 50%;
    }

    .p-badge-secondary {
        background: dt('badge.secondary.background');
        color: dt('badge.secondary.color');
    }

    .p-badge-success {
        background: dt('badge.success.background');
        color: dt('badge.success.color');
    }

    .p-badge-info {
        background: dt('badge.info.background');
        color: dt('badge.info.color');
    }

    .p-badge-warn {
        background: dt('badge.warn.background');
        color: dt('badge.warn.color');
    }

    .p-badge-danger {
        background: dt('badge.danger.background');
        color: dt('badge.danger.color');
    }

    .p-badge-contrast {
        background: dt('badge.contrast.background');
        color: dt('badge.contrast.color');
    }

    .p-badge-sm {
        font-size: dt('badge.sm.font.size');
        min-width: dt('badge.sm.min.width');
        height: dt('badge.sm.height');
    }

    .p-badge-lg {
        font-size: dt('badge.lg.font.size');
        min-width: dt('badge.lg.min.width');
        height: dt('badge.lg.height');
    }

    .p-badge-xl {
        font-size: dt('badge.xl.font.size');
        min-width: dt('badge.xl.min.width');
        height: dt('badge.xl.height');
    }
`,Ze={root:function(e){var o=e.props,a=e.instance;return["p-badge p-component",{"p-badge-circle":Ht(o.value)&&String(o.value).length===1,"p-badge-dot":qt(o.value)&&!a.$slots.default,"p-badge-sm":o.size==="small","p-badge-lg":o.size==="large","p-badge-xl":o.size==="xlarge","p-badge-info":o.severity==="info","p-badge-success":o.severity==="success","p-badge-warn":o.severity==="warn","p-badge-danger":o.severity==="danger","p-badge-secondary":o.severity==="secondary","p-badge-contrast":o.severity==="contrast"}]}},Ge=yt.extend({name:"badge",style:Ye,classes:Ze}),Xe={name:"BaseBadge",extends:Bt,props:{value:{type:[String,Number],default:null},severity:{type:String,default:null},size:{type:String,default:null}},style:Ge,provide:function(){return{$pcBadge:this,$parentInstance:this}}};function dt(t){"@babel/helpers - typeof";return dt=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},dt(t)}function Ut(t,e,o){return(e=We(e))in t?Object.defineProperty(t,e,{value:o,enumerable:!0,configurable:!0,writable:!0}):t[e]=o,t}function We(t){var e=Je(t,"string");return dt(e)=="symbol"?e:e+""}function Je(t,e){if(dt(t)!="object"||!t)return t;var o=t[Symbol.toPrimitive];if(o!==void 0){var a=o.call(t,e);if(dt(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var Xt={name:"Badge",extends:Xe,inheritAttrs:!1,computed:{dataP:function(){return it(Ut(Ut({circle:this.value!=null&&String(this.value).length===1,empty:this.value==null&&!this.$slots.default},this.severity,this.severity),this.size,this.size))}}},Qe=["data-p"];function tn(t,e,o,a,p,c){return l(),d("span",w({class:t.cx("root"),"data-p":c.dataP},t.ptmi("root")),[F(t.$slots,"default",{},function(){return[vt(f(t.value),1)]})],16,Qe)}Xt.render=tn;var en=`
    .p-button {
        display: inline-flex;
        cursor: pointer;
        user-select: none;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        color: dt('button.primary.color');
        background: dt('button.primary.background');
        border: 1px solid dt('button.primary.border.color');
        padding: dt('button.padding.y') dt('button.padding.x');
        font-size: 1rem;
        font-family: inherit;
        font-feature-settings: inherit;
        transition:
            background dt('button.transition.duration'),
            color dt('button.transition.duration'),
            border-color dt('button.transition.duration'),
            outline-color dt('button.transition.duration'),
            box-shadow dt('button.transition.duration');
        border-radius: dt('button.border.radius');
        outline-color: transparent;
        gap: dt('button.gap');
    }

    .p-button:disabled {
        cursor: default;
    }

    .p-button-icon-right {
        order: 1;
    }

    .p-button-icon-right:dir(rtl) {
        order: -1;
    }

    .p-button:not(.p-button-vertical) .p-button-icon:not(.p-button-icon-right):dir(rtl) {
        order: 1;
    }

    .p-button-icon-bottom {
        order: 2;
    }

    .p-button-icon-only {
        width: dt('button.icon.only.width');
        padding-inline-start: 0;
        padding-inline-end: 0;
        gap: 0;
    }

    .p-button-icon-only.p-button-rounded {
        border-radius: 50%;
        height: dt('button.icon.only.width');
    }

    .p-button-icon-only .p-button-label {
        visibility: hidden;
        width: 0;
    }

    .p-button-icon-only::after {
        content: "\0A0";
        visibility: hidden;
        width: 0;
    }

    .p-button-sm {
        font-size: dt('button.sm.font.size');
        padding: dt('button.sm.padding.y') dt('button.sm.padding.x');
    }

    .p-button-sm .p-button-icon {
        font-size: dt('button.sm.font.size');
    }

    .p-button-sm.p-button-icon-only {
        width: dt('button.sm.icon.only.width');
    }

    .p-button-sm.p-button-icon-only.p-button-rounded {
        height: dt('button.sm.icon.only.width');
    }

    .p-button-lg {
        font-size: dt('button.lg.font.size');
        padding: dt('button.lg.padding.y') dt('button.lg.padding.x');
    }

    .p-button-lg .p-button-icon {
        font-size: dt('button.lg.font.size');
    }

    .p-button-lg.p-button-icon-only {
        width: dt('button.lg.icon.only.width');
    }

    .p-button-lg.p-button-icon-only.p-button-rounded {
        height: dt('button.lg.icon.only.width');
    }

    .p-button-vertical {
        flex-direction: column;
    }

    .p-button-label {
        font-weight: dt('button.label.font.weight');
    }

    .p-button-fluid {
        width: 100%;
    }

    .p-button-fluid.p-button-icon-only {
        width: dt('button.icon.only.width');
    }

    .p-button:not(:disabled):hover {
        background: dt('button.primary.hover.background');
        border: 1px solid dt('button.primary.hover.border.color');
        color: dt('button.primary.hover.color');
    }

    .p-button:not(:disabled):active {
        background: dt('button.primary.active.background');
        border: 1px solid dt('button.primary.active.border.color');
        color: dt('button.primary.active.color');
    }

    .p-button:focus-visible {
        box-shadow: dt('button.primary.focus.ring.shadow');
        outline: dt('button.focus.ring.width') dt('button.focus.ring.style') dt('button.primary.focus.ring.color');
        outline-offset: dt('button.focus.ring.offset');
    }

    .p-button .p-badge {
        min-width: dt('button.badge.size');
        height: dt('button.badge.size');
        line-height: dt('button.badge.size');
    }

    .p-button-raised {
        box-shadow: dt('button.raised.shadow');
    }

    .p-button-rounded {
        border-radius: dt('button.rounded.border.radius');
    }

    .p-button-secondary {
        background: dt('button.secondary.background');
        border: 1px solid dt('button.secondary.border.color');
        color: dt('button.secondary.color');
    }

    .p-button-secondary:not(:disabled):hover {
        background: dt('button.secondary.hover.background');
        border: 1px solid dt('button.secondary.hover.border.color');
        color: dt('button.secondary.hover.color');
    }

    .p-button-secondary:not(:disabled):active {
        background: dt('button.secondary.active.background');
        border: 1px solid dt('button.secondary.active.border.color');
        color: dt('button.secondary.active.color');
    }

    .p-button-secondary:focus-visible {
        outline-color: dt('button.secondary.focus.ring.color');
        box-shadow: dt('button.secondary.focus.ring.shadow');
    }

    .p-button-success {
        background: dt('button.success.background');
        border: 1px solid dt('button.success.border.color');
        color: dt('button.success.color');
    }

    .p-button-success:not(:disabled):hover {
        background: dt('button.success.hover.background');
        border: 1px solid dt('button.success.hover.border.color');
        color: dt('button.success.hover.color');
    }

    .p-button-success:not(:disabled):active {
        background: dt('button.success.active.background');
        border: 1px solid dt('button.success.active.border.color');
        color: dt('button.success.active.color');
    }

    .p-button-success:focus-visible {
        outline-color: dt('button.success.focus.ring.color');
        box-shadow: dt('button.success.focus.ring.shadow');
    }

    .p-button-info {
        background: dt('button.info.background');
        border: 1px solid dt('button.info.border.color');
        color: dt('button.info.color');
    }

    .p-button-info:not(:disabled):hover {
        background: dt('button.info.hover.background');
        border: 1px solid dt('button.info.hover.border.color');
        color: dt('button.info.hover.color');
    }

    .p-button-info:not(:disabled):active {
        background: dt('button.info.active.background');
        border: 1px solid dt('button.info.active.border.color');
        color: dt('button.info.active.color');
    }

    .p-button-info:focus-visible {
        outline-color: dt('button.info.focus.ring.color');
        box-shadow: dt('button.info.focus.ring.shadow');
    }

    .p-button-warn {
        background: dt('button.warn.background');
        border: 1px solid dt('button.warn.border.color');
        color: dt('button.warn.color');
    }

    .p-button-warn:not(:disabled):hover {
        background: dt('button.warn.hover.background');
        border: 1px solid dt('button.warn.hover.border.color');
        color: dt('button.warn.hover.color');
    }

    .p-button-warn:not(:disabled):active {
        background: dt('button.warn.active.background');
        border: 1px solid dt('button.warn.active.border.color');
        color: dt('button.warn.active.color');
    }

    .p-button-warn:focus-visible {
        outline-color: dt('button.warn.focus.ring.color');
        box-shadow: dt('button.warn.focus.ring.shadow');
    }

    .p-button-help {
        background: dt('button.help.background');
        border: 1px solid dt('button.help.border.color');
        color: dt('button.help.color');
    }

    .p-button-help:not(:disabled):hover {
        background: dt('button.help.hover.background');
        border: 1px solid dt('button.help.hover.border.color');
        color: dt('button.help.hover.color');
    }

    .p-button-help:not(:disabled):active {
        background: dt('button.help.active.background');
        border: 1px solid dt('button.help.active.border.color');
        color: dt('button.help.active.color');
    }

    .p-button-help:focus-visible {
        outline-color: dt('button.help.focus.ring.color');
        box-shadow: dt('button.help.focus.ring.shadow');
    }

    .p-button-danger {
        background: dt('button.danger.background');
        border: 1px solid dt('button.danger.border.color');
        color: dt('button.danger.color');
    }

    .p-button-danger:not(:disabled):hover {
        background: dt('button.danger.hover.background');
        border: 1px solid dt('button.danger.hover.border.color');
        color: dt('button.danger.hover.color');
    }

    .p-button-danger:not(:disabled):active {
        background: dt('button.danger.active.background');
        border: 1px solid dt('button.danger.active.border.color');
        color: dt('button.danger.active.color');
    }

    .p-button-danger:focus-visible {
        outline-color: dt('button.danger.focus.ring.color');
        box-shadow: dt('button.danger.focus.ring.shadow');
    }

    .p-button-contrast {
        background: dt('button.contrast.background');
        border: 1px solid dt('button.contrast.border.color');
        color: dt('button.contrast.color');
    }

    .p-button-contrast:not(:disabled):hover {
        background: dt('button.contrast.hover.background');
        border: 1px solid dt('button.contrast.hover.border.color');
        color: dt('button.contrast.hover.color');
    }

    .p-button-contrast:not(:disabled):active {
        background: dt('button.contrast.active.background');
        border: 1px solid dt('button.contrast.active.border.color');
        color: dt('button.contrast.active.color');
    }

    .p-button-contrast:focus-visible {
        outline-color: dt('button.contrast.focus.ring.color');
        box-shadow: dt('button.contrast.focus.ring.shadow');
    }

    .p-button-outlined {
        background: transparent;
        border-color: dt('button.outlined.primary.border.color');
        color: dt('button.outlined.primary.color');
    }

    .p-button-outlined:not(:disabled):hover {
        background: dt('button.outlined.primary.hover.background');
        border-color: dt('button.outlined.primary.border.color');
        color: dt('button.outlined.primary.color');
    }

    .p-button-outlined:not(:disabled):active {
        background: dt('button.outlined.primary.active.background');
        border-color: dt('button.outlined.primary.border.color');
        color: dt('button.outlined.primary.color');
    }

    .p-button-outlined.p-button-secondary {
        border-color: dt('button.outlined.secondary.border.color');
        color: dt('button.outlined.secondary.color');
    }

    .p-button-outlined.p-button-secondary:not(:disabled):hover {
        background: dt('button.outlined.secondary.hover.background');
        border-color: dt('button.outlined.secondary.border.color');
        color: dt('button.outlined.secondary.color');
    }

    .p-button-outlined.p-button-secondary:not(:disabled):active {
        background: dt('button.outlined.secondary.active.background');
        border-color: dt('button.outlined.secondary.border.color');
        color: dt('button.outlined.secondary.color');
    }

    .p-button-outlined.p-button-success {
        border-color: dt('button.outlined.success.border.color');
        color: dt('button.outlined.success.color');
    }

    .p-button-outlined.p-button-success:not(:disabled):hover {
        background: dt('button.outlined.success.hover.background');
        border-color: dt('button.outlined.success.border.color');
        color: dt('button.outlined.success.color');
    }

    .p-button-outlined.p-button-success:not(:disabled):active {
        background: dt('button.outlined.success.active.background');
        border-color: dt('button.outlined.success.border.color');
        color: dt('button.outlined.success.color');
    }

    .p-button-outlined.p-button-info {
        border-color: dt('button.outlined.info.border.color');
        color: dt('button.outlined.info.color');
    }

    .p-button-outlined.p-button-info:not(:disabled):hover {
        background: dt('button.outlined.info.hover.background');
        border-color: dt('button.outlined.info.border.color');
        color: dt('button.outlined.info.color');
    }

    .p-button-outlined.p-button-info:not(:disabled):active {
        background: dt('button.outlined.info.active.background');
        border-color: dt('button.outlined.info.border.color');
        color: dt('button.outlined.info.color');
    }

    .p-button-outlined.p-button-warn {
        border-color: dt('button.outlined.warn.border.color');
        color: dt('button.outlined.warn.color');
    }

    .p-button-outlined.p-button-warn:not(:disabled):hover {
        background: dt('button.outlined.warn.hover.background');
        border-color: dt('button.outlined.warn.border.color');
        color: dt('button.outlined.warn.color');
    }

    .p-button-outlined.p-button-warn:not(:disabled):active {
        background: dt('button.outlined.warn.active.background');
        border-color: dt('button.outlined.warn.border.color');
        color: dt('button.outlined.warn.color');
    }

    .p-button-outlined.p-button-help {
        border-color: dt('button.outlined.help.border.color');
        color: dt('button.outlined.help.color');
    }

    .p-button-outlined.p-button-help:not(:disabled):hover {
        background: dt('button.outlined.help.hover.background');
        border-color: dt('button.outlined.help.border.color');
        color: dt('button.outlined.help.color');
    }

    .p-button-outlined.p-button-help:not(:disabled):active {
        background: dt('button.outlined.help.active.background');
        border-color: dt('button.outlined.help.border.color');
        color: dt('button.outlined.help.color');
    }

    .p-button-outlined.p-button-danger {
        border-color: dt('button.outlined.danger.border.color');
        color: dt('button.outlined.danger.color');
    }

    .p-button-outlined.p-button-danger:not(:disabled):hover {
        background: dt('button.outlined.danger.hover.background');
        border-color: dt('button.outlined.danger.border.color');
        color: dt('button.outlined.danger.color');
    }

    .p-button-outlined.p-button-danger:not(:disabled):active {
        background: dt('button.outlined.danger.active.background');
        border-color: dt('button.outlined.danger.border.color');
        color: dt('button.outlined.danger.color');
    }

    .p-button-outlined.p-button-contrast {
        border-color: dt('button.outlined.contrast.border.color');
        color: dt('button.outlined.contrast.color');
    }

    .p-button-outlined.p-button-contrast:not(:disabled):hover {
        background: dt('button.outlined.contrast.hover.background');
        border-color: dt('button.outlined.contrast.border.color');
        color: dt('button.outlined.contrast.color');
    }

    .p-button-outlined.p-button-contrast:not(:disabled):active {
        background: dt('button.outlined.contrast.active.background');
        border-color: dt('button.outlined.contrast.border.color');
        color: dt('button.outlined.contrast.color');
    }

    .p-button-outlined.p-button-plain {
        border-color: dt('button.outlined.plain.border.color');
        color: dt('button.outlined.plain.color');
    }

    .p-button-outlined.p-button-plain:not(:disabled):hover {
        background: dt('button.outlined.plain.hover.background');
        border-color: dt('button.outlined.plain.border.color');
        color: dt('button.outlined.plain.color');
    }

    .p-button-outlined.p-button-plain:not(:disabled):active {
        background: dt('button.outlined.plain.active.background');
        border-color: dt('button.outlined.plain.border.color');
        color: dt('button.outlined.plain.color');
    }

    .p-button-text {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.primary.color');
    }

    .p-button-text:not(:disabled):hover {
        background: dt('button.text.primary.hover.background');
        border-color: transparent;
        color: dt('button.text.primary.color');
    }

    .p-button-text:not(:disabled):active {
        background: dt('button.text.primary.active.background');
        border-color: transparent;
        color: dt('button.text.primary.color');
    }

    .p-button-text.p-button-secondary {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.secondary.color');
    }

    .p-button-text.p-button-secondary:not(:disabled):hover {
        background: dt('button.text.secondary.hover.background');
        border-color: transparent;
        color: dt('button.text.secondary.color');
    }

    .p-button-text.p-button-secondary:not(:disabled):active {
        background: dt('button.text.secondary.active.background');
        border-color: transparent;
        color: dt('button.text.secondary.color');
    }

    .p-button-text.p-button-success {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.success.color');
    }

    .p-button-text.p-button-success:not(:disabled):hover {
        background: dt('button.text.success.hover.background');
        border-color: transparent;
        color: dt('button.text.success.color');
    }

    .p-button-text.p-button-success:not(:disabled):active {
        background: dt('button.text.success.active.background');
        border-color: transparent;
        color: dt('button.text.success.color');
    }

    .p-button-text.p-button-info {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.info.color');
    }

    .p-button-text.p-button-info:not(:disabled):hover {
        background: dt('button.text.info.hover.background');
        border-color: transparent;
        color: dt('button.text.info.color');
    }

    .p-button-text.p-button-info:not(:disabled):active {
        background: dt('button.text.info.active.background');
        border-color: transparent;
        color: dt('button.text.info.color');
    }

    .p-button-text.p-button-warn {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.warn.color');
    }

    .p-button-text.p-button-warn:not(:disabled):hover {
        background: dt('button.text.warn.hover.background');
        border-color: transparent;
        color: dt('button.text.warn.color');
    }

    .p-button-text.p-button-warn:not(:disabled):active {
        background: dt('button.text.warn.active.background');
        border-color: transparent;
        color: dt('button.text.warn.color');
    }

    .p-button-text.p-button-help {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.help.color');
    }

    .p-button-text.p-button-help:not(:disabled):hover {
        background: dt('button.text.help.hover.background');
        border-color: transparent;
        color: dt('button.text.help.color');
    }

    .p-button-text.p-button-help:not(:disabled):active {
        background: dt('button.text.help.active.background');
        border-color: transparent;
        color: dt('button.text.help.color');
    }

    .p-button-text.p-button-danger {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.danger.color');
    }

    .p-button-text.p-button-danger:not(:disabled):hover {
        background: dt('button.text.danger.hover.background');
        border-color: transparent;
        color: dt('button.text.danger.color');
    }

    .p-button-text.p-button-danger:not(:disabled):active {
        background: dt('button.text.danger.active.background');
        border-color: transparent;
        color: dt('button.text.danger.color');
    }

    .p-button-text.p-button-contrast {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.contrast.color');
    }

    .p-button-text.p-button-contrast:not(:disabled):hover {
        background: dt('button.text.contrast.hover.background');
        border-color: transparent;
        color: dt('button.text.contrast.color');
    }

    .p-button-text.p-button-contrast:not(:disabled):active {
        background: dt('button.text.contrast.active.background');
        border-color: transparent;
        color: dt('button.text.contrast.color');
    }

    .p-button-text.p-button-plain {
        background: transparent;
        border-color: transparent;
        color: dt('button.text.plain.color');
    }

    .p-button-text.p-button-plain:not(:disabled):hover {
        background: dt('button.text.plain.hover.background');
        border-color: transparent;
        color: dt('button.text.plain.color');
    }

    .p-button-text.p-button-plain:not(:disabled):active {
        background: dt('button.text.plain.active.background');
        border-color: transparent;
        color: dt('button.text.plain.color');
    }

    .p-button-link {
        background: transparent;
        border-color: transparent;
        color: dt('button.link.color');
    }

    .p-button-link:not(:disabled):hover {
        background: transparent;
        border-color: transparent;
        color: dt('button.link.hover.color');
    }

    .p-button-link:not(:disabled):hover .p-button-label {
        text-decoration: underline;
    }

    .p-button-link:not(:disabled):active {
        background: transparent;
        border-color: transparent;
        color: dt('button.link.active.color');
    }
`;function ut(t){"@babel/helpers - typeof";return ut=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},ut(t)}function D(t,e,o){return(e=nn(e))in t?Object.defineProperty(t,e,{value:o,enumerable:!0,configurable:!0,writable:!0}):t[e]=o,t}function nn(t){var e=on(t,"string");return ut(e)=="symbol"?e:e+""}function on(t,e){if(ut(t)!="object"||!t)return t;var o=t[Symbol.toPrimitive];if(o!==void 0){var a=o.call(t,e);if(ut(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var rn={root:function(e){var o=e.instance,a=e.props;return["p-button p-component",D(D(D(D(D(D(D(D(D({"p-button-icon-only":o.hasIcon&&!a.label&&!a.badge,"p-button-vertical":(a.iconPos==="top"||a.iconPos==="bottom")&&a.label,"p-button-loading":a.loading,"p-button-link":a.link||a.variant==="link"},"p-button-".concat(a.severity),a.severity),"p-button-raised",a.raised),"p-button-rounded",a.rounded),"p-button-text",a.text||a.variant==="text"),"p-button-outlined",a.outlined||a.variant==="outlined"),"p-button-sm",a.size==="small"),"p-button-lg",a.size==="large"),"p-button-plain",a.plain),"p-button-fluid",o.hasFluid)]},loadingIcon:"p-button-loading-icon",icon:function(e){var o=e.props;return["p-button-icon",D({},"p-button-icon-".concat(o.iconPos),o.label)]},label:"p-button-label"},an=yt.extend({name:"button",style:en,classes:rn}),sn={name:"BaseButton",extends:Bt,props:{label:{type:String,default:null},icon:{type:String,default:null},iconPos:{type:String,default:"left"},iconClass:{type:[String,Object],default:null},badge:{type:String,default:null},badgeClass:{type:[String,Object],default:null},badgeSeverity:{type:String,default:"secondary"},loading:{type:Boolean,default:!1},loadingIcon:{type:String,default:void 0},as:{type:[String,Object],default:"BUTTON"},asChild:{type:Boolean,default:!1},link:{type:Boolean,default:!1},severity:{type:String,default:null},raised:{type:Boolean,default:!1},rounded:{type:Boolean,default:!1},text:{type:Boolean,default:!1},outlined:{type:Boolean,default:!1},size:{type:String,default:null},variant:{type:String,default:null},plain:{type:Boolean,default:!1},fluid:{type:Boolean,default:null}},style:an,provide:function(){return{$pcButton:this,$parentInstance:this}}};function ct(t){"@babel/helpers - typeof";return ct=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},ct(t)}function E(t,e,o){return(e=ln(e))in t?Object.defineProperty(t,e,{value:o,enumerable:!0,configurable:!0,writable:!0}):t[e]=o,t}function ln(t){var e=dn(t,"string");return ct(e)=="symbol"?e:e+""}function dn(t,e){if(ct(t)!="object"||!t)return t;var o=t[Symbol.toPrimitive];if(o!==void 0){var a=o.call(t,e);if(ct(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var Wt={name:"Button",extends:sn,inheritAttrs:!1,inject:{$pcFluid:{default:null}},methods:{getPTOptions:function(e){var o=e==="root"?this.ptmi:this.ptm;return o(e,{context:{disabled:this.disabled}})}},computed:{disabled:function(){return this.$attrs.disabled||this.$attrs.disabled===""||this.loading},defaultAriaLabel:function(){return this.label?this.label+(this.badge?" "+this.badge:""):this.$attrs.ariaLabel},hasIcon:function(){return this.icon||this.$slots.icon},attrs:function(){return w(this.asAttrs,this.a11yAttrs,this.getPTOptions("root"))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.disabled}:void 0},a11yAttrs:function(){return{"aria-label":this.defaultAriaLabel,"data-pc-name":"button","data-p-disabled":this.disabled,"data-p-severity":this.severity}},hasFluid:function(){return qt(this.fluid)?!!this.$pcFluid:this.fluid},dataP:function(){return it(E(E(E(E(E(E(E(E(E(E({},this.size,this.size),"icon-only",this.hasIcon&&!this.label&&!this.badge),"loading",this.loading),"fluid",this.hasFluid),"rounded",this.rounded),"raised",this.raised),"outlined",this.outlined||this.variant==="outlined"),"text",this.text||this.variant==="text"),"link",this.link||this.variant==="link"),"vertical",(this.iconPos==="top"||this.iconPos==="bottom")&&this.label))},dataIconP:function(){return it(E(E({},this.iconPos,this.iconPos),this.size,this.size))},dataLabelP:function(){return it(E(E({},this.size,this.size),"icon-only",this.hasIcon&&!this.label&&!this.badge))}},components:{SpinnerIcon:Se,Badge:Xt},directives:{ripple:Ce}},un=["data-p"],cn=["data-p"];function bn(t,e,o,a,p,c){var v=mt("SpinnerIcon"),y=mt("Badge"),k=Yt("ripple");return t.asChild?F(t.$slots,"default",{key:1,class:m(t.cx("root")),a11yAttrs:c.a11yAttrs}):U((l(),j(Zt(t.as),w({key:0,class:t.cx("root"),"data-p":c.dataP},c.attrs),{default:J(function(){return[F(t.$slots,"default",{},function(){return[t.loading?F(t.$slots,"loadingicon",w({key:0,class:[t.cx("loadingIcon"),t.cx("icon")]},t.ptm("loadingIcon")),function(){return[t.loadingIcon?(l(),d("span",w({key:0,class:[t.cx("loadingIcon"),t.cx("icon"),t.loadingIcon]},t.ptm("loadingIcon")),null,16)):(l(),j(v,w({key:1,class:[t.cx("loadingIcon"),t.cx("icon")],spin:""},t.ptm("loadingIcon")),null,16,["class"]))]}):F(t.$slots,"icon",w({key:1,class:[t.cx("icon")]},t.ptm("icon")),function(){return[t.icon?(l(),d("span",w({key:0,class:[t.cx("icon"),t.icon,t.iconClass],"data-p":c.dataIconP},t.ptm("icon")),null,16,un)):h("",!0)]}),t.label?(l(),d("span",w({key:2,class:t.cx("label")},t.ptm("label"),{"data-p":c.dataLabelP}),f(t.label),17,cn)):h("",!0),t.badge?(l(),j(y,{key:3,value:t.badge,class:m(t.badgeClass),severity:t.badgeSeverity,unstyled:t.unstyled,pt:t.ptm("pcBadge")},null,8,["value","class","severity","unstyled","pt"])):h("",!0)]})]}),_:3},16,["class","data-p"])),[[k]])}Wt.render=bn;var pn=yt.extend({name:"focustrap-directive"}),fn=$e.extend({style:pn});function bt(t){"@babel/helpers - typeof";return bt=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},bt(t)}function Vt(t,e){var o=Object.keys(t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(t);e&&(a=a.filter(function(p){return Object.getOwnPropertyDescriptor(t,p).enumerable})),o.push.apply(o,a)}return o}function Kt(t){for(var e=1;e<arguments.length;e++){var o=arguments[e]!=null?arguments[e]:{};e%2?Vt(Object(o),!0).forEach(function(a){hn(t,a,o[a])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(o)):Vt(Object(o)).forEach(function(a){Object.defineProperty(t,a,Object.getOwnPropertyDescriptor(o,a))})}return t}function hn(t,e,o){return(e=vn(e))in t?Object.defineProperty(t,e,{value:o,enumerable:!0,configurable:!0,writable:!0}):t[e]=o,t}function vn(t){var e=gn(t,"string");return bt(e)=="symbol"?e:e+""}function gn(t,e){if(bt(t)!="object"||!t)return t;var o=t[Symbol.toPrimitive];if(o!==void 0){var a=o.call(t,e);if(bt(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var mn=fn.extend("focustrap",{mounted:function(e,o){var a=o.value||{},p=a.disabled;p||(this.createHiddenFocusableElements(e,o),this.bind(e,o),this.autoElementFocus(e,o)),e.setAttribute("data-pd-focustrap",!0),this.$el=e},updated:function(e,o){var a=o.value||{},p=a.disabled;p&&this.unbind(e)},unmounted:function(e){this.unbind(e)},methods:{getComputedSelector:function(e){return':not(.p-hidden-focusable):not([data-p-hidden-focusable="true"])'.concat(e??"")},bind:function(e,o){var a=this,p=o.value||{},c=p.onFocusIn,v=p.onFocusOut;e.$_pfocustrap_mutationobserver=new MutationObserver(function(y){y.forEach(function(k){if(k.type==="childList"&&!e.contains(document.activeElement)){var x=function(C){var A=At(C)?At(C,a.getComputedSelector(e.$_pfocustrap_focusableselector))?C:at(e,a.getComputedSelector(e.$_pfocustrap_focusableselector)):at(C);return Ht(A)?A:C.nextSibling&&x(C.nextSibling)};lt(x(k.nextSibling))}})}),e.$_pfocustrap_mutationobserver.disconnect(),e.$_pfocustrap_mutationobserver.observe(e,{childList:!0}),e.$_pfocustrap_focusinlistener=function(y){return c&&c(y)},e.$_pfocustrap_focusoutlistener=function(y){return v&&v(y)},e.addEventListener("focusin",e.$_pfocustrap_focusinlistener),e.addEventListener("focusout",e.$_pfocustrap_focusoutlistener)},unbind:function(e){e.$_pfocustrap_mutationobserver&&e.$_pfocustrap_mutationobserver.disconnect(),e.$_pfocustrap_focusinlistener&&e.removeEventListener("focusin",e.$_pfocustrap_focusinlistener)&&(e.$_pfocustrap_focusinlistener=null),e.$_pfocustrap_focusoutlistener&&e.removeEventListener("focusout",e.$_pfocustrap_focusoutlistener)&&(e.$_pfocustrap_focusoutlistener=null)},autoFocus:function(e){this.autoElementFocus(this.$el,{value:Kt(Kt({},e),{},{autoFocus:!0})})},autoElementFocus:function(e,o){var a=o.value||{},p=a.autoFocusSelector,c=p===void 0?"":p,v=a.firstFocusableSelector,y=v===void 0?"":v,k=a.autoFocus,x=k===void 0?!1:k,B=at(e,"[autofocus]".concat(this.getComputedSelector(c)));x&&!B&&(B=at(e,this.getComputedSelector(y))),lt(B)},onFirstHiddenElementFocus:function(e){var o,a=e.currentTarget,p=e.relatedTarget,c=p===a.$_pfocustrap_lasthiddenfocusableelement||!((o=this.$el)!==null&&o!==void 0&&o.contains(p))?at(a.parentElement,this.getComputedSelector(a.$_pfocustrap_focusableselector)):a.$_pfocustrap_lasthiddenfocusableelement;lt(c)},onLastHiddenElementFocus:function(e){var o,a=e.currentTarget,p=e.relatedTarget,c=p===a.$_pfocustrap_firsthiddenfocusableelement||!((o=this.$el)!==null&&o!==void 0&&o.contains(p))?ce(a.parentElement,this.getComputedSelector(a.$_pfocustrap_focusableselector)):a.$_pfocustrap_firsthiddenfocusableelement;lt(c)},createHiddenFocusableElements:function(e,o){var a=this,p=o.value||{},c=p.tabIndex,v=c===void 0?0:c,y=p.firstFocusableSelector,k=y===void 0?"":y,x=p.lastFocusableSelector,B=x===void 0?"":x,C=function(et){return be("span",{class:"p-hidden-accessible p-hidden-focusable",tabIndex:v,role:"presentation","aria-hidden":!0,"data-p-hidden-accessible":!0,"data-p-hidden-focusable":!0,onFocus:et?.bind(a)})},A=C(this.onFirstHiddenElementFocus),K=C(this.onLastHiddenElementFocus);A.$_pfocustrap_lasthiddenfocusableelement=K,A.$_pfocustrap_focusableselector=k,A.setAttribute("data-pc-section","firstfocusableelement"),K.$_pfocustrap_firsthiddenfocusableelement=A,K.$_pfocustrap_focusableselector=B,K.setAttribute("data-pc-section","lastfocusableelement"),e.prepend(A),e.append(K)}}});function yn(){fe({variableName:Gt("scrollbar.width").name})}function wn(){pe({variableName:Gt("scrollbar.width").name})}var kn=`
    .p-drawer {
        display: flex;
        flex-direction: column;
        transform: translate3d(0px, 0px, 0px);
        position: relative;
        transition: transform 0.3s;
        background: dt('drawer.background');
        color: dt('drawer.color');
        border: 1px solid dt('drawer.border.color');
        box-shadow: dt('drawer.shadow');
    }

    .p-drawer-content {
        overflow-y: auto;
        flex-grow: 1;
        padding: dt('drawer.content.padding');
    }

    .p-drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        padding: dt('drawer.header.padding');
    }

    .p-drawer-footer {
        padding: dt('drawer.footer.padding');
    }

    .p-drawer-title {
        font-weight: dt('drawer.title.font.weight');
        font-size: dt('drawer.title.font.size');
    }

    .p-drawer-full .p-drawer {
        transition: none;
        transform: none;
        width: 100vw !important;
        height: 100vh !important;
        max-height: 100%;
        top: 0px !important;
        left: 0px !important;
        border-width: 1px;
    }

    .p-drawer-left .p-drawer-enter-from,
    .p-drawer-left .p-drawer-leave-to {
        transform: translateX(-100%);
    }

    .p-drawer-right .p-drawer-enter-from,
    .p-drawer-right .p-drawer-leave-to {
        transform: translateX(100%);
    }

    .p-drawer-top .p-drawer-enter-from,
    .p-drawer-top .p-drawer-leave-to {
        transform: translateY(-100%);
    }

    .p-drawer-bottom .p-drawer-enter-from,
    .p-drawer-bottom .p-drawer-leave-to {
        transform: translateY(100%);
    }

    .p-drawer-full .p-drawer-enter-from,
    .p-drawer-full .p-drawer-leave-to {
        opacity: 0;
    }

    .p-drawer-full .p-drawer-enter-active,
    .p-drawer-full .p-drawer-leave-active {
        transition: opacity 400ms cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .p-drawer-left .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-end-width: 1px;
    }

    .p-drawer-right .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-start-width: 1px;
    }

    .p-drawer-top .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-end-width: 1px;
    }

    .p-drawer-bottom .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-start-width: 1px;
    }

    .p-drawer-left .p-drawer-content,
    .p-drawer-right .p-drawer-content,
    .p-drawer-top .p-drawer-content,
    .p-drawer-bottom .p-drawer-content {
        width: 100%;
        height: 100%;
    }

    .p-drawer-open {
        display: flex;
    }

    .p-drawer-mask:dir(rtl) {
        flex-direction: row-reverse;
    }
`,xn={mask:function(e){var o=e.position,a=e.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:o==="left"?"flex-start":o==="right"?"flex-end":"center",alignItems:o==="top"?"flex-start":o==="bottom"?"flex-end":"center",pointerEvents:a?"auto":"none"}},root:{pointerEvents:"auto"}},_n={mask:function(e){var o=e.instance,a=e.props,p=["left","right","top","bottom"],c=p.find(function(v){return v===a.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter":a.modal,"p-drawer-open":o.containerVisible,"p-drawer-full":o.fullScreen},c?"p-drawer-".concat(c):""]},root:function(e){var o=e.instance;return["p-drawer p-component",{"p-drawer-full":o.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},Cn=yt.extend({name:"drawer",style:kn,classes:_n,inlineStyles:xn}),Sn={name:"BaseDrawer",extends:Bt,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:Cn,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function pt(t){"@babel/helpers - typeof";return pt=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},pt(t)}function Pt(t,e,o){return(e=$n(e))in t?Object.defineProperty(t,e,{value:o,enumerable:!0,configurable:!0,writable:!0}):t[e]=o,t}function $n(t){var e=Ln(t,"string");return pt(e)=="symbol"?e:e+""}function Ln(t,e){if(pt(t)!="object"||!t)return t;var o=t[Symbol.toPrimitive];if(o!==void 0){var a=o.call(t,e);if(pt(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var Jt={name:"Drawer",extends:Sn,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(e){e&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&Lt.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&Lt.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&he(this.mask,"p-overlay-mask-leave"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&Lt.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(e){this.dismissable&&this.modal&&this.mask===e.target&&this.hide()},focus:function(){var e=function(p){return p&&p.querySelector("[autofocus]")},o=this.$slots.header&&e(this.headerContainer);o||(o=this.$slots.default&&e(this.container),o||(o=this.$slots.footer&&e(this.footerContainer),o||(o=this.closeButton))),o&&lt(o)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&yn()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&wn()},onKeydown:function(e){e.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(e){this.container=e},maskRef:function(e){this.mask=e},contentRef:function(e){this.content=e},headerContainerRef:function(e){this.headerContainer=e},footerContainerRef:function(e){this.footerContainer=e},closeButtonRef:function(e){this.closeButton=e?e.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var e=this;this.outsideClickListener||(this.outsideClickListener=function(o){e.isOutsideClicked(o)&&e.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(e){return this.container&&!this.container.contains(e.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return it(Pt(Pt(Pt({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:mn},components:{Button:Wt,Portal:Pe,TimesIcon:Le}},Pn=["data-p"],En=["role","aria-modal","data-p"];function Bn(t,e,o,a,p,c){var v=mt("Button"),y=mt("Portal"),k=Yt("focustrap");return l(),j(y,null,{default:J(function(){return[p.containerVisible?(l(),d("div",w({key:0,ref:c.maskRef,onMousedown:e[0]||(e[0]=function(){return c.onMaskClick&&c.onMaskClick.apply(c,arguments)}),class:t.cx("mask"),style:t.sx("mask",!0,{position:t.position,modal:t.modal}),"data-p":c.dataP},t.ptm("mask")),[gt(Et,w({name:"p-drawer",onEnter:c.onEnter,onAfterEnter:c.onAfterEnter,onBeforeLeave:c.onBeforeLeave,onLeave:c.onLeave,onAfterLeave:c.onAfterLeave,appear:""},t.ptm("transition")),{default:J(function(){return[t.visible?U((l(),d("div",w({key:0,ref:c.containerRef,class:t.cx("root"),style:t.sx("root"),role:t.modal?"dialog":"complementary","aria-modal":t.modal?!0:void 0,"data-p":c.dataP},t.ptmi("root")),[t.$slots.container?F(t.$slots,"container",{key:0,closeCallback:c.hide}):(l(),d(_,{key:1},[n("div",w({ref:c.headerContainerRef,class:t.cx("header")},t.ptm("header")),[F(t.$slots,"header",{class:m(t.cx("title"))},function(){return[t.header?(l(),d("div",w({key:0,class:t.cx("title")},t.ptm("title")),f(t.header),17)):h("",!0)]}),t.showCloseIcon?F(t.$slots,"closebutton",{key:0,closeCallback:c.hide},function(){return[gt(v,w({ref:c.closeButtonRef,type:"button",class:t.cx("pcCloseButton"),"aria-label":c.closeAriaLabel,unstyled:t.unstyled,onClick:c.hide},t.closeButtonProps,{pt:t.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:J(function(x){return[F(t.$slots,"closeicon",{},function(){return[(l(),j(Zt(t.closeIcon?"span":"TimesIcon"),w({class:[t.closeIcon,x.class]},t.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):h("",!0)],16),n("div",w({ref:c.contentRef,class:t.cx("content")},t.ptm("content")),[F(t.$slots,"default")],16),t.$slots.footer?(l(),d("div",w({key:0,ref:c.footerContainerRef,class:t.cx("footer")},t.ptm("footer")),[F(t.$slots,"footer")],16)):h("",!0)],64))],16,En)),[[k]]):h("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,Pn)):h("",!0)]}),_:3})}Jt.render=Bn;const In={class:"header"},Fn={class:"header-left"},jn=["src"],Mn={class:"fw-bold"},An={class:"nav user-menu"},On={key:0,class:"nav-item"},zn=["data-feather"],Dn={class:"nav-item"},Rn={class:"nav-item dropdown"},Tn={href:"javascript:void(0);",class:"dropdown-toggle nav-link","data-bs-toggle":"dropdown"},Un={key:0,class:"badge rounded-pill bg-danger"},Vn={class:"dropdown-menu dropdown-menu-end notifications shadow-lg"},Kn={class:"topnav-dropdown-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom"},Nn={key:0,class:"text-primary"},qn={class:"noti-content max-h-[350px] overflow-y-auto"},Hn=["onClick"],Yn={class:"flex-grow-1 me-2"},Zn={class:"fw-semibold text-sm text-gray-800"},Gn={class:"text-muted mt-1 d-block",style:{"font-size":"0.7rem"}},Xn={key:0,class:"align-self-start"},Wn={key:1,class:"p-3 text-center text-muted small"},Jn={class:"nav-item dropdown has-arrow main-drop cursor-pointer","data-bs-toggle":"modal","data-bs-target":"#verifyPasswordModal"},Qn={class:"ms-2 d-none d-sm-block"},to={class:"fw-bold text-black"},eo={class:"super-admin text-black"},no={key:0,class:"sidebar",id:"sidebar","aria-label":"Primary"},oo={class:"sidebar-inner"},ro={id:"sidebar-menu",class:"sidebar-menu px-2"},ao={class:"mb-3"},so=["onClick"],lo=["data-feather"],io={class:"truncate-when-mini"},uo={key:0,class:"mt-3 mb-1 px-3 text-muted text-uppercase small section-title truncate-when-mini"},co={key:0,class:"dropdown-parent"},bo=["onClick","title"],po=["data-feather"],fo={class:"flex-grow-1 text-start truncate-when-mini"},ho=["data-feather"],vo=["onClick"],go=["data-feather"],mo=["onClick","title"],yo=["data-feather"],wo={class:"truncate-when-mini"},ko={key:2,class:"side-link"},xo=["onClick","title"],_o=["data-feather"],Co={class:"truncate-when-mini"},So={class:"flex flex-col h-full bg-white dark:bg-gray-900"},$o={class:"flex items-center justify-between px-4 pt-4 pb-3 border-b dark:border-gray-700"},Lo={class:"flex items-center gap-3"},Po=["src"],Eo={class:"font-bold text-lg text-black dark:text-white"},Bo=["onClick"],Io={class:"overflow-y-auto flex-1 px-2 py-3"},Fo={class:"list-none p-0 m-0"},jo=["onClick"],Mo=["data-feather"],Ao={class:"font-medium"},Oo={key:0,class:"drawer-section-title mt-4 mb-2 px-4 text-dark text-xs font-semibold uppercase tracking-wider"},zo={key:0,class:"mb-1"},Do=["onClick"],Ro={class:"flex items-center"},To=["data-feather"],Uo={class:"font-medium"},Vo=["data-feather"],Ko={class:"list-none pl-8 mt-1 space-y-1"},No=["onClick"],qo=["data-feather"],Ho=["onClick"],Yo=["data-feather"],Zo={class:"font-medium"},Go={key:2,class:"mb-1"},Xo=["onClick"],Wo=["data-feather"],Jo={class:"font-medium"},Qo={key:4,class:"position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50",style:{"z-index":"9999"}},tr={class:"content bg-white dark:bg-gray-900 text-black dark:text-white"},er={class:"modal fade",id:"verifyPasswordModal",tabindex:"-1","aria-hidden":"true"},nr={class:"modal-dialog modal-dialog-centered"},or={class:"modal-content text-black rounded-4"},rr={class:"modal-body"},ar={class:"mb-3"},sr={key:0,class:"invalid-feedback"},lr={class:"mb-3"},ir={key:0,class:"invalid-feedback"},dr={class:"modal-footer border-0"},ur=["disabled"],cr={key:0,class:"spinner-border spinner-border-sm me-2"},br={class:"modal fade",id:"userProfileModal",tabindex:"-1","aria-hidden":"true"},pr={class:"modal-dialog modal-dialog-centered modal-lg"},fr={class:"modal-content text-black rounded-4"},hr={class:"modal-body"},vr={class:"row g-3"},gr={class:"col-md-6"},mr={key:0,class:"invalid-feedback"},yr={class:"col-md-6"},wr={key:0,class:"invalid-feedback"},kr={class:"col-md-6"},xr={key:0,class:"invalid-feedback"},_r={class:"col-md-6"},Cr={key:0,class:"invalid-feedback"},Fr={__name:"Master",setup(t){const e=ve(),o=g(!1),a=g(!1),p=X(()=>e.props.current_user),c=X(()=>p.value?.roles?.includes("Cashier"));console.log("isCashierRole:",e.props.current_user?.roles),c.value&&He();const v=s=>{console.log("Sidebar action triggered:",s),s==="systemRestore"?(k.value=!0,console.log("showConfirmRestore set to:",o.value)):s==="databaseBackup"&&(x.value=!0)},y=g(!1),k=g(!1),x=g(!1),B=g(!1),C=X(()=>(nt.value.roles||[]).includes("Cashier")),A=()=>{document.fullscreenElement?document.exitFullscreen&&(document.exitFullscreen(),B.value=!1):(document.documentElement.requestFullscreen(),B.value=!0)},K=()=>{B.value=!!document.fullscreenElement,C.value&&!document.fullscreenElement&&setTimeout(()=>{document.documentElement.requestFullscreen().catch(s=>{console.warn("Could not re-enter fullscreen:",s)})},100)},wt=s=>{if(s.key==="Escape"&&C.value&&document.fullscreenElement)return s.preventDefault(),s.stopPropagation(),!1},et=async()=>{if(C.value)try{await Ot(),setTimeout(async()=>{document.fullscreenElement||(await document.documentElement.requestFullscreen(),B.value=!0)},500)}catch(s){console.error("Failed to enter fullscreen:",s),P.warning("Please allow fullscreen mode for better experience")}};ge(a,s=>{s&&Ot(()=>{window.feather?.replace()})}),document.addEventListener("fullscreenchange",()=>{B.value=!!document.fullscreenElement});const ft=g(!1),Qt=async()=>{y.value=!1,ft.value=!0,await new Promise(s=>setTimeout(s,800));try{await V.post(route("logout"))}finally{ft.value=!1}},te=async()=>{try{(await axios.post(route("system.restore"))).data.success&&(P.success("System restored successfully!"),o.value=!1,window.location.href=route("front-page"))}catch(s){console.error("System restore error:",s);let r="Failed to restore system. Please try again.";s.response?s.response.data?.message?r=s.response.data.message:s.response.data?.error?r=s.response.data.error:r=`Error ${s.response.status}: ${s.response.statusText}`:s.message&&(r=s.message),P.error(r)}},ee=async()=>{try{const s=await axios.post(route("database.backup"),{},{responseType:"blob"}),r=window.URL.createObjectURL(new Blob([s.data])),i=document.createElement("a");i.href=r;const u=s.headers["content-disposition"];let b="database_backup_"+new Date().toISOString().slice(0,10)+".sql";if(u){const L=u.match(/filename="?(.+)"?/i);L&&(b=L[1])}i.setAttribute("download",b),document.body.appendChild(i),i.click(),i.remove(),window.URL.revokeObjectURL(r),P.success("Database backup downloaded successfully!"),x.value=!1}catch(s){console.error("Database backup error:",s);let r="Failed to create database backup. Please try again.";if(s.response?.data)if(s.response.data instanceof Blob)try{const i=await s.response.data.text();r=JSON.parse(i).message||r}catch{r="An error occurred while creating the backup."}else s.response.data.message&&(r=s.response.data.message);else s.message&&(r=s.message);P.error(r),x.value=!1}},It=X(()=>e.props.current_user?.permissions??[]),ne=X(()=>e.props.current_user?.roles??[]),I=s=>!s||ne.value.includes("Super Admin")?!0:(Array.isArray(It.value)?It.value:[]).includes(s),S=g({}),nt=X(()=>e.props.current_user??{}),ht=X(()=>e.props.business_info??{}),Ft=ke({selector:"html",attribute:"class",valueDark:"dark",valueLight:"light"}),oe=xe(Ft);st(()=>{window.feather?.replace()});const T=g({username:"",password:""}),$=g({}),ot=g(!1),kt=()=>{T.value={username:"",password:""},$.value={}},xt=async()=>{if($.value={},!T.value.username){$.value.username="Username is required",P.error("Username is required");return}if(!T.value.password){$.value.password="Password is required",P.error("Password is required");return}ot.value=!0;try{(await axios.post("/api/profile/verify-credentials",{username:T.value.username,password:T.value.password})).data.success&&(bootstrap.Modal.getInstance(document.getElementById("verifyPasswordModal")).hide(),kt(),P.success("Identity verified successfully"),setTimeout(()=>{new bootstrap.Modal(document.getElementById("userProfileModal")).show()},300))}catch(s){if(console.error("Verification error:",s),s.response?.status===422){$.value=s.response.data.errors||{};const r=Object.values($.value)[0];Array.isArray(r)?P.error(r[0]):P.error(r||"Validation error occurred")}else if(s.response?.status===401){const r="Invalid username or password";$.value.general=r,P.error(r)}else{const r=s.response?.data?.message||"An error occurred. Please try again.";$.value.general=r,P.error(r)}}finally{ot.value=!1}},_t=g([{label:"Dashboard",icon:"grid",route:"dashboard"},{section:"POS Management",children:[{label:"Inventory",icon:"package",children:[{label:"Items",icon:"box",route:"inventory.index"},{label:"Categories",icon:"layers",route:"inventory.categories.index"},{label:"Logs Moments",icon:"archive",route:"stock.logs.index"},{label:"Purchase Order",icon:"shopping-cart",route:"purchase.orders.index"},{label:"Reference Management",icon:"database",route:"reference.index"}]},{label:"Menu",icon:"book-open",children:[{label:"Categories",icon:"layers",route:"menu-categories.index"},{label:"Menus",icon:"box",route:"menu.index"}]},{label:"Sale",icon:"shopping-bag",route:"pos.order"},{label:"Orders",icon:"list",route:"orders.index"},{label:"KOT",icon:"clipboard",route:"kots.index"},{label:"Payment",icon:"credit-card",route:"payment.index"},{label:"Analytics",icon:"bar-chart-2",route:"analytics.index"},{label:"Promo",icon:"tag",route:"promos.index"},{label:"Meals",icon:"coffee",route:"meals.index"},{label:"Discount",icon:"percent",route:"discounts.index"},{label:"Addons",icon:"plus-square",children:[{label:"Addon Groups",icon:"layers",route:"addon-groups.index"},{label:"Addons",icon:"plus-circle",route:"addons.index"}]},{label:"Shift Management",icon:"users",route:"shift.index"},{label:"Settings",icon:"settings",route:"settings.index"},{label:"Restore System",icon:"refresh-cw",action:"systemRestore"},{label:"Backup Database",icon:"database",action:"databaseBackup"}]}]),H=s=>{try{return route().current(s)}catch{return!1}},O=g(new Set),jt=s=>{O.value.has(s)?O.value.delete(s):O.value.add(s)},N=(s=[])=>s.some(r=>r.children?N(r.children):H(r.route)),re=()=>{const s=(r=[])=>{r.forEach(i=>{i.children?.length&&(N(i.children)&&O.value.add(i.label),s(i.children))})};_t.value.forEach(r=>r.children&&s(r.children))},Ct=g("desktop");g(!0);const z=g(!1),q=g(!1),Q=g(!0),Y=g(!0),rt=g(!1),St=()=>{const s=window.innerWidth;s<768?(Ct.value="mobile",z.value=!0,q.value=!1,Q.value=!1,rt.value=!1,Y.value=!1):s<992?(Ct.value="tablet",z.value=!1,q.value=!0,Q.value=!1,Y.value=!1,rt.value=!1):(Ct.value="desktop",z.value=!1,q.value=!1,Q.value=!0,Y.value=!0,rt.value=!1)},Mt=()=>{z.value||q.value?a.value=!a.value:Y.value=!Y.value},$t=s=>{s&&(V.visit(route(s)),(z.value||q.value)&&(a.value=!1))},ae=s=>{v(s),(z.value||q.value)&&(a.value=!1)};st(()=>{document.addEventListener("fullscreenchange",K),document.addEventListener("keydown",wt,!0),et(),St(),window.addEventListener("resize",St,{passive:!0}),window.feather?.replace(),re()}),me(()=>{document.removeEventListener("fullscreenchange",K),document.removeEventListener("keydown",wt,!0),window.removeEventListener("resize",St)}),ye(()=>window.feather?.replace());const M=g({username:nt.value.name??"",password:"",pin:"",role:nt.value.roles[0]??""}),se=async()=>{try{(await axios.post("/api/profile/update",M.value)).data.success?(P.success("Profile updated successfully"),bootstrap.Modal.getInstance(document.getElementById("userProfileModal")).hide()):alert("Something went wrong!")}catch(s){s?.response?.status===422&&s.response.data?.errors&&(S.value=s.response.data.errors,P.error("Please fill in all required fields correctly."))}};st(()=>{document.getElementById("userProfileModal").addEventListener("hidden.bs.modal",()=>{S.value={},M.value.password="",M.value.pin=""})});const Z=g([]),G=g(0),le=async()=>{const s=await axios.get("/api/notifications");Z.value=s.data,console.log("notifications.value",Z.value),G.value=Z.value.filter(r=>!r.is_read).length},ie=async s=>{try{await axios.post(`/api/notifications/mark-as-read/${s}`);const r=Z.value.find(i=>i.id===s);r&&!r.is_read&&(r.is_read=!0,G.value--)}catch(r){console.error("Error marking as read:",r)}},de=async()=>{try{await axios.post("/api/notifications/mark-all-as-read"),Z.value.forEach(s=>s.is_read=!0),G.value=0}catch(s){console.error("Error marking all as read:",s)}};return st(le),(s,r)=>(l(),d(_,null,[n("div",{class:m(["layout-root",{"state-desktop":Q.value,"state-tablet":q.value,"state-mobile":z.value,"sidebar-open":z.value?rt.value:Y.value,"sidebar-collapsed":(Q.value||q.value)&&!Y.value,"sidebar-overlay":z.value}])},[n("header",In,[n("div",Fn,[n("img",{src:ht.value.image_url,alt:"logo",width:"50",height:"50px",class:"rounded-full border shadow"},null,8,jn),n("h5",Mn,f(ht.value.business_name),1),n("button",{class:"icon-btn",onClick:Mt,"aria-label":"Toggle sidebar"},[...r[13]||(r[13]=[n("i",{"data-feather":"menu"},null,-1)])])]),r[21]||(r[21]=n("div",{class:"header-center"},null,-1)),n("ul",An,[n("button",{class:"btn btn-primary rounded-pill py-2 px-3",onClick:r[0]||(r[0]=i=>R(V).visit("/pos/order"))}," Quick Order "),n("button",{class:"btn btn-danger rounded-pill py-2 px-3 d-flex align-items-center",onClick:r[1]||(r[1]=i=>y.value=!0)},[...r[14]||(r[14]=[vt(" Logout ",-1),n("svg",{xmlns:"http://www.w3.org/2000/svg",width:"18",height:"18",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round",class:"feather feather-log-out ms-2"},[n("path",{d:"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"}),n("polyline",{points:"16 17 21 12 16 7"}),n("line",{x1:"21",y1:"12",x2:"9",y2:"12"})],-1)])]),C.value?h("",!0):(l(),d("li",On,[n("button",{class:"icon-btn",onClick:A,title:"Toggle Fullscreen"},[n("i",{"data-feather":B.value?"minimize":"maximize"},null,8,zn)])])),n("li",Dn,[n("button",{class:"icon-btn",onClick:r[2]||(r[2]=i=>R(oe)())},[R(Ft)?(l(),j(R(Ee),{key:0,size:20})):(l(),j(R(Be),{key:1,size:20}))])]),n("li",Rn,[n("a",Tn,[r[15]||(r[15]=n("img",{src:"/assets/img/icons/notification-bing.svg",alt:"noti"},null,-1)),G.value>0?(l(),d("span",Un,f(G.value),1)):h("",!0)]),n("div",Vn,[n("div",Kn,[r[17]||(r[17]=n("span",{class:"notification-title fw-bold"},"Notifications",-1)),n("a",{href:"javascript:void(0)",class:"text-primary fw-semibold",style:{"font-size":"0.9rem"},onClick:Dt(de,["stop"])},[r[16]||(r[16]=vt(" Mark all as read ",-1)),G.value>0?(l(),d("span",Nn,"("+f(G.value)+")",1)):h("",!0)])]),n("div",qn,[Z.value.length?(l(!0),d(_,{key:0},W(Z.value,i=>(l(),d("div",{key:i.id,class:m(["d-flex align-items-start justify-content-between p-3 border-bottom cursor-pointer transition-all",i.is_read?"bg-gray-50 m-2 mb-2":"bg-white shadow-sm m-2"]),onClick:Dt(u=>ie(i.id),["stop"])},[n("div",Yn,[n("div",Zn,f(i.message),1),n("span",{class:m(["inline-flex notifi-span items-center rounded-full px-2 py-0.5 text-xs font-medium mt-1",{"text-red-700 bg-red-300":i.status?.toLowerCase()==="out_of_stock","text-yellow-700 bg-yellow-100":i.status?.toLowerCase()==="low_stock","text-orange-700 bg-orange-200":i.status?.toLowerCase()==="expired","text-blue-700 bg-blue-100":i.status?.toLowerCase()==="near_expiry"}])},f(i.status.replace(/_/g," ").toUpperCase()),3),n("small",Gn,f(new Date(i.created_at).toLocaleString("en-US",{dateStyle:"medium",timeStyle:"short"})),1)]),i.is_read?h("",!0):(l(),d("div",Xn,[...r[18]||(r[18]=[n("span",{class:"inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full new-badge bg-green-100 text-green-600"}," NEW ",-1)])]))],10,Hn))),128)):(l(),d("div",Wn,"No notifications"))])])]),n("li",Jn,[r[20]||(r[20]=n("span",{class:"user-img"},[n("i",{class:"bi bi-person-circle"})],-1)),n("div",Qn,[n("b",to,f(nt.value.name),1),r[19]||(r[19]=n("br",null,null,-1)),n("small",eo,f(nt.value.roles[0]),1)])])])]),Q.value?(l(),d("aside",no,[n("div",oo,[n("div",ro,[n("ul",ao,[(l(!0),d(_,null,W(_t.value,i=>(l(),d(_,{key:i.label||i.section},[!i.section&&I(i.route)?(l(),d("li",{key:0,class:m({active:H(i.route)})},[n("button",{class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0 text-start",onClick:u=>R(V).visit(s.route(i.route))},[n("i",{"data-feather":i.icon,class:"me-2 icons"},null,8,lo),n("span",io,f(i.label),1)],8,so)],2)):(l(),d(_,{key:1},[i.section&&i.children.some(u=>I(u.route))?(l(),d("li",uo,f(i.section),1)):h("",!0),(l(!0),d(_,null,W(i.children,u=>(l(),d(_,{key:u.label},[u.children&&u.children.length&&u.children.some(b=>I(b.route))?(l(),d("li",co,[n("button",{class:m(["d-flex align-items-center side-link px-3 py-2 w-100 border-0",{active:O.value.has(u.label)||N(u.children)}]),onClick:b=>jt(u.label),type:"button",title:u.label},[n("i",{"data-feather":u.icon,class:"me-2"},null,8,po),n("span",fo,f(u.label),1),n("i",{class:"chevron-icon","data-feather":O.value.has(u.label)||N(u.children)?"chevron-up":"chevron-down"},null,8,ho)],10,bo),n("ul",{class:m(["list-unstyled my-1 submenu-dropdown",{expanded:O.value.has(u.label)||N(u.children)}])},[I(s.child?.route)?(l(!0),d(_,{key:0},W(u.children,b=>(l(),d("li",{key:b.label,class:m({active:H(b.route)})},[n("button",{class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0 text-start",onClick:L=>R(V).visit(s.route(b.route))},[n("i",{"data-feather":b.icon,class:"me-2"},null,8,go),n("span",null,f(b.label),1)],8,vo)],2))),128)):h("",!0)],2)])):u.route&&I(u.route)?(l(),d("li",{key:1,class:m([{active:u.route?H(u.route):!1},"side-link"])},[n("button",{class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0 text-start",onClick:b=>R(V).visit(s.route(u.route)),title:u.label},[n("i",{"data-feather":u.icon,class:"me-2"},null,8,yo),n("span",wo,f(u.label),1)],8,mo)],2)):u.action&&I(u.action)?(l(),d("li",ko,[n("button",{onClick:b=>v(u.action),class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0",title:u.label},[n("i",{"data-feather":u.icon,class:"me-2"},null,8,_o),n("span",Co,f(u.label),1)],8,xo)])):h("",!0)],64))),128))],64))],64))),128))])])])])):h("",!0),gt(R(Jt),{visible:a.value,"onUpdate:visible":r[3]||(r[3]=i=>a.value=i),position:"left",class:"sidebar-drawer"},{container:J(({closeCallback:i})=>[n("div",So,[n("div",$o,[n("div",Lo,[n("img",{src:ht.value.image_url,alt:"logo",width:"40",height:"40",class:"rounded-full border shadow"},null,8,Po),n("span",Eo,f(ht.value.business_name),1)]),n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110",onClick:i,"data-bs-dismiss":"modal","aria-label":"Close",title:"Close"},[...r[22]||(r[22]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])],8,Bo)]),n("div",Io,[n("ul",Fo,[(l(!0),d(_,null,W(_t.value,u=>(l(),d(_,{key:u.label||u.section},[!u.section&&I(u.route)?(l(),d("li",{key:0,class:m([{active:H(u.route)},"mb-1"])},[n("button",{class:"drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors",onClick:b=>$t(u.route)},[n("i",{"data-feather":u.icon,class:"w-5 h-5 mr-3"},null,8,Mo),n("span",Ao,f(u.label),1)],8,jo)],2)):(l(),d(_,{key:1},[u.section&&u.children.some(b=>I(b.route))?(l(),d("li",Oo,f(u.section),1)):h("",!0),(l(!0),d(_,null,W(u.children,b=>(l(),d(_,{key:b.label},[b.children&&b.children.length&&b.children.some(L=>I(L.route))?(l(),d("li",zo,[n("button",{class:m(["drawer-link w-full flex items-center justify-between px-4 py-3 rounded-lg text-left transition-colors",{active:O.value.has(b.label)||N(b.children)}]),onClick:L=>jt(b.label),type:"button"},[n("div",Ro,[n("i",{"data-feather":b.icon,class:"w-5 h-5 mr-3"},null,8,To),n("span",Uo,f(b.label),1)]),n("i",{class:"chevron-icon","data-feather":O.value.has(b.label)||N(b.children)?"chevron-up":"chevron-down"},null,8,Vo)],10,Do),U(n("ul",Ko,[I(s.child?.route)?(l(!0),d(_,{key:0},W(b.children,L=>(l(),d("li",{key:L.label,class:m({active:H(L.route)})},[n("button",{class:"drawer-link w-full flex items-center px-4 py-2 rounded-lg text-left text-sm transition-colors",onClick:Sr=>$t(L.route)},[n("i",{"data-feather":L.icon,class:"w-4 h-4 mr-3"},null,8,qo),n("span",null,f(L.label),1)],8,No)],2))),128)):h("",!0)],512),[[we,O.value.has(b.label)||N(b.children)]])])):b.route&&I(b.route)?(l(),d("li",{key:1,class:m([{active:H(b.route)},"mb-1"])},[n("button",{class:"drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors",onClick:L=>$t(b.route)},[n("i",{"data-feather":b.icon,class:"w-5 h-5 mr-3"},null,8,Yo),n("span",Zo,f(b.label),1)],8,Ho)],2)):b.action&&I(b.action)?(l(),d("li",Go,[n("button",{class:"drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors",onClick:L=>ae(b.action)},[n("i",{"data-feather":b.icon,class:"w-5 h-5 mr-3"},null,8,Wo),n("span",Jo,f(b.label),1)],8,Xo)])):h("",!0)],64))),128))],64))],64))),128))])])])]),_:1},8,["visible"]),k.value?(l(),j(Tt,{key:1,show:k.value,title:"Confirm System Restore",message:"Are you sure you want to restore the system? This will reset all data to default settings. This action cannot be undone.",confirmLabel:"Yes, Restore",onConfirm:te,onCancel:r[4]||(r[4]=i=>k.value=!1)},null,8,["show"])):h("",!0),x.value?(l(),j(Tt,{key:2,show:x.value,title:"Confirm Database Backup",message:"Are you sure you want to create a database backup? The backup file will be downloaded to your computer.",confirmLabel:"Yes, Backup",onConfirm:ee,onCancel:r[5]||(r[5]=i=>x.value=!1)},null,8,["show"])):h("",!0),y.value?(l(),j(ze,{key:3,show:y.value,loading:ft.value,onConfirm:Qt,onCancel:r[6]||(r[6]=i=>y.value=!1)},null,8,["show","loading"])):h("",!0),ft.value?(l(),d("div",Qo,[...r[23]||(r[23]=[n("div",{class:"spinner-border text-light",role:"status",style:{width:"3rem",height:"3rem"}},null,-1)])])):h("",!0),z.value&&rt.value?(l(),d("div",{key:5,class:"overlay-backdrop","aria-hidden":"true",onClick:Mt})):h("",!0),n("main",tr,[F(s.$slots,"default")])],2),r[34]||(r[34]=zt('<footer class="footer bg-white dark:bg-gray-800 border-top sticky bottom-0"><div class="container-fluid"><div class="row align-items-center py-3"><div class="col-md-4"></div><div class="col-md-4 text-center"><span class="text-muted"> Powered by <strong class="text-primary">10XGLOBAL</strong></span></div><div class="col-md-4 text-end"><a href="#" class="text-decoration-none hover:text-primary-dark"> Need Help? <strong class="text-primary">Contact Us</strong></a></div></div></div></footer>',1)),n("div",er,[n("div",nr,[n("div",or,[n("div",{class:"modal-header border-0"},[r[25]||(r[25]=n("h5",{class:"modal-title fw-bold"},"Verify Your Identity",-1)),n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110","data-bs-dismiss":"modal","aria-label":"Close",title:"Close",onClick:kt},[...r[24]||(r[24]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])])]),n("div",rr,[r[28]||(r[28]=n("p",{class:"text-muted mb-3"},"Please verify your credentials to access profile settings",-1)),n("div",ar,[r[26]||(r[26]=n("label",{class:"form-label"},"Username",-1)),U(n("input",{type:"text",class:m(["form-control",{"is-invalid":$.value.username}]),"onUpdate:modelValue":r[7]||(r[7]=i=>T.value.username=i),onKeyup:Rt(xt,["enter"]),placeholder:"Enter your username"},null,34),[[tt,T.value.username]]),$.value.username?(l(),d("div",sr,f($.value.username),1)):h("",!0)]),n("div",lr,[r[27]||(r[27]=n("label",{class:"form-label"},"Password",-1)),U(n("input",{type:"password",class:m(["form-control",{"is-invalid":$.value.password}]),"onUpdate:modelValue":r[8]||(r[8]=i=>T.value.password=i),onKeyup:Rt(xt,["enter"]),placeholder:"Enter your password"},null,34),[[tt,T.value.password]]),$.value.password?(l(),d("div",ir,f($.value.password),1)):h("",!0)])]),n("div",dr,[n("button",{type:"button",class:"btn btn-secondary rounded-pill px-2 py-2","data-bs-dismiss":"modal",onClick:kt}," Cancel "),n("button",{type:"button",class:"btn btn-primary rounded-pill px-2 py-2",onClick:xt,disabled:ot.value},[ot.value?(l(),d("span",cr)):h("",!0),vt(" "+f(ot.value?"Verifying...":"Verify"),1)],8,ur)])])])]),n("div",br,[n("div",pr,[n("div",fr,[r[33]||(r[33]=zt('<div class="modal-header border-0"><h5 class="modal-title fw-bold">User Profile</h5><button class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110" data-bs-dismiss="modal" aria-label="Close" title="Close"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>',1)),n("div",hr,[n("div",vr,[n("div",gr,[r[29]||(r[29]=n("label",{class:"form-label"},"UserName",-1)),U(n("input",{type:"text",class:m(["form-control",{"is-invalid":S.value.username}]),"onUpdate:modelValue":r[9]||(r[9]=i=>M.value.username=i)},null,2),[[tt,M.value.username]]),S.value.username?(l(),d("div",mr,f(S.value.username[0]),1)):h("",!0)]),n("div",yr,[r[30]||(r[30]=n("label",{class:"form-label"},"Password",-1)),U(n("input",{type:"password",class:m(["form-control",{"is-invalid":S.value.password}]),placeholder:"Enter new password (leave blank to keep current)","onUpdate:modelValue":r[10]||(r[10]=i=>M.value.password=i)},null,2),[[tt,M.value.password]]),S.value.password?(l(),d("div",wr,f(S.value.password[0]),1)):h("",!0)]),n("div",kr,[r[31]||(r[31]=n("label",{class:"form-label"},"Pin",-1)),U(n("input",{type:"text",class:m(["form-control",{"is-invalid":S.value.pin}]),placeholder:"Enter new PIN (leave blank to keep current)","onUpdate:modelValue":r[11]||(r[11]=i=>M.value.pin=i)},null,2),[[tt,M.value.pin]]),S.value.pin?(l(),d("div",xr,f(S.value.pin[0]),1)):h("",!0)]),n("div",_r,[r[32]||(r[32]=n("label",{class:"form-label"},"Role",-1)),U(n("input",{type:"text",class:m(["form-control",{"is-invalid":S.value.role}]),"onUpdate:modelValue":r[12]||(r[12]=i=>M.value.role=i),readonly:""},null,2),[[tt,M.value.role]]),S.value.role?(l(),d("div",Cr,f(S.value.role[0]),1)):h("",!0)])])]),n("div",{class:"modal-footer border-0"},[n("button",{type:"button",class:"btn btn-primary py-2 px-2 w-30 rounded-pill",onClick:se}," Update ")])])])])],64))}};export{Fr as _};
