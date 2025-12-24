import{_ as Yt,a as F,b as i,d as W,g as d,l as h,f as n,t as f,V as Et,e as gt,u as A,o as st,z as ye,$ as K,a0 as wt,a1 as Gt,a2 as Zt,H as O,p as mt,a3 as w,E as yt,a4 as Qt,m as V,j as g,k as Xt,a5 as we,a6 as lt,a7 as at,a8 as ke,a9 as zt,aa as xe,ab as Wt,ac as _e,ad as Ce,F as C,A as Se,r as v,c as Y,w as Dt,n as Tt,Q as $e,I as Le,D as Rt,y as Ut,i as G,ae as Pe,v as tt,U as Vt,s as S}from"./app-Cc-dChBz.js";import{u as Ee,a as Be}from"./index-7lFnmn3u.js";import{c as Ie}from"./createLucideIcon-gbYBBr64.js";import{R as Me,s as Oe,B as Fe,a as je,b as Ae,x as Lt}from"./index-DZnH082G.js";import{s as Bt,f as it}from"./index-UELNzy9_.js";import{S as ze,M as De}from"./sun-4gNgzfwy.js";/**
 * @license lucide-vue-next v0.525.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const Te=Ie("refresh-ccw",[["path",{d:"M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8",key:"14sxne"}],["path",{d:"M3 3v5h5",key:"1xhq8a"}],["path",{d:"M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16",key:"1hlbsb"}],["path",{d:"M16 16h5v5",key:"ccwih5"}]]),Re={key:0,class:"fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"},Ue={class:"bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative"},Ve={class:"text-center text-lg font-medium text-gray-800 mb-2"},Ke={class:"text-center text-sm text-gray-500 mb-6"},Ne={__name:"LogoutModal",props:{title:{type:String,default:"Confirm Logout"},message:{type:String,default:"Are you sure you want to log out?"},show:{type:Boolean,default:!1}},emits:["confirm","cancel"],setup(t,{emit:e}){const r=e,s=()=>r("confirm"),p=()=>r("cancel");return(u,m)=>(i(),F(Et,{name:"fade-slide"},{default:W(()=>[t.show?(i(),d("div",Re,[n("div",Ue,[n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110",onClick:p,title:"Close"},[...m[0]||(m[0]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])]),m[1]||(m[1]=n("div",{class:"flex justify-center mb-4"},[n("div",{class:"bg-red-100 p-3 rounded-full"},[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"w-8 h-8 text-red-600",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"},[n("path",{d:"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"}),n("polyline",{points:"16 17 21 12 16 7"}),n("line",{x1:"21",y1:"12",x2:"9",y2:"12"})])])],-1)),n("h3",Ve,f(t.title),1),n("p",Ke,f(t.message),1),n("div",{class:"flex justify-center gap-3"},[n("button",{onClick:s,class:"btn btn-danger d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"}," Yes, Logout "),n("button",{onClick:p,class:"btn btn-secondary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"}," Cancel ")])])])):h("",!0)]),_:1}))}},qe=Yt(Ne,[["__scopeId","data-v-00f86d7b"]]),He={key:0,class:"fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"},Ye={class:"bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-drop-in relative"},Ge={class:"flex justify-center mb-4"},Ze={class:"bg-orange-100 p-3 rounded-full"},Qe={class:"text-center text-lg font-medium text-gray-800 mb-2"},Xe={class:"text-center text-sm text-gray-500 mb-6"},We={class:"flex justify-center gap-3"},Je={__name:"RestoreSystemModal",props:{show:{type:Boolean,required:!0},title:{type:String,default:"Confirm System Restore"},message:{type:String,default:"Are you sure you want to restore the system? This action cannot be undone."},confirmLabel:{type:String,default:"Yes, Restore"}},emits:["confirm","cancel"],setup(t,{emit:e}){const r=e,s=()=>{r("confirm")},p=()=>{r("cancel")};return(u,m)=>(i(),F(Et,{name:"fade-slide"},{default:W(()=>[t.show?(i(),d("div",He,[n("div",Ye,[n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110",onClick:p,title:"Close"},[...m[0]||(m[0]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])]),n("div",Ge,[n("div",Ze,[gt(A(Te),{class:"w-8 h-8 text-orange-600"})])]),n("h3",Qe,f(t.title),1),n("p",Xe,f(t.message),1),n("div",We,[n("button",{onClick:s,class:"btn btn-primary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"},f(t.confirmLabel),1),n("button",{onClick:p,class:"btn btn-secondary d-flex align-items-center gap-1 px-3 py-2 rounded-pill text-white"}," Cancel ")])])])):h("",!0)]),_:1}))}},Kt=Yt(Je,[["__scopeId","data-v-60cf45eb"]]);function tn(){let t=null;const e=async()=>{try{const r=await fetch("/check-auto-logout",{method:"GET",headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"},credentials:"same-origin"}),s=r.headers.get("content-type");if(s&&s.includes("text/html")){console.warn("Received HTML response - likely session expired"),t&&clearInterval(t),K.visit("/login",{method:"get",replace:!0});return}if(r.status===401){t&&clearInterval(t),K.visit("/login",{method:"get",replace:!0});return}if(!s||!s.includes("application/json")){const u=await r.text();console.warn("Non-JSON response received:",u.substring(0,200)),t&&clearInterval(t);return}(await r.json()).should_logout&&(t&&clearInterval(t),K.visit("/login",{method:"get",replace:!0}))}catch(r){console.error("Auto-logout check failed:",r)}};return st(()=>{e(),t=setInterval(e,1e4)}),ye(()=>{t&&clearInterval(t)}),{checkAutoLogout:e}}var en=`
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
`,nn={root:function(e){var r=e.props,s=e.instance;return["p-badge p-component",{"p-badge-circle":Zt(r.value)&&String(r.value).length===1,"p-badge-dot":Gt(r.value)&&!s.$slots.default,"p-badge-sm":r.size==="small","p-badge-lg":r.size==="large","p-badge-xl":r.size==="xlarge","p-badge-info":r.severity==="info","p-badge-success":r.severity==="success","p-badge-warn":r.severity==="warn","p-badge-danger":r.severity==="danger","p-badge-secondary":r.severity==="secondary","p-badge-contrast":r.severity==="contrast"}]}},on=wt.extend({name:"badge",style:en,classes:nn}),rn={name:"BaseBadge",extends:Bt,props:{value:{type:[String,Number],default:null},severity:{type:String,default:null},size:{type:String,default:null}},style:on,provide:function(){return{$pcBadge:this,$parentInstance:this}}};function dt(t){"@babel/helpers - typeof";return dt=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},dt(t)}function Nt(t,e,r){return(e=an(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function an(t){var e=sn(t,"string");return dt(e)=="symbol"?e:e+""}function sn(t,e){if(dt(t)!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var s=r.call(t,e);if(dt(s)!="object")return s;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var Jt={name:"Badge",extends:rn,inheritAttrs:!1,computed:{dataP:function(){return it(Nt(Nt({circle:this.value!=null&&String(this.value).length===1,empty:this.value==null&&!this.$slots.default},this.severity,this.severity),this.size,this.size))}}},ln=["data-p"];function dn(t,e,r,s,p,u){return i(),d("span",w({class:t.cx("root"),"data-p":u.dataP},t.ptmi("root")),[O(t.$slots,"default",{},function(){return[mt(f(t.value),1)]})],16,ln)}Jt.render=dn;var un=`
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
`;function ut(t){"@babel/helpers - typeof";return ut=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},ut(t)}function R(t,e,r){return(e=cn(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function cn(t){var e=bn(t,"string");return ut(e)=="symbol"?e:e+""}function bn(t,e){if(ut(t)!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var s=r.call(t,e);if(ut(s)!="object")return s;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var pn={root:function(e){var r=e.instance,s=e.props;return["p-button p-component",R(R(R(R(R(R(R(R(R({"p-button-icon-only":r.hasIcon&&!s.label&&!s.badge,"p-button-vertical":(s.iconPos==="top"||s.iconPos==="bottom")&&s.label,"p-button-loading":s.loading,"p-button-link":s.link||s.variant==="link"},"p-button-".concat(s.severity),s.severity),"p-button-raised",s.raised),"p-button-rounded",s.rounded),"p-button-text",s.text||s.variant==="text"),"p-button-outlined",s.outlined||s.variant==="outlined"),"p-button-sm",s.size==="small"),"p-button-lg",s.size==="large"),"p-button-plain",s.plain),"p-button-fluid",r.hasFluid)]},loadingIcon:"p-button-loading-icon",icon:function(e){var r=e.props;return["p-button-icon",R({},"p-button-icon-".concat(r.iconPos),r.label)]},label:"p-button-label"},fn=wt.extend({name:"button",style:un,classes:pn}),hn={name:"BaseButton",extends:Bt,props:{label:{type:String,default:null},icon:{type:String,default:null},iconPos:{type:String,default:"left"},iconClass:{type:[String,Object],default:null},badge:{type:String,default:null},badgeClass:{type:[String,Object],default:null},badgeSeverity:{type:String,default:"secondary"},loading:{type:Boolean,default:!1},loadingIcon:{type:String,default:void 0},as:{type:[String,Object],default:"BUTTON"},asChild:{type:Boolean,default:!1},link:{type:Boolean,default:!1},severity:{type:String,default:null},raised:{type:Boolean,default:!1},rounded:{type:Boolean,default:!1},text:{type:Boolean,default:!1},outlined:{type:Boolean,default:!1},size:{type:String,default:null},variant:{type:String,default:null},plain:{type:Boolean,default:!1},fluid:{type:Boolean,default:null}},style:fn,provide:function(){return{$pcButton:this,$parentInstance:this}}};function ct(t){"@babel/helpers - typeof";return ct=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},ct(t)}function B(t,e,r){return(e=vn(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function vn(t){var e=mn(t,"string");return ct(e)=="symbol"?e:e+""}function mn(t,e){if(ct(t)!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var s=r.call(t,e);if(ct(s)!="object")return s;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var te={name:"Button",extends:hn,inheritAttrs:!1,inject:{$pcFluid:{default:null}},methods:{getPTOptions:function(e){var r=e==="root"?this.ptmi:this.ptm;return r(e,{context:{disabled:this.disabled}})}},computed:{disabled:function(){return this.$attrs.disabled||this.$attrs.disabled===""||this.loading},defaultAriaLabel:function(){return this.label?this.label+(this.badge?" "+this.badge:""):this.$attrs.ariaLabel},hasIcon:function(){return this.icon||this.$slots.icon},attrs:function(){return w(this.asAttrs,this.a11yAttrs,this.getPTOptions("root"))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.disabled}:void 0},a11yAttrs:function(){return{"aria-label":this.defaultAriaLabel,"data-pc-name":"button","data-p-disabled":this.disabled,"data-p-severity":this.severity}},hasFluid:function(){return Gt(this.fluid)?!!this.$pcFluid:this.fluid},dataP:function(){return it(B(B(B(B(B(B(B(B(B(B({},this.size,this.size),"icon-only",this.hasIcon&&!this.label&&!this.badge),"loading",this.loading),"fluid",this.hasFluid),"rounded",this.rounded),"raised",this.raised),"outlined",this.outlined||this.variant==="outlined"),"text",this.text||this.variant==="text"),"link",this.link||this.variant==="link"),"vertical",(this.iconPos==="top"||this.iconPos==="bottom")&&this.label))},dataIconP:function(){return it(B(B({},this.iconPos,this.iconPos),this.size,this.size))},dataLabelP:function(){return it(B(B({},this.size,this.size),"icon-only",this.hasIcon&&!this.label&&!this.badge))}},components:{SpinnerIcon:Oe,Badge:Jt},directives:{ripple:Me}},gn=["data-p"],yn=["data-p"];function wn(t,e,r,s,p,u){var m=yt("SpinnerIcon"),y=yt("Badge"),k=Qt("ripple");return t.asChild?O(t.$slots,"default",{key:1,class:g(t.cx("root")),a11yAttrs:u.a11yAttrs}):V((i(),F(Xt(t.as),w({key:0,class:t.cx("root"),"data-p":u.dataP},u.attrs),{default:W(function(){return[O(t.$slots,"default",{},function(){return[t.loading?O(t.$slots,"loadingicon",w({key:0,class:[t.cx("loadingIcon"),t.cx("icon")]},t.ptm("loadingIcon")),function(){return[t.loadingIcon?(i(),d("span",w({key:0,class:[t.cx("loadingIcon"),t.cx("icon"),t.loadingIcon]},t.ptm("loadingIcon")),null,16)):(i(),F(m,w({key:1,class:[t.cx("loadingIcon"),t.cx("icon")],spin:""},t.ptm("loadingIcon")),null,16,["class"]))]}):O(t.$slots,"icon",w({key:1,class:[t.cx("icon")]},t.ptm("icon")),function(){return[t.icon?(i(),d("span",w({key:0,class:[t.cx("icon"),t.icon,t.iconClass],"data-p":u.dataIconP},t.ptm("icon")),null,16,gn)):h("",!0)]}),t.label?(i(),d("span",w({key:2,class:t.cx("label")},t.ptm("label"),{"data-p":u.dataLabelP}),f(t.label),17,yn)):h("",!0),t.badge?(i(),F(y,{key:3,value:t.badge,class:g(t.badgeClass),severity:t.badgeSeverity,unstyled:t.unstyled,pt:t.ptm("pcBadge")},null,8,["value","class","severity","unstyled","pt"])):h("",!0)]})]}),_:3},16,["class","data-p"])),[[k]])}te.render=wn;var kn=wt.extend({name:"focustrap-directive"}),xn=Fe.extend({style:kn});function bt(t){"@babel/helpers - typeof";return bt=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},bt(t)}function qt(t,e){var r=Object.keys(t);if(Object.getOwnPropertySymbols){var s=Object.getOwnPropertySymbols(t);e&&(s=s.filter(function(p){return Object.getOwnPropertyDescriptor(t,p).enumerable})),r.push.apply(r,s)}return r}function Ht(t){for(var e=1;e<arguments.length;e++){var r=arguments[e]!=null?arguments[e]:{};e%2?qt(Object(r),!0).forEach(function(s){_n(t,s,r[s])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(r)):qt(Object(r)).forEach(function(s){Object.defineProperty(t,s,Object.getOwnPropertyDescriptor(r,s))})}return t}function _n(t,e,r){return(e=Cn(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function Cn(t){var e=Sn(t,"string");return bt(e)=="symbol"?e:e+""}function Sn(t,e){if(bt(t)!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var s=r.call(t,e);if(bt(s)!="object")return s;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var $n=xn.extend("focustrap",{mounted:function(e,r){var s=r.value||{},p=s.disabled;p||(this.createHiddenFocusableElements(e,r),this.bind(e,r),this.autoElementFocus(e,r)),e.setAttribute("data-pd-focustrap",!0),this.$el=e},updated:function(e,r){var s=r.value||{},p=s.disabled;p&&this.unbind(e)},unmounted:function(e){this.unbind(e)},methods:{getComputedSelector:function(e){return':not(.p-hidden-focusable):not([data-p-hidden-focusable="true"])'.concat(e??"")},bind:function(e,r){var s=this,p=r.value||{},u=p.onFocusIn,m=p.onFocusOut;e.$_pfocustrap_mutationobserver=new MutationObserver(function(y){y.forEach(function(k){if(k.type==="childList"&&!e.contains(document.activeElement)){var x=function($){var _=zt($)?zt($,s.getComputedSelector(e.$_pfocustrap_focusableselector))?$:at(e,s.getComputedSelector(e.$_pfocustrap_focusableselector)):at($);return Zt(_)?_:$.nextSibling&&x($.nextSibling)};lt(x(k.nextSibling))}})}),e.$_pfocustrap_mutationobserver.disconnect(),e.$_pfocustrap_mutationobserver.observe(e,{childList:!0}),e.$_pfocustrap_focusinlistener=function(y){return u&&u(y)},e.$_pfocustrap_focusoutlistener=function(y){return m&&m(y)},e.addEventListener("focusin",e.$_pfocustrap_focusinlistener),e.addEventListener("focusout",e.$_pfocustrap_focusoutlistener)},unbind:function(e){e.$_pfocustrap_mutationobserver&&e.$_pfocustrap_mutationobserver.disconnect(),e.$_pfocustrap_focusinlistener&&e.removeEventListener("focusin",e.$_pfocustrap_focusinlistener)&&(e.$_pfocustrap_focusinlistener=null),e.$_pfocustrap_focusoutlistener&&e.removeEventListener("focusout",e.$_pfocustrap_focusoutlistener)&&(e.$_pfocustrap_focusoutlistener=null)},autoFocus:function(e){this.autoElementFocus(this.$el,{value:Ht(Ht({},e),{},{autoFocus:!0})})},autoElementFocus:function(e,r){var s=r.value||{},p=s.autoFocusSelector,u=p===void 0?"":p,m=s.firstFocusableSelector,y=m===void 0?"":m,k=s.autoFocus,x=k===void 0?!1:k,I=at(e,"[autofocus]".concat(this.getComputedSelector(u)));x&&!I&&(I=at(e,this.getComputedSelector(y))),lt(I)},onFirstHiddenElementFocus:function(e){var r,s=e.currentTarget,p=e.relatedTarget,u=p===s.$_pfocustrap_lasthiddenfocusableelement||!((r=this.$el)!==null&&r!==void 0&&r.contains(p))?at(s.parentElement,this.getComputedSelector(s.$_pfocustrap_focusableselector)):s.$_pfocustrap_lasthiddenfocusableelement;lt(u)},onLastHiddenElementFocus:function(e){var r,s=e.currentTarget,p=e.relatedTarget,u=p===s.$_pfocustrap_firsthiddenfocusableelement||!((r=this.$el)!==null&&r!==void 0&&r.contains(p))?we(s.parentElement,this.getComputedSelector(s.$_pfocustrap_focusableselector)):s.$_pfocustrap_firsthiddenfocusableelement;lt(u)},createHiddenFocusableElements:function(e,r){var s=this,p=r.value||{},u=p.tabIndex,m=u===void 0?0:u,y=p.firstFocusableSelector,k=y===void 0?"":y,x=p.lastFocusableSelector,I=x===void 0?"":x,$=function(et){return ke("span",{class:"p-hidden-accessible p-hidden-focusable",tabIndex:m,role:"presentation","aria-hidden":!0,"data-p-hidden-accessible":!0,"data-p-hidden-focusable":!0,onFocus:et?.bind(s)})},_=$(this.onFirstHiddenElementFocus),z=$(this.onLastHiddenElementFocus);_.$_pfocustrap_lasthiddenfocusableelement=z,_.$_pfocustrap_focusableselector=k,_.setAttribute("data-pc-section","firstfocusableelement"),z.$_pfocustrap_firsthiddenfocusableelement=_,z.$_pfocustrap_focusableselector=I,z.setAttribute("data-pc-section","lastfocusableelement"),e.prepend(_),e.append(z)}}});function Ln(){_e({variableName:Wt("scrollbar.width").name})}function Pn(){xe({variableName:Wt("scrollbar.width").name})}var En=`
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
`,Bn={mask:function(e){var r=e.position,s=e.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:r==="left"?"flex-start":r==="right"?"flex-end":"center",alignItems:r==="top"?"flex-start":r==="bottom"?"flex-end":"center",pointerEvents:s?"auto":"none"}},root:{pointerEvents:"auto"}},In={mask:function(e){var r=e.instance,s=e.props,p=["left","right","top","bottom"],u=p.find(function(m){return m===s.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter":s.modal,"p-drawer-open":r.containerVisible,"p-drawer-full":r.fullScreen},u?"p-drawer-".concat(u):""]},root:function(e){var r=e.instance;return["p-drawer p-component",{"p-drawer-full":r.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},Mn=wt.extend({name:"drawer",style:En,classes:In,inlineStyles:Bn}),On={name:"BaseDrawer",extends:Bt,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:Mn,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function pt(t){"@babel/helpers - typeof";return pt=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},pt(t)}function Pt(t,e,r){return(e=Fn(e))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}function Fn(t){var e=jn(t,"string");return pt(e)=="symbol"?e:e+""}function jn(t,e){if(pt(t)!="object"||!t)return t;var r=t[Symbol.toPrimitive];if(r!==void 0){var s=r.call(t,e);if(pt(s)!="object")return s;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var ee={name:"Drawer",extends:On,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(e){e&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&Lt.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&Lt.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&Ce(this.mask,"p-overlay-mask-leave"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&Lt.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(e){this.dismissable&&this.modal&&this.mask===e.target&&this.hide()},focus:function(){var e=function(p){return p&&p.querySelector("[autofocus]")},r=this.$slots.header&&e(this.headerContainer);r||(r=this.$slots.default&&e(this.container),r||(r=this.$slots.footer&&e(this.footerContainer),r||(r=this.closeButton))),r&&lt(r)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&Ln()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&Pn()},onKeydown:function(e){e.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(e){this.container=e},maskRef:function(e){this.mask=e},contentRef:function(e){this.content=e},headerContainerRef:function(e){this.headerContainer=e},footerContainerRef:function(e){this.footerContainer=e},closeButtonRef:function(e){this.closeButton=e?e.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var e=this;this.outsideClickListener||(this.outsideClickListener=function(r){e.isOutsideClicked(r)&&e.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(e){return this.container&&!this.container.contains(e.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return it(Pt(Pt(Pt({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:$n},components:{Button:te,Portal:Ae,TimesIcon:je}},An=["data-p"],zn=["role","aria-modal","data-p"];function Dn(t,e,r,s,p,u){var m=yt("Button"),y=yt("Portal"),k=Qt("focustrap");return i(),F(y,null,{default:W(function(){return[p.containerVisible?(i(),d("div",w({key:0,ref:u.maskRef,onMousedown:e[0]||(e[0]=function(){return u.onMaskClick&&u.onMaskClick.apply(u,arguments)}),class:t.cx("mask"),style:t.sx("mask",!0,{position:t.position,modal:t.modal}),"data-p":u.dataP},t.ptm("mask")),[gt(Et,w({name:"p-drawer",onEnter:u.onEnter,onAfterEnter:u.onAfterEnter,onBeforeLeave:u.onBeforeLeave,onLeave:u.onLeave,onAfterLeave:u.onAfterLeave,appear:""},t.ptm("transition")),{default:W(function(){return[t.visible?V((i(),d("div",w({key:0,ref:u.containerRef,class:t.cx("root"),style:t.sx("root"),role:t.modal?"dialog":"complementary","aria-modal":t.modal?!0:void 0,"data-p":u.dataP},t.ptmi("root")),[t.$slots.container?O(t.$slots,"container",{key:0,closeCallback:u.hide}):(i(),d(C,{key:1},[n("div",w({ref:u.headerContainerRef,class:t.cx("header")},t.ptm("header")),[O(t.$slots,"header",{class:g(t.cx("title"))},function(){return[t.header?(i(),d("div",w({key:0,class:t.cx("title")},t.ptm("title")),f(t.header),17)):h("",!0)]}),t.showCloseIcon?O(t.$slots,"closebutton",{key:0,closeCallback:u.hide},function(){return[gt(m,w({ref:u.closeButtonRef,type:"button",class:t.cx("pcCloseButton"),"aria-label":u.closeAriaLabel,unstyled:t.unstyled,onClick:u.hide},t.closeButtonProps,{pt:t.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:W(function(x){return[O(t.$slots,"closeicon",{},function(){return[(i(),F(Xt(t.closeIcon?"span":"TimesIcon"),w({class:[t.closeIcon,x.class]},t.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):h("",!0)],16),n("div",w({ref:u.contentRef,class:t.cx("content")},t.ptm("content")),[O(t.$slots,"default")],16),t.$slots.footer?(i(),d("div",w({key:0,ref:u.footerContainerRef,class:t.cx("footer")},t.ptm("footer")),[O(t.$slots,"footer")],16)):h("",!0)],64))],16,zn)),[[k]]):h("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,An)):h("",!0)]}),_:3})}ee.render=Dn;const Tn={class:"header"},Rn={class:"header-left"},Un=["src"],Vn={class:"fw-bold"},Kn={class:"nav user-menu"},Nn={key:0,class:"nav-item"},qn=["data-feather"],Hn={class:"nav-item"},Yn={class:"nav-item dropdown"},Gn={href:"javascript:void(0);",class:"dropdown-toggle nav-link","data-bs-toggle":"dropdown"},Zn={key:0,class:"badge rounded-pill bg-danger"},Qn={class:"dropdown-menu dropdown-menu-end notifications shadow-lg"},Xn={class:"topnav-dropdown-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom"},Wn={key:0,class:"text-primary"},Jn={class:"noti-content max-h-[350px] overflow-y-auto"},to=["onClick"],eo={class:"flex-grow-1 me-2"},no={class:"fw-semibold text-sm text-gray-800"},oo={class:"text-muted mt-1 d-block",style:{"font-size":"0.7rem"}},ro={key:0,class:"align-self-start"},ao={key:1,class:"p-3 text-center text-muted small"},so={class:"nav-item dropdown has-arrow main-drop cursor-pointer","data-bs-toggle":"modal","data-bs-target":"#verifyPasswordModal"},lo={class:"ms-2 d-none d-sm-block"},io={class:"fw-bold text-black"},uo={class:"super-admin text-black"},co={key:0,class:"sidebar",id:"sidebar","aria-label":"Primary"},bo={class:"sidebar-inner"},po={id:"sidebar-menu",class:"sidebar-menu px-2"},fo={class:"mb-3"},ho=["onClick"],vo=["data-feather"],mo={class:"truncate-when-mini"},go={key:0,class:"mt-3 mb-1 px-3 text-muted text-uppercase small section-title truncate-when-mini"},yo={key:0,class:"dropdown-parent"},wo=["onClick","title"],ko=["data-feather"],xo={class:"flex-grow-1 text-start truncate-when-mini"},_o=["data-feather"],Co=["onClick"],So=["data-feather"],$o=["onClick","title"],Lo=["data-feather"],Po={class:"truncate-when-mini"},Eo={key:2,class:"side-link"},Bo=["onClick","title"],Io=["data-feather"],Mo={class:"truncate-when-mini"},Oo={class:"flex flex-col h-full bg-white dark:bg-gray-900"},Fo={class:"flex items-center justify-between px-4 pt-4 pb-3 border-b dark:border-gray-700"},jo={class:"flex items-center gap-3"},Ao=["src"],zo={class:"font-bold text-lg text-black dark:text-white"},Do=["onClick"],To={class:"overflow-y-auto flex-1 px-2 py-3"},Ro={class:"list-none p-0 m-0"},Uo=["onClick"],Vo=["data-feather"],Ko={class:"font-medium"},No={key:0,class:"drawer-section-title mt-4 mb-2 px-4 text-dark text-xs font-semibold uppercase tracking-wider"},qo={key:0,class:"mb-1"},Ho=["onClick"],Yo={class:"flex items-center"},Go=["data-feather"],Zo={class:"font-medium"},Qo=["data-feather"],Xo={class:"list-none pl-8 mt-1 space-y-1"},Wo=["onClick"],Jo=["data-feather"],tr=["onClick"],er=["data-feather"],nr={class:"font-medium"},or={key:2,class:"mb-1"},rr=["onClick"],ar=["data-feather"],sr={class:"font-medium"},lr={key:4,class:"position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-dark bg-opacity-50",style:{"z-index":"9999"}},ir={class:"content bg-white dark:bg-gray-900 text-black dark:text-white"},dr={class:"modal fade",id:"verifyPasswordModal",tabindex:"-1","aria-hidden":"true"},ur={class:"modal-dialog modal-dialog-centered"},cr={class:"modal-content text-black rounded-4"},br={class:"modal-body"},pr={class:"mb-3"},fr={key:0,class:"invalid-feedback"},hr={class:"mb-3"},vr={key:0,class:"invalid-feedback"},mr={class:"modal-footer border-0"},gr=["disabled"],yr={key:0,class:"spinner-border spinner-border-sm me-2"},wr={class:"modal fade",id:"userProfileModal",tabindex:"-1","aria-hidden":"true"},kr={class:"modal-dialog modal-dialog-centered modal-lg"},xr={class:"modal-content text-black rounded-4"},_r={class:"modal-body"},Cr={class:"row g-3"},Sr={class:"col-md-6"},$r={key:0,class:"invalid-feedback"},Lr={class:"col-md-6"},Pr={key:0,class:"invalid-feedback"},Er={class:"col-md-6"},Br={key:0,class:"invalid-feedback"},Ir={class:"col-md-6"},Mr={key:0,class:"invalid-feedback"},Or={class:"modal fade",id:"quickOrderModal",tabindex:"-1","aria-hidden":"true",ref:"quickOrderModalRef"},Fr={class:"modal-dialog modal-lg modal-dialog-centered"},jr={class:"modal-content rounded-4"},Ar={class:"modal-header border-0"},zr={class:"modal-body"},Dr={class:"row g-3"},Tr={class:"row g-3"},Rr=["onClick"],Ur=["data-feather"],Vr={class:"text-start"},Kr={class:"fw-bold"},Nr={class:"text-muted"},Wr={__name:"Master",setup(t){const e=Se(),r=v(!1),s=v(!1),p=Y(()=>e.props.current_user),u=Y(()=>p.value?.roles?.includes("Cashier"));u.value&&tn();const m=a=>{console.log("Sidebar action triggered:",a),a==="systemRestore"?(k.value=!0,console.log("showConfirmRestore set to:",r.value)):a==="databaseBackup"&&(x.value=!0)},y=v(!1),k=v(!1),x=v(!1),I=v(!1),$=v(!1),_=v(null),z=Y(()=>(nt.value.roles||[]).includes("Cashier")),It=()=>{document.fullscreenElement?document.exitFullscreen&&(document.exitFullscreen(),I.value=!1):(document.documentElement.requestFullscreen(),I.value=!0)},et=()=>{_.value=null,$.value=!0},ne=()=>{if(!_.value){S.error("Please select an order type");return}localStorage.setItem("quickOrderType",_.value),$.value=!1,K.visit(route("pos.order",{type:_.value}))},oe=Y(()=>e.props.onboarding?.service_options?.order_types||[]),re=a=>({eat_in:"home",dine_in:"home",take_away:"shopping-bag",takeaway:"shopping-bag",delivery:"truck"})[a]||"package",ae=a=>({eat_in:"Eat In",dine_in:"Dine In",take_away:"Take Away",takeaway:"Take Away",delivery:"Delivery"})[a]||a.replace(/_/g," ").toUpperCase(),se=a=>({eat_in:"Customer dines here",dine_in:"Customer dines here",takeaway:"Customer take order",delivery:"Deliver to customer"})[a]||"",Mt=()=>{I.value=!!document.fullscreenElement,z.value&&!document.fullscreenElement&&setTimeout(()=>{document.documentElement.requestFullscreen().catch(a=>{console.warn("Could not re-enter fullscreen:",a)})},100)},Ot=a=>{if(a.key==="Escape"&&z.value&&document.fullscreenElement)return a.preventDefault(),a.stopPropagation(),!1},le=async()=>{if(z.value)try{await Tt(),setTimeout(async()=>{document.fullscreenElement||(await document.documentElement.requestFullscreen(),I.value=!0)},500)}catch(a){console.error("Failed to enter fullscreen:",a),S.warning("Please allow fullscreen mode for better experience")}};Dt(s,a=>{a&&Tt(()=>{window.feather?.replace()})}),Dt($,a=>{a?new bootstrap.Modal(document.getElementById("quickOrderModal")).show():bootstrap.Modal.getInstance(document.getElementById("quickOrderModal"))?.hide()}),document.addEventListener("fullscreenchange",()=>{I.value=!!document.fullscreenElement});const ft=v(!1),ie=async()=>{y.value=!1,ft.value=!0,await new Promise(a=>setTimeout(a,800));try{await K.post(route("logout"))}finally{ft.value=!1}},de=async()=>{try{(await axios.post(route("system.restore"))).data.success&&(S.success("System restored successfully!"),r.value=!1,window.location.href=route("front-page"))}catch(a){console.error("System restore error:",a);let o="Failed to restore system. Please try again.";a.response?a.response.data?.message?o=a.response.data.message:a.response.data?.error?o=a.response.data.error:o=`Error ${a.response.status}: ${a.response.statusText}`:a.message&&(o=a.message),S.error(o)}},ue=async()=>{try{const a=await axios.post(route("database.backup"),{},{responseType:"blob"}),o=window.URL.createObjectURL(new Blob([a.data])),l=document.createElement("a");l.href=o;const c=a.headers["content-disposition"];let b="database_backup_"+new Date().toISOString().slice(0,10)+".sql";if(c){const E=c.match(/filename="?(.+)"?/i);E&&(b=E[1])}l.setAttribute("download",b),document.body.appendChild(l),l.click(),l.remove(),window.URL.revokeObjectURL(o),S.success("Database backup downloaded successfully!"),x.value=!1}catch(a){console.error("Database backup error:",a);let o="Failed to create database backup. Please try again.";if(a.response?.data)if(a.response.data instanceof Blob)try{const l=await a.response.data.text();o=JSON.parse(l).message||o}catch{o="An error occurred while creating the backup."}else a.response.data.message&&(o=a.response.data.message);else a.message&&(o=a.message);S.error(o),x.value=!1}},Ft=Y(()=>e.props.current_user?.permissions??[]),ce=Y(()=>e.props.current_user?.roles??[]),M=a=>!a||ce.value.includes("Super Admin")?!0:(Array.isArray(Ft.value)?Ft.value:[]).includes(a),L=v({}),nt=Y(()=>e.props.current_user??{}),ht=Y(()=>e.props.business_info??{}),vt=Ee({selector:"html",attribute:"class",valueDark:"dark",valueLight:"light"}),be=Be(vt);st(()=>{window.feather?.replace()});const U=v({username:"",password:""}),P=v({}),ot=v(!1),kt=()=>{U.value={username:"",password:""},P.value={}},xt=async()=>{if(P.value={},!U.value.username){P.value.username="Username is required",S.error("Username is required");return}if(!U.value.password){P.value.password="Password is required",S.error("Password is required");return}ot.value=!0;try{(await axios.post("/api/profile/verify-credentials",{username:U.value.username,password:U.value.password})).data.success&&(bootstrap.Modal.getInstance(document.getElementById("verifyPasswordModal")).hide(),kt(),S.success("Identity verified successfully"),setTimeout(()=>{new bootstrap.Modal(document.getElementById("userProfileModal")).show()},300))}catch(a){if(console.error("Verification error:",a),a.response?.status===422){P.value=a.response.data.errors||{};const o=Object.values(P.value)[0];Array.isArray(o)?S.error(o[0]):S.error(o||"Validation error occurred")}else if(a.response?.status===401){const o="Invalid username or password";P.value.general=o,S.error(o)}else{const o=a.response?.data?.message||"An error occurred. Please try again.";P.value.general=o,S.error(o)}}finally{ot.value=!1}},_t=v([{label:"Dashboard",icon:"grid",route:"dashboard"},{section:"POS Management",children:[{label:"Inventory",icon:"package",children:[{label:"Items",icon:"box",route:"inventory.index"},{label:"Categories",icon:"layers",route:"inventory.categories.index"},{label:"Logs Moments",icon:"archive",route:"stock.logs.index"},{label:"Purchase Order",icon:"shopping-cart",route:"purchase.orders.index"},{label:"Reference Management",icon:"database",route:"reference.index"}]},{label:"Menu",icon:"book-open",children:[{label:"Categories",icon:"layers",route:"menu-categories.index"},{label:"Menus",icon:"box",route:"menu.index"},{label:"Addon Groups",icon:"gift",route:"addon-groups.index"},{label:"Addons",icon:"plus-circle",route:"addons.index"},{label:"Promo",icon:"tag",route:"promos.index"},{label:"Discount",icon:"percent",route:"discounts.index"},{label:"Meals",icon:"coffee",route:"meals.index"}]},{label:"Sale",icon:"shopping-bag",route:"pos.order"},{label:"Orders",icon:"list",route:"orders.index"},{label:"KOT",icon:"clipboard",route:"kots.index"},{label:"Payment",icon:"credit-card",route:"payment.index"},{label:"Analytics",icon:"bar-chart-2",route:"analytics.index"},{label:"Shift Management",icon:"users",route:"shift.index"},{label:"Settings",icon:"settings",route:"settings.index"},{label:"Backup",icon:"database",action:"databaseBackup"}]}]),Z=a=>{try{return route().current(a)}catch{return!1}},D=v(new Set),jt=a=>{D.value.has(a)?D.value.delete(a):D.value.add(a)},N=(a=[])=>a.some(o=>o.children?N(o.children):Z(o.route)),pe=()=>{const a=(o=[])=>{o.forEach(l=>{l.children?.length&&(N(l.children)&&D.value.add(l.label),a(l.children))})};_t.value.forEach(o=>o.children&&a(o.children))},Ct=v("desktop");v(!0);const T=v(!1),q=v(!1),Q=v(!0),H=v(!0),rt=v(!1),St=()=>{const a=window.innerWidth;a<768?(Ct.value="mobile",T.value=!0,q.value=!1,Q.value=!1,rt.value=!1,H.value=!1):a<992?(Ct.value="tablet",T.value=!1,q.value=!0,Q.value=!1,H.value=!1,rt.value=!1):(Ct.value="desktop",T.value=!1,q.value=!1,Q.value=!0,H.value=!u.value,rt.value=!1)},At=()=>{T.value||q.value?s.value=!s.value:H.value=!H.value},$t=a=>{a&&(K.visit(route(a)),(T.value||q.value)&&(s.value=!1))},fe=a=>{m(a),(T.value||q.value)&&(s.value=!1)};st(()=>{document.addEventListener("fullscreenchange",Mt),document.addEventListener("keydown",Ot,!0),le(),Q.value&&(H.value=!u.value),St(),window.addEventListener("resize",St,{passive:!0}),window.feather?.replace(),pe()}),$e(()=>{document.removeEventListener("fullscreenchange",Mt),document.removeEventListener("keydown",Ot,!0),window.removeEventListener("resize",St)}),Le(()=>window.feather?.replace());const j=v({username:nt.value.name??"",password:"",pin:"",role:nt.value.roles[0]??""}),he=async()=>{try{(await axios.post("/api/profile/update",j.value)).data.success?(S.success("Profile updated successfully"),bootstrap.Modal.getInstance(document.getElementById("userProfileModal")).hide()):alert("Something went wrong!")}catch(a){a?.response?.status===422&&a.response.data?.errors&&(L.value=a.response.data.errors,S.error("Please fill in all required fields correctly."))}};st(()=>{document.getElementById("userProfileModal").addEventListener("hidden.bs.modal",()=>{L.value={},j.value.password="",j.value.pin=""})});const J=v([]),X=v(0),ve=async()=>{const a=await axios.get("/api/notifications");J.value=a.data,X.value=J.value.filter(o=>!o.is_read).length},me=async a=>{try{await axios.post(`/api/notifications/mark-as-read/${a}`);const o=J.value.find(l=>l.id===a);o&&!o.is_read&&(o.is_read=!0,X.value--)}catch(o){console.error("Error marking as read:",o)}},ge=async()=>{try{await axios.post("/api/notifications/mark-all-as-read"),J.value.forEach(a=>a.is_read=!0),X.value=0}catch(a){console.error("Error marking all as read:",a)}};return st(ve),(a,o)=>(i(),d(C,null,[n("div",{class:g(["layout-root",{"state-desktop":Q.value,"state-tablet":q.value,"state-mobile":T.value,"sidebar-open":T.value?rt.value:H.value,"sidebar-collapsed":(Q.value||q.value)&&!H.value,"sidebar-overlay":T.value}])},[n("header",Tn,[n("div",Rn,[n("img",{src:ht.value.image_url,alt:"logo",width:"50",height:"50px",class:"rounded-full border shadow"},null,8,Un),n("h5",Vn,f(ht.value.business_name),1),n("button",{class:"icon-btn",onClick:At,"aria-label":"Toggle sidebar"},[...o[13]||(o[13]=[n("i",{"data-feather":"menu"},null,-1)])])]),o[21]||(o[21]=n("div",{class:"header-center"},null,-1)),n("ul",Kn,[n("button",{class:"btn btn-primary rounded-pill py-2 px-3",onClick:et}," Quick Order "),n("button",{class:"btn btn-danger rounded-pill py-2 px-3 d-flex align-items-center",onClick:o[0]||(o[0]=l=>y.value=!0)},[...o[14]||(o[14]=[mt(" Logout ",-1),n("svg",{xmlns:"http://www.w3.org/2000/svg",width:"18",height:"18",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round",class:"feather feather-log-out ms-2"},[n("path",{d:"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"}),n("polyline",{points:"16 17 21 12 16 7"}),n("line",{x1:"21",y1:"12",x2:"9",y2:"12"})],-1)])]),z.value?h("",!0):(i(),d("li",Nn,[n("button",{class:"icon-btn",onClick:It,title:"Toggle Fullscreen"},[n("i",{"data-feather":I.value?"minimize":"maximize"},null,8,qn)])])),n("li",Hn,[n("button",{class:"icon-btn",onClick:o[1]||(o[1]=l=>A(be)())},[A(vt)?(i(),F(A(ze),{key:0,size:20})):(i(),F(A(De),{key:1,size:20}))])]),n("li",Yn,[n("a",Gn,[o[15]||(o[15]=n("img",{src:"/assets/img/icons/notification-bing.svg",alt:"noti"},null,-1)),X.value>0?(i(),d("span",Zn,f(X.value),1)):h("",!0)]),n("div",Qn,[n("div",Xn,[o[17]||(o[17]=n("span",{class:"notification-title fw-bold"},"Notifications",-1)),n("a",{href:"javascript:void(0)",class:"text-primary fw-semibold",style:{"font-size":"0.9rem"},onClick:Ut(ge,["stop"])},[o[16]||(o[16]=mt(" Mark all as read ",-1)),X.value>0?(i(),d("span",Wn,"("+f(X.value)+")",1)):h("",!0)])]),n("div",Jn,[J.value.length?(i(!0),d(C,{key:0},G(J.value,l=>(i(),d("div",{key:l.id,class:g(["d-flex align-items-start justify-content-between p-3 border-bottom cursor-pointer transition-all",l.is_read?"bg-gray-50 m-2 mb-2":"bg-white shadow-sm m-2"]),onClick:Ut(c=>me(l.id),["stop"])},[n("div",eo,[n("div",no,f(l.message),1),n("span",{class:g(["inline-flex notifi-span items-center rounded-full px-2 py-0.5 text-xs font-medium mt-1",{"text-red-700 bg-red-300":l.status?.toLowerCase()==="out_of_stock","text-yellow-700 bg-yellow-100":l.status?.toLowerCase()==="low_stock","text-orange-700 bg-orange-200":l.status?.toLowerCase()==="expired","text-blue-700 bg-blue-100":l.status?.toLowerCase()==="near_expiry"}])},f(l.status.replace(/_/g," ").toUpperCase()),3),n("small",oo,f(new Date(l.created_at).toLocaleString("en-US",{dateStyle:"medium",timeStyle:"short"})),1)]),l.is_read?h("",!0):(i(),d("div",ro,[...o[18]||(o[18]=[n("span",{class:"inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full new-badge bg-green-100 text-green-600"}," NEW ",-1)])]))],10,to))),128)):(i(),d("div",ao,"No notifications"))])])]),n("li",so,[o[20]||(o[20]=n("span",{class:"user-img"},[n("i",{class:"bi bi-person-circle"})],-1)),n("div",lo,[n("b",io,f(nt.value.name),1),o[19]||(o[19]=n("br",null,null,-1)),n("small",uo,f(nt.value.roles[0]),1)])])])]),Q.value?(i(),d("aside",co,[n("div",bo,[n("div",po,[n("ul",fo,[(i(!0),d(C,null,G(_t.value,l=>(i(),d(C,{key:l.label||l.section},[!l.section&&M(l.route)?(i(),d("li",{key:0,class:g({active:Z(l.route)})},[n("button",{class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0 text-start",onClick:c=>A(K).visit(a.route(l.route))},[n("i",{"data-feather":l.icon,class:"me-2 icons"},null,8,vo),n("span",mo,f(l.label),1)],8,ho)],2)):(i(),d(C,{key:1},[l.section&&l.children.some(c=>M(c.route))?(i(),d("li",go,f(l.section),1)):h("",!0),(i(!0),d(C,null,G(l.children,c=>(i(),d(C,{key:c.label},[c.children&&c.children.length&&c.children.some(b=>M(b.route))?(i(),d("li",yo,[n("button",{class:g(["d-flex align-items-center side-link px-3 py-2 w-100 border-0",{active:D.value.has(c.label)||N(c.children)}]),onClick:b=>jt(c.label),type:"button",title:c.label},[n("i",{"data-feather":c.icon,class:"me-2"},null,8,ko),n("span",xo,f(c.label),1),n("i",{class:"chevron-icon","data-feather":D.value.has(c.label)||N(c.children)?"chevron-up":"chevron-down"},null,8,_o)],10,wo),n("ul",{class:g(["list-unstyled my-1 submenu-dropdown",{expanded:D.value.has(c.label)||N(c.children)}])},[M(a.child?.route)?(i(!0),d(C,{key:0},G(c.children,b=>(i(),d("li",{key:b.label,class:g({active:Z(b.route)})},[n("button",{class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0 text-start",onClick:E=>A(K).visit(a.route(b.route))},[n("i",{"data-feather":b.icon,class:"me-2"},null,8,So),n("span",null,f(b.label),1)],8,Co)],2))),128)):h("",!0)],2)])):c.route&&M(c.route)?(i(),d("li",{key:1,class:g([{active:c.route?Z(c.route):!1},"side-link"])},[n("button",{class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0 text-start",onClick:b=>A(K).visit(a.route(c.route)),title:c.label},[n("i",{"data-feather":c.icon,class:"me-2"},null,8,Lo),n("span",Po,f(c.label),1)],8,$o)],2)):c.action&&M(c.action)?(i(),d("li",Eo,[n("button",{onClick:b=>m(c.action),class:"d-flex align-items-center side-link px-3 py-2 w-100 border-0",title:c.label},[n("i",{"data-feather":c.icon,class:"me-2"},null,8,Io),n("span",Mo,f(c.label),1)],8,Bo)])):h("",!0)],64))),128))],64))],64))),128))])])])])):h("",!0),gt(A(ee),{visible:s.value,"onUpdate:visible":o[2]||(o[2]=l=>s.value=l),position:"left",class:"sidebar-drawer"},{container:W(({closeCallback:l})=>[n("div",Oo,[n("div",Fo,[n("div",jo,[n("img",{src:ht.value.image_url,alt:"logo",width:"40",height:"40",class:"rounded-full border shadow"},null,8,Ao),n("span",zo,f(ht.value.business_name),1)]),n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110",onClick:l,"data-bs-dismiss":"modal","aria-label":"Close",title:"Close"},[...o[22]||(o[22]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])],8,Do)]),n("div",To,[n("ul",Ro,[(i(!0),d(C,null,G(_t.value,c=>(i(),d(C,{key:c.label||c.section},[!c.section&&M(c.route)?(i(),d("li",{key:0,class:g([{active:Z(c.route)},"mb-1"])},[n("button",{class:"drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors",onClick:b=>$t(c.route)},[n("i",{"data-feather":c.icon,class:"w-5 h-5 mr-3"},null,8,Vo),n("span",Ko,f(c.label),1)],8,Uo)],2)):(i(),d(C,{key:1},[c.section&&c.children.some(b=>M(b.route))?(i(),d("li",No,f(c.section),1)):h("",!0),(i(!0),d(C,null,G(c.children,b=>(i(),d(C,{key:b.label},[b.children&&b.children.length&&b.children.some(E=>M(E.route))?(i(),d("li",qo,[n("button",{class:g(["drawer-link w-full flex items-center justify-between px-4 py-3 rounded-lg text-left transition-colors",{active:D.value.has(b.label)||N(b.children)}]),onClick:E=>jt(b.label),type:"button"},[n("div",Yo,[n("i",{"data-feather":b.icon,class:"w-5 h-5 mr-3"},null,8,Go),n("span",Zo,f(b.label),1)]),n("i",{class:"chevron-icon","data-feather":D.value.has(b.label)||N(b.children)?"chevron-up":"chevron-down"},null,8,Qo)],10,Ho),V(n("ul",Xo,[M(a.child?.route)?(i(!0),d(C,{key:0},G(b.children,E=>(i(),d("li",{key:E.label,class:g({active:Z(E.route)})},[n("button",{class:"drawer-link w-full flex items-center px-4 py-2 rounded-lg text-left text-sm transition-colors",onClick:qr=>$t(E.route)},[n("i",{"data-feather":E.icon,class:"w-4 h-4 mr-3"},null,8,Jo),n("span",null,f(E.label),1)],8,Wo)],2))),128)):h("",!0)],512),[[Pe,D.value.has(b.label)||N(b.children)]])])):b.route&&M(b.route)?(i(),d("li",{key:1,class:g([{active:Z(b.route)},"mb-1"])},[n("button",{class:"drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors",onClick:E=>$t(b.route)},[n("i",{"data-feather":b.icon,class:"w-5 h-5 mr-3"},null,8,er),n("span",nr,f(b.label),1)],8,tr)],2)):b.action&&M(b.action)?(i(),d("li",or,[n("button",{class:"drawer-link w-full flex items-center px-4 py-3 rounded-lg text-left transition-colors",onClick:E=>fe(b.action)},[n("i",{"data-feather":b.icon,class:"w-5 h-5 mr-3"},null,8,ar),n("span",sr,f(b.label),1)],8,rr)])):h("",!0)],64))),128))],64))],64))),128))])])])]),_:1},8,["visible"]),k.value?(i(),F(Kt,{key:1,show:k.value,title:"Confirm System Restore",message:"Are you sure you want to restore the system? This will reset all data to default settings. This action cannot be undone.",confirmLabel:"Yes, Restore",onConfirm:de,onCancel:o[3]||(o[3]=l=>k.value=!1)},null,8,["show"])):h("",!0),x.value?(i(),F(Kt,{key:2,show:x.value,title:"Confirm Database Backup",message:"Are you sure you want to create a database backup? The backup file will be downloaded to your computer.",confirmLabel:"Yes, Backup",onConfirm:ue,onCancel:o[4]||(o[4]=l=>x.value=!1)},null,8,["show"])):h("",!0),y.value?(i(),F(qe,{key:3,show:y.value,loading:ft.value,onConfirm:ie,onCancel:o[5]||(o[5]=l=>y.value=!1)},null,8,["show","loading"])):h("",!0),ft.value?(i(),d("div",lr,[...o[23]||(o[23]=[n("div",{class:"spinner-border text-light",role:"status",style:{width:"3rem",height:"3rem"}},null,-1)])])):h("",!0),T.value&&rt.value?(i(),d("div",{key:5,class:"overlay-backdrop","aria-hidden":"true",onClick:At})):h("",!0),n("main",ir,[O(a.$slots,"default")])],2),o[37]||(o[37]=Rt('<footer class="footer bg-white dark:bg-gray-800 border-top sticky bottom-0"><div class="container-fluid"><div class="row align-items-center py-3"><div class="col-md-4"></div><div class="col-md-4 text-center"><span class="text-muted"> Powered by <strong class="text-primary">10XGLOBAL</strong></span></div><div class="col-md-4 text-end"><a href="#" class="text-decoration-none hover:text-primary-dark"> Need Help? <strong class="text-primary">Contact Us</strong></a></div></div></div></footer>',1)),n("div",dr,[n("div",ur,[n("div",cr,[n("div",{class:"modal-header border-0"},[o[25]||(o[25]=n("h5",{class:"modal-title fw-bold"},"Verify Your Identity",-1)),n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110","data-bs-dismiss":"modal","aria-label":"Close",title:"Close",onClick:kt},[...o[24]||(o[24]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])])]),n("div",br,[o[28]||(o[28]=n("p",{class:"text-muted mb-3"},"Please verify your credentials to access profile settings",-1)),n("div",pr,[o[26]||(o[26]=n("label",{class:"form-label"},"Username",-1)),V(n("input",{type:"text",class:g(["form-control",{"is-invalid":P.value.username}]),"onUpdate:modelValue":o[6]||(o[6]=l=>U.value.username=l),onKeyup:Vt(xt,["enter"]),placeholder:"Enter your username"},null,34),[[tt,U.value.username]]),P.value.username?(i(),d("div",fr,f(P.value.username),1)):h("",!0)]),n("div",hr,[o[27]||(o[27]=n("label",{class:"form-label"},"Password",-1)),V(n("input",{type:"password",class:g(["form-control",{"is-invalid":P.value.password}]),"onUpdate:modelValue":o[7]||(o[7]=l=>U.value.password=l),onKeyup:Vt(xt,["enter"]),placeholder:"Enter your password"},null,34),[[tt,U.value.password]]),P.value.password?(i(),d("div",vr,f(P.value.password),1)):h("",!0)])]),n("div",mr,[n("button",{type:"button",class:"btn btn-secondary rounded-pill px-2 py-2","data-bs-dismiss":"modal",onClick:kt}," Cancel "),n("button",{type:"button",class:"btn btn-primary rounded-pill px-2 py-2",onClick:xt,disabled:ot.value},[ot.value?(i(),d("span",yr)):h("",!0),mt(" "+f(ot.value?"Verifying...":"Verify"),1)],8,gr)])])])]),n("div",wr,[n("div",kr,[n("div",xr,[o[33]||(o[33]=Rt('<div class="modal-header border-0"><h5 class="modal-title fw-bold">User Profile</h5><button class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110" data-bs-dismiss="modal" aria-label="Close" title="Close"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>',1)),n("div",_r,[n("div",Cr,[n("div",Sr,[o[29]||(o[29]=n("label",{class:"form-label"},"UserName",-1)),V(n("input",{type:"text",class:g(["form-control",{"is-invalid":L.value.username}]),"onUpdate:modelValue":o[8]||(o[8]=l=>j.value.username=l)},null,2),[[tt,j.value.username]]),L.value.username?(i(),d("div",$r,f(L.value.username[0]),1)):h("",!0)]),n("div",Lr,[o[30]||(o[30]=n("label",{class:"form-label"},"Password",-1)),V(n("input",{type:"password",class:g(["form-control",{"is-invalid":L.value.password}]),placeholder:"Enter new password (leave blank to keep current)","onUpdate:modelValue":o[9]||(o[9]=l=>j.value.password=l)},null,2),[[tt,j.value.password]]),L.value.password?(i(),d("div",Pr,f(L.value.password[0]),1)):h("",!0)]),n("div",Er,[o[31]||(o[31]=n("label",{class:"form-label"},"Pin",-1)),V(n("input",{type:"text",class:g(["form-control",{"is-invalid":L.value.pin}]),placeholder:"Enter new PIN (leave blank to keep current)","onUpdate:modelValue":o[10]||(o[10]=l=>j.value.pin=l)},null,2),[[tt,j.value.pin]]),L.value.pin?(i(),d("div",Br,f(L.value.pin[0]),1)):h("",!0)]),n("div",Ir,[o[32]||(o[32]=n("label",{class:"form-label"},"Role",-1)),V(n("input",{type:"text",class:g(["form-control",{"is-invalid":L.value.role}]),"onUpdate:modelValue":o[11]||(o[11]=l=>j.value.role=l),readonly:""},null,2),[[tt,j.value.role]]),L.value.role?(i(),d("div",Mr,f(L.value.role[0]),1)):h("",!0)])])]),n("div",{class:"modal-footer border-0"},[n("button",{type:"button",class:"btn btn-primary py-2 px-2 w-30 rounded-pill",onClick:he}," Update ")])])])]),n("div",Or,[n("div",Fr,[n("div",jr,[n("div",Ar,[o[35]||(o[35]=n("h5",{class:"modal-title fw-bold"},"Select Order Type",-1)),n("button",{class:"absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110","data-bs-dismiss":"modal","aria-label":"Close",title:"Close",onClick:o[12]||(o[12]=l=>$.value=!1)},[...o[34]||(o[34]=[n("svg",{xmlns:"http://www.w3.org/2000/svg",class:"h-6 w-6 text-red-500",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor","stroke-width":"2"},[n("path",{"stroke-linecap":"round","stroke-linejoin":"round",d:"M6 18L18 6M6 6l12 12"})],-1)])])]),n("div",zr,[o[36]||(o[36]=n("p",{class:"text-muted mb-1"},"Please select how the customer will receive their order:",-1)),n("div",Dr,[n("div",Tr,[(i(!0),d(C,null,G(oe.value,l=>(i(),d("div",{key:l,class:"col-md-4 mb-3"},[n("button",{class:g(["card-option w-100 p-4 border rounded-3 cursor-pointer transition d-flex align-items-center justify-content-center gap-3",{"border-primary bg-light":!A(vt)&&_.value===l,"border-primary dark-selected":A(vt)&&_.value===l}]),onClick:c=>{_.value=l,ne()}},[n("i",{"data-feather":re(l),class:"flex-shrink-0",style:{width:"28px",height:"28px"}},null,8,Ur),n("div",Vr,[n("div",Kr,f(ae(l)),1),n("small",Nr,f(se(l)),1)])],10,Rr)]))),128))])])])])])],512)],64))}};export{Wr as _};
