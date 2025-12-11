import{a_ as ot,f as S,b as z,e as J,a3 as w,a0 as D,H as L,a$ as lt,ag as at,ak as Y,aV as R,aU as j,E as ut,m as Z,F as U,g as tt,d as dt}from"./app-I1xAEktG.js";import{a as it,s as Q,f as ct}from"./index-ur9SuMgD.js";import{a as ht}from"./index-f94f_6r1.js";import{s as ft}from"./index-CmJfvnBt.js";function M(e){"@babel/helpers - typeof";return M=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},M(e)}function pt(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function mt(e,t){for(var n=0;n<t.length;n++){var i=t[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(e,gt(i.key),i)}}function vt(e,t,n){return t&&mt(e.prototype,t),Object.defineProperty(e,"prototype",{writable:!1}),e}function gt(e){var t=yt(e,"string");return M(t)=="symbol"?t:t+""}function yt(e,t){if(M(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var i=n.call(e,t);if(M(i)!="object")return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}var he=(function(){function e(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:function(){};pt(this,e),this.element=t,this.listener=n}return vt(e,[{key:"bindScrollListener",value:function(){this.scrollableParents=ot(this.element);for(var n=0;n<this.scrollableParents.length;n++)this.scrollableParents[n].addEventListener("scroll",this.listener)}},{key:"unbindScrollListener",value:function(){if(this.scrollableParents)for(var n=0;n<this.scrollableParents.length;n++)this.scrollableParents[n].removeEventListener("scroll",this.listener)}},{key:"destroy",value:function(){this.unbindScrollListener(),this.element=null,this.listener=null,this.scrollableParents=null}}])})(),bt={name:"ChevronDownIcon",extends:it};function St(e){return Ct(e)||It(e)||wt(e)||zt()}function zt(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function wt(e,t){if(e){if(typeof e=="string")return q(e,t);var n={}.toString.call(e).slice(8,-1);return n==="Object"&&e.constructor&&(n=e.constructor.name),n==="Map"||n==="Set"?Array.from(e):n==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?q(e,t):void 0}}function It(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function Ct(e){if(Array.isArray(e))return q(e)}function q(e,t){(t==null||t>e.length)&&(t=e.length);for(var n=0,i=Array(t);n<t;n++)i[n]=e[n];return i}function xt(e,t,n,i,s,r){return z(),S("svg",w({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},e.pti()),St(t[0]||(t[0]=[J("path",{d:"M7.01744 10.398C6.91269 10.3985 6.8089 10.378 6.71215 10.3379C6.61541 10.2977 6.52766 10.2386 6.45405 10.1641L1.13907 4.84913C1.03306 4.69404 0.985221 4.5065 1.00399 4.31958C1.02276 4.13266 1.10693 3.95838 1.24166 3.82747C1.37639 3.69655 1.55301 3.61742 1.74039 3.60402C1.92777 3.59062 2.11386 3.64382 2.26584 3.75424L7.01744 8.47394L11.769 3.75424C11.9189 3.65709 12.097 3.61306 12.2748 3.62921C12.4527 3.64535 12.6199 3.72073 12.7498 3.84328C12.8797 3.96582 12.9647 4.12842 12.9912 4.30502C13.0177 4.48162 12.9841 4.662 12.8958 4.81724L7.58083 10.1322C7.50996 10.2125 7.42344 10.2775 7.32656 10.3232C7.22968 10.3689 7.12449 10.3944 7.01744 10.398Z",fill:"currentColor"},null,-1)])),16)}bt.render=xt;var Pt={name:"SearchIcon",extends:it};function $t(e){return Bt(e)||Tt(e)||Ot(e)||Lt()}function Lt(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function Ot(e,t){if(e){if(typeof e=="string")return G(e,t);var n={}.toString.call(e).slice(8,-1);return n==="Object"&&e.constructor&&(n=e.constructor.name),n==="Map"||n==="Set"?Array.from(e):n==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?G(e,t):void 0}}function Tt(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function Bt(e){if(Array.isArray(e))return G(e)}function G(e,t){(t==null||t>e.length)&&(t=e.length);for(var n=0,i=Array(t);n<t;n++)i[n]=e[n];return i}function At(e,t,n,i,s,r){return z(),S("svg",w({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},e.pti()),$t(t[0]||(t[0]=[J("path",{"fill-rule":"evenodd","clip-rule":"evenodd",d:"M2.67602 11.0265C3.6661 11.688 4.83011 12.0411 6.02086 12.0411C6.81149 12.0411 7.59438 11.8854 8.32483 11.5828C8.87005 11.357 9.37808 11.0526 9.83317 10.6803L12.9769 13.8241C13.0323 13.8801 13.0983 13.9245 13.171 13.9548C13.2438 13.985 13.3219 14.0003 13.4007 14C13.4795 14.0003 13.5575 13.985 13.6303 13.9548C13.7031 13.9245 13.7691 13.8801 13.8244 13.8241C13.9367 13.7116 13.9998 13.5592 13.9998 13.4003C13.9998 13.2414 13.9367 13.089 13.8244 12.9765L10.6807 9.8328C11.053 9.37773 11.3573 8.86972 11.5831 8.32452C11.8857 7.59408 12.0414 6.81119 12.0414 6.02056C12.0414 4.8298 11.6883 3.66579 11.0268 2.67572C10.3652 1.68564 9.42494 0.913972 8.32483 0.45829C7.22472 0.00260857 6.01418 -0.116618 4.84631 0.115686C3.67844 0.34799 2.60568 0.921393 1.76369 1.76338C0.921698 2.60537 0.348296 3.67813 0.115991 4.84601C-0.116313 6.01388 0.00291375 7.22441 0.458595 8.32452C0.914277 9.42464 1.68595 10.3649 2.67602 11.0265ZM3.35565 2.0158C4.14456 1.48867 5.07206 1.20731 6.02086 1.20731C7.29317 1.20731 8.51338 1.71274 9.41304 2.6124C10.3127 3.51206 10.8181 4.73226 10.8181 6.00457C10.8181 6.95337 10.5368 7.88088 10.0096 8.66978C9.48251 9.45868 8.73328 10.0736 7.85669 10.4367C6.98011 10.7997 6.01554 10.8947 5.08496 10.7096C4.15439 10.5245 3.2996 10.0676 2.62869 9.39674C1.95778 8.72583 1.50089 7.87104 1.31579 6.94046C1.13068 6.00989 1.22568 5.04532 1.58878 4.16874C1.95187 3.29215 2.56675 2.54292 3.35565 2.0158Z",fill:"currentColor"},null,-1)])),16)}Pt.render=At;var kt=`
    .p-iconfield {
        position: relative;
        display: block;
    }

    .p-inputicon {
        position: absolute;
        top: 50%;
        margin-top: calc(-1 * (dt('icon.size') / 2));
        color: dt('iconfield.icon.color');
        line-height: 1;
        z-index: 1;
    }

    .p-iconfield .p-inputicon:first-child {
        inset-inline-start: dt('form.field.padding.x');
    }

    .p-iconfield .p-inputicon:last-child {
        inset-inline-end: dt('form.field.padding.x');
    }

    .p-iconfield .p-inputtext:not(:first-child),
    .p-iconfield .p-inputwrapper:not(:first-child) .p-inputtext {
        padding-inline-start: calc((dt('form.field.padding.x') * 2) + dt('icon.size'));
    }

    .p-iconfield .p-inputtext:not(:last-child) {
        padding-inline-end: calc((dt('form.field.padding.x') * 2) + dt('icon.size'));
    }

    .p-iconfield:has(.p-inputfield-sm) .p-inputicon {
        font-size: dt('form.field.sm.font.size');
        width: dt('form.field.sm.font.size');
        height: dt('form.field.sm.font.size');
        margin-top: calc(-1 * (dt('form.field.sm.font.size') / 2));
    }

    .p-iconfield:has(.p-inputfield-lg) .p-inputicon {
        font-size: dt('form.field.lg.font.size');
        width: dt('form.field.lg.font.size');
        height: dt('form.field.lg.font.size');
        margin-top: calc(-1 * (dt('form.field.lg.font.size') / 2));
    }
`,Ht={root:"p-iconfield"},Vt=D.extend({name:"iconfield",style:kt,classes:Ht}),Rt={name:"BaseIconField",extends:Q,style:Vt,provide:function(){return{$pcIconField:this,$parentInstance:this}}},jt={name:"IconField",extends:Rt,inheritAttrs:!1};function _t(e,t,n,i,s,r){return z(),S("div",w({class:e.cx("root")},e.ptmi("root")),[L(e.$slots,"default")],16)}jt.render=_t;var Mt={root:"p-inputicon"},Wt=D.extend({name:"inputicon",classes:Mt}),Et={name:"BaseInputIcon",extends:Q,style:Wt,props:{class:null},provide:function(){return{$pcInputIcon:this,$parentInstance:this}}},Nt={name:"InputIcon",extends:Et,inheritAttrs:!1,computed:{containerClass:function(){return[this.cx("root"),this.class]}}};function Ft(e,t,n,i,s,r){return z(),S("span",w({class:r.containerClass},e.ptmi("root"),{"aria-hidden":"true"}),[L(e.$slots,"default")],16)}Nt.render=Ft;var Dt=`
    .p-inputtext {
        font-family: inherit;
        font-feature-settings: inherit;
        font-size: 1rem;
        color: dt('inputtext.color');
        background: dt('inputtext.background');
        padding-block: dt('inputtext.padding.y');
        padding-inline: dt('inputtext.padding.x');
        border: 1px solid dt('inputtext.border.color');
        transition:
            background dt('inputtext.transition.duration'),
            color dt('inputtext.transition.duration'),
            border-color dt('inputtext.transition.duration'),
            outline-color dt('inputtext.transition.duration'),
            box-shadow dt('inputtext.transition.duration');
        appearance: none;
        border-radius: dt('inputtext.border.radius');
        outline-color: transparent;
        box-shadow: dt('inputtext.shadow');
    }

    .p-inputtext:enabled:hover {
        border-color: dt('inputtext.hover.border.color');
    }

    .p-inputtext:enabled:focus {
        border-color: dt('inputtext.focus.border.color');
        box-shadow: dt('inputtext.focus.ring.shadow');
        outline: dt('inputtext.focus.ring.width') dt('inputtext.focus.ring.style') dt('inputtext.focus.ring.color');
        outline-offset: dt('inputtext.focus.ring.offset');
    }

    .p-inputtext.p-invalid {
        border-color: dt('inputtext.invalid.border.color');
    }

    .p-inputtext.p-variant-filled {
        background: dt('inputtext.filled.background');
    }

    .p-inputtext.p-variant-filled:enabled:hover {
        background: dt('inputtext.filled.hover.background');
    }

    .p-inputtext.p-variant-filled:enabled:focus {
        background: dt('inputtext.filled.focus.background');
    }

    .p-inputtext:disabled {
        opacity: 1;
        background: dt('inputtext.disabled.background');
        color: dt('inputtext.disabled.color');
    }

    .p-inputtext::placeholder {
        color: dt('inputtext.placeholder.color');
    }

    .p-inputtext.p-invalid::placeholder {
        color: dt('inputtext.invalid.placeholder.color');
    }

    .p-inputtext-sm {
        font-size: dt('inputtext.sm.font.size');
        padding-block: dt('inputtext.sm.padding.y');
        padding-inline: dt('inputtext.sm.padding.x');
    }

    .p-inputtext-lg {
        font-size: dt('inputtext.lg.font.size');
        padding-block: dt('inputtext.lg.padding.y');
        padding-inline: dt('inputtext.lg.padding.x');
    }

    .p-inputtext-fluid {
        width: 100%;
    }
`,Kt={root:function(t){var n=t.instance,i=t.props;return["p-inputtext p-component",{"p-filled":n.$filled,"p-inputtext-sm p-inputfield-sm":i.size==="small","p-inputtext-lg p-inputfield-lg":i.size==="large","p-invalid":n.$invalid,"p-variant-filled":n.$variant==="filled","p-inputtext-fluid":n.$fluid}]}},Zt=D.extend({name:"inputtext",style:Dt,classes:Kt}),Ut={name:"BaseInputText",extends:ht,style:Zt,provide:function(){return{$pcInputText:this,$parentInstance:this}}};function W(e){"@babel/helpers - typeof";return W=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},W(e)}function qt(e,t,n){return(t=Gt(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function Gt(e){var t=Jt(e,"string");return W(t)=="symbol"?t:t+""}function Jt(e,t){if(W(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var i=n.call(e,t);if(W(i)!="object")return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var Qt={name:"InputText",extends:Ut,inheritAttrs:!1,methods:{onInput:function(t){this.writeValue(t.target.value,t)}},computed:{attrs:function(){return w(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return ct(qt({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},Xt=["value","name","disabled","aria-invalid","data-p"];function Yt(e,t,n,i,s,r){return z(),S("input",w({type:"text",class:e.cx("root"),value:e.d_value,name:e.name,disabled:e.disabled,"aria-invalid":e.$invalid||void 0,"data-p":r.dataP,onInput:t[0]||(t[0]=function(){return r.onInput&&r.onInput.apply(r,arguments)})},r.attrs),null,16,Xt)}Qt.render=Yt;var fe=lt(),te=`
    .p-virtualscroller-loader {
        background: dt('virtualscroller.loader.mask.background');
        color: dt('virtualscroller.loader.mask.color');
    }

    .p-virtualscroller-loading-icon {
        font-size: dt('virtualscroller.loader.icon.size');
        width: dt('virtualscroller.loader.icon.size');
        height: dt('virtualscroller.loader.icon.size');
    }
`,ee=`
.p-virtualscroller {
    position: relative;
    overflow: auto;
    contain: strict;
    transform: translateZ(0);
    will-change: scroll-position;
    outline: 0 none;
}

.p-virtualscroller-content {
    position: absolute;
    top: 0;
    left: 0;
    min-height: 100%;
    min-width: 100%;
    will-change: transform;
}

.p-virtualscroller-spacer {
    position: absolute;
    top: 0;
    left: 0;
    height: 1px;
    width: 1px;
    transform-origin: 0 0;
    pointer-events: none;
}

.p-virtualscroller-loader {
    position: sticky;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.p-virtualscroller-loader-mask {
    display: flex;
    align-items: center;
    justify-content: center;
}

.p-virtualscroller-horizontal > .p-virtualscroller-content {
    display: flex;
}

.p-virtualscroller-inline .p-virtualscroller-content {
    position: static;
}

.p-virtualscroller .p-virtualscroller-loading {
    transform: none !important;
    min-height: 0;
    position: sticky;
    inset-block-start: 0;
    inset-inline-start: 0;
}
`,et=D.extend({name:"virtualscroller",css:ee,style:te}),ne={name:"BaseVirtualScroller",extends:Q,props:{id:{type:String,default:null},style:null,class:null,items:{type:Array,default:null},itemSize:{type:[Number,Array],default:0},scrollHeight:null,scrollWidth:null,orientation:{type:String,default:"vertical"},numToleratedItems:{type:Number,default:null},delay:{type:Number,default:0},resizeDelay:{type:Number,default:10},lazy:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},loaderDisabled:{type:Boolean,default:!1},columns:{type:Array,default:null},loading:{type:Boolean,default:!1},showSpacer:{type:Boolean,default:!0},showLoader:{type:Boolean,default:!1},tabindex:{type:Number,default:0},inline:{type:Boolean,default:!1},step:{type:Number,default:0},appendOnly:{type:Boolean,default:!1},autoSize:{type:Boolean,default:!1}},style:et,provide:function(){return{$pcVirtualScroller:this,$parentInstance:this}},beforeMount:function(){var t;et.loadCSS({nonce:(t=this.$primevueConfig)===null||t===void 0||(t=t.csp)===null||t===void 0?void 0:t.nonce})}};function E(e){"@babel/helpers - typeof";return E=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},E(e)}function nt(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);t&&(i=i.filter(function(s){return Object.getOwnPropertyDescriptor(e,s).enumerable})),n.push.apply(n,i)}return n}function _(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]!=null?arguments[t]:{};t%2?nt(Object(n),!0).forEach(function(i){rt(e,i,n[i])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):nt(Object(n)).forEach(function(i){Object.defineProperty(e,i,Object.getOwnPropertyDescriptor(n,i))})}return e}function rt(e,t,n){return(t=ie(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function ie(e){var t=re(e,"string");return E(t)=="symbol"?t:t+""}function re(e,t){if(E(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var i=n.call(e,t);if(E(i)!="object")return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var se={name:"VirtualScroller",extends:ne,inheritAttrs:!1,emits:["update:numToleratedItems","scroll","scroll-index-change","lazy-load"],data:function(){var t=this.isBoth();return{first:t?{rows:0,cols:0}:0,last:t?{rows:0,cols:0}:0,page:t?{rows:0,cols:0}:0,numItemsInViewport:t?{rows:0,cols:0}:0,lastScrollPos:t?{top:0,left:0}:0,d_numToleratedItems:this.numToleratedItems,d_loading:this.loading,loaderArr:[],spacerStyle:{},contentStyle:{}}},element:null,content:null,lastScrollPos:null,scrollTimeout:null,resizeTimeout:null,defaultWidth:0,defaultHeight:0,defaultContentWidth:0,defaultContentHeight:0,isRangeChanged:!1,lazyLoadState:{},resizeListener:null,resizeObserver:null,initialized:!1,watch:{numToleratedItems:function(t){this.d_numToleratedItems=t},loading:function(t,n){this.lazy&&t!==n&&t!==this.d_loading&&(this.d_loading=t)},items:{handler:function(t,n){(!n||n.length!==(t||[]).length)&&(this.init(),this.calculateAutoSize())},deep:!0},itemSize:function(){this.init(),this.calculateAutoSize()},orientation:function(){this.lastScrollPos=this.isBoth()?{top:0,left:0}:0},scrollHeight:function(){this.init(),this.calculateAutoSize()},scrollWidth:function(){this.init(),this.calculateAutoSize()}},mounted:function(){this.viewInit(),this.lastScrollPos=this.isBoth()?{top:0,left:0}:0,this.lazyLoadState=this.lazyLoadState||{}},updated:function(){!this.initialized&&this.viewInit()},unmounted:function(){this.unbindResizeListener(),this.initialized=!1},methods:{viewInit:function(){Y(this.element)&&(this.setContentEl(this.content),this.init(),this.calculateAutoSize(),this.defaultWidth=R(this.element),this.defaultHeight=j(this.element),this.defaultContentWidth=R(this.content),this.defaultContentHeight=j(this.content),this.initialized=!0),this.element&&this.bindResizeListener()},init:function(){this.disabled||(this.setSize(),this.calculateOptions(),this.setSpacerSize())},isVertical:function(){return this.orientation==="vertical"},isHorizontal:function(){return this.orientation==="horizontal"},isBoth:function(){return this.orientation==="both"},scrollTo:function(t){this.element&&this.element.scrollTo(t)},scrollToIndex:function(t){var n=this,i=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"auto",s=this.isBoth(),r=this.isHorizontal(),l=s?t.every(function($){return $>-1}):t>-1;if(l){var a=this.first,u=this.element,d=u.scrollTop,o=d===void 0?0:d,c=u.scrollLeft,h=c===void 0?0:c,g=this.calculateNumItems(),m=g.numToleratedItems,v=this.getContentPosition(),y=this.itemSize,P=function(){var C=arguments.length>0&&arguments[0]!==void 0?arguments[0]:0,B=arguments.length>1?arguments[1]:void 0;return C<=B?0:C},I=function(C,B,H){return C*B+H},O=function(){var C=arguments.length>0&&arguments[0]!==void 0?arguments[0]:0,B=arguments.length>1&&arguments[1]!==void 0?arguments[1]:0;return n.scrollTo({left:C,top:B,behavior:i})},f=s?{rows:0,cols:0}:0,k=!1,T=!1;s?(f={rows:P(t[0],m[0]),cols:P(t[1],m[1])},O(I(f.cols,y[1],v.left),I(f.rows,y[0],v.top)),T=this.lastScrollPos.top!==o||this.lastScrollPos.left!==h,k=f.rows!==a.rows||f.cols!==a.cols):(f=P(t,m),r?O(I(f,y,v.left),o):O(h,I(f,y,v.top)),T=this.lastScrollPos!==(r?h:o),k=f!==a),this.isRangeChanged=k,T&&(this.first=f)}},scrollInView:function(t,n){var i=this,s=arguments.length>2&&arguments[2]!==void 0?arguments[2]:"auto";if(n){var r=this.isBoth(),l=this.isHorizontal(),a=r?t.every(function(y){return y>-1}):t>-1;if(a){var u=this.getRenderedRange(),d=u.first,o=u.viewport,c=function(){var P=arguments.length>0&&arguments[0]!==void 0?arguments[0]:0,I=arguments.length>1&&arguments[1]!==void 0?arguments[1]:0;return i.scrollTo({left:P,top:I,behavior:s})},h=n==="to-start",g=n==="to-end";if(h){if(r)o.first.rows-d.rows>t[0]?c(o.first.cols*this.itemSize[1],(o.first.rows-1)*this.itemSize[0]):o.first.cols-d.cols>t[1]&&c((o.first.cols-1)*this.itemSize[1],o.first.rows*this.itemSize[0]);else if(o.first-d>t){var m=(o.first-1)*this.itemSize;l?c(m,0):c(0,m)}}else if(g){if(r)o.last.rows-d.rows<=t[0]+1?c(o.first.cols*this.itemSize[1],(o.first.rows+1)*this.itemSize[0]):o.last.cols-d.cols<=t[1]+1&&c((o.first.cols+1)*this.itemSize[1],o.first.rows*this.itemSize[0]);else if(o.last-d<=t+1){var v=(o.first+1)*this.itemSize;l?c(v,0):c(0,v)}}}}else this.scrollToIndex(t,s)},getRenderedRange:function(){var t=function(c,h){return Math.floor(c/(h||c))},n=this.first,i=0;if(this.element){var s=this.isBoth(),r=this.isHorizontal(),l=this.element,a=l.scrollTop,u=l.scrollLeft;if(s)n={rows:t(a,this.itemSize[0]),cols:t(u,this.itemSize[1])},i={rows:n.rows+this.numItemsInViewport.rows,cols:n.cols+this.numItemsInViewport.cols};else{var d=r?u:a;n=t(d,this.itemSize),i=n+this.numItemsInViewport}}return{first:this.first,last:this.last,viewport:{first:n,last:i}}},calculateNumItems:function(){var t=this.isBoth(),n=this.isHorizontal(),i=this.itemSize,s=this.getContentPosition(),r=this.element?this.element.offsetWidth-s.left:0,l=this.element?this.element.offsetHeight-s.top:0,a=function(h,g){return Math.ceil(h/(g||h))},u=function(h){return Math.ceil(h/2)},d=t?{rows:a(l,i[0]),cols:a(r,i[1])}:a(n?r:l,i),o=this.d_numToleratedItems||(t?[u(d.rows),u(d.cols)]:u(d));return{numItemsInViewport:d,numToleratedItems:o}},calculateOptions:function(){var t=this,n=this.isBoth(),i=this.first,s=this.calculateNumItems(),r=s.numItemsInViewport,l=s.numToleratedItems,a=function(o,c,h){var g=arguments.length>3&&arguments[3]!==void 0?arguments[3]:!1;return t.getLast(o+c+(o<h?2:3)*h,g)},u=n?{rows:a(i.rows,r.rows,l[0]),cols:a(i.cols,r.cols,l[1],!0)}:a(i,r,l);this.last=u,this.numItemsInViewport=r,this.d_numToleratedItems=l,this.$emit("update:numToleratedItems",this.d_numToleratedItems),this.showLoader&&(this.loaderArr=n?Array.from({length:r.rows}).map(function(){return Array.from({length:r.cols})}):Array.from({length:r})),this.lazy&&Promise.resolve().then(function(){var d;t.lazyLoadState={first:t.step?n?{rows:0,cols:i.cols}:0:i,last:Math.min(t.step?t.step:u,((d=t.items)===null||d===void 0?void 0:d.length)||0)},t.$emit("lazy-load",t.lazyLoadState)})},calculateAutoSize:function(){var t=this;this.autoSize&&!this.d_loading&&Promise.resolve().then(function(){if(t.content){var n=t.isBoth(),i=t.isHorizontal(),s=t.isVertical();t.content.style.minHeight=t.content.style.minWidth="auto",t.content.style.position="relative",t.element.style.contain="none";var r=[R(t.element),j(t.element)],l=r[0],a=r[1];(n||i)&&(t.element.style.width=l<t.defaultWidth?l+"px":t.scrollWidth||t.defaultWidth+"px"),(n||s)&&(t.element.style.height=a<t.defaultHeight?a+"px":t.scrollHeight||t.defaultHeight+"px"),t.content.style.minHeight=t.content.style.minWidth="",t.content.style.position="",t.element.style.contain=""}})},getLast:function(){var t,n,i=arguments.length>0&&arguments[0]!==void 0?arguments[0]:0,s=arguments.length>1?arguments[1]:void 0;return this.items?Math.min(s?((t=this.columns||this.items[0])===null||t===void 0?void 0:t.length)||0:((n=this.items)===null||n===void 0?void 0:n.length)||0,i):0},getContentPosition:function(){if(this.content){var t=getComputedStyle(this.content),n=parseFloat(t.paddingLeft)+Math.max(parseFloat(t.left)||0,0),i=parseFloat(t.paddingRight)+Math.max(parseFloat(t.right)||0,0),s=parseFloat(t.paddingTop)+Math.max(parseFloat(t.top)||0,0),r=parseFloat(t.paddingBottom)+Math.max(parseFloat(t.bottom)||0,0);return{left:n,right:i,top:s,bottom:r,x:n+i,y:s+r}}return{left:0,right:0,top:0,bottom:0,x:0,y:0}},setSize:function(){var t=this;if(this.element){var n=this.isBoth(),i=this.isHorizontal(),s=this.element.parentElement,r=this.scrollWidth||"".concat(this.element.offsetWidth||s.offsetWidth,"px"),l=this.scrollHeight||"".concat(this.element.offsetHeight||s.offsetHeight,"px"),a=function(d,o){return t.element.style[d]=o};n||i?(a("height",l),a("width",r)):a("height",l)}},setSpacerSize:function(){var t=this,n=this.items;if(n){var i=this.isBoth(),s=this.isHorizontal(),r=this.getContentPosition(),l=function(u,d,o){var c=arguments.length>3&&arguments[3]!==void 0?arguments[3]:0;return t.spacerStyle=_(_({},t.spacerStyle),rt({},"".concat(u),(d||[]).length*o+c+"px"))};i?(l("height",n,this.itemSize[0],r.y),l("width",this.columns||n[1],this.itemSize[1],r.x)):s?l("width",this.columns||n,this.itemSize,r.x):l("height",n,this.itemSize,r.y)}},setContentPosition:function(t){var n=this;if(this.content&&!this.appendOnly){var i=this.isBoth(),s=this.isHorizontal(),r=t?t.first:this.first,l=function(o,c){return o*c},a=function(){var o=arguments.length>0&&arguments[0]!==void 0?arguments[0]:0,c=arguments.length>1&&arguments[1]!==void 0?arguments[1]:0;return n.contentStyle=_(_({},n.contentStyle),{transform:"translate3d(".concat(o,"px, ").concat(c,"px, 0)")})};if(i)a(l(r.cols,this.itemSize[1]),l(r.rows,this.itemSize[0]));else{var u=l(r,this.itemSize);s?a(u,0):a(0,u)}}},onScrollPositionChange:function(t){var n=this,i=t.target,s=this.isBoth(),r=this.isHorizontal(),l=this.getContentPosition(),a=function(p,b){return p?p>b?p-b:p:0},u=function(p,b){return Math.floor(p/(b||p))},d=function(p,b,V,N,x,A){return p<=x?x:A?V-N-x:b+x-1},o=function(p,b,V,N,x,A,F,st){if(p<=A)return 0;var K=Math.max(0,F?p<b?V:p-A:p>b?V:p-2*A),X=n.getLast(K,st);return K>X?X-x:K},c=function(p,b,V,N,x,A){var F=b+N+2*x;return p>=x&&(F+=x+1),n.getLast(F,A)},h=a(i.scrollTop,l.top),g=a(i.scrollLeft,l.left),m=s?{rows:0,cols:0}:0,v=this.last,y=!1,P=this.lastScrollPos;if(s){var I=this.lastScrollPos.top<=h,O=this.lastScrollPos.left<=g;if(!this.appendOnly||this.appendOnly&&(I||O)){var f={rows:u(h,this.itemSize[0]),cols:u(g,this.itemSize[1])},k={rows:d(f.rows,this.first.rows,this.last.rows,this.numItemsInViewport.rows,this.d_numToleratedItems[0],I),cols:d(f.cols,this.first.cols,this.last.cols,this.numItemsInViewport.cols,this.d_numToleratedItems[1],O)};m={rows:o(f.rows,k.rows,this.first.rows,this.last.rows,this.numItemsInViewport.rows,this.d_numToleratedItems[0],I),cols:o(f.cols,k.cols,this.first.cols,this.last.cols,this.numItemsInViewport.cols,this.d_numToleratedItems[1],O,!0)},v={rows:c(f.rows,m.rows,this.last.rows,this.numItemsInViewport.rows,this.d_numToleratedItems[0]),cols:c(f.cols,m.cols,this.last.cols,this.numItemsInViewport.cols,this.d_numToleratedItems[1],!0)},y=m.rows!==this.first.rows||v.rows!==this.last.rows||m.cols!==this.first.cols||v.cols!==this.last.cols||this.isRangeChanged,P={top:h,left:g}}}else{var T=r?g:h,$=this.lastScrollPos<=T;if(!this.appendOnly||this.appendOnly&&$){var C=u(T,this.itemSize),B=d(C,this.first,this.last,this.numItemsInViewport,this.d_numToleratedItems,$);m=o(C,B,this.first,this.last,this.numItemsInViewport,this.d_numToleratedItems,$),v=c(C,m,this.last,this.numItemsInViewport,this.d_numToleratedItems),y=m!==this.first||v!==this.last||this.isRangeChanged,P=T}}return{first:m,last:v,isRangeChanged:y,scrollPos:P}},onScrollChange:function(t){var n=this.onScrollPositionChange(t),i=n.first,s=n.last,r=n.isRangeChanged,l=n.scrollPos;if(r){var a={first:i,last:s};if(this.setContentPosition(a),this.first=i,this.last=s,this.lastScrollPos=l,this.$emit("scroll-index-change",a),this.lazy&&this.isPageChanged(i)){var u,d,o={first:this.step?Math.min(this.getPageByFirst(i)*this.step,(((u=this.items)===null||u===void 0?void 0:u.length)||0)-this.step):i,last:Math.min(this.step?(this.getPageByFirst(i)+1)*this.step:s,((d=this.items)===null||d===void 0?void 0:d.length)||0)},c=this.lazyLoadState.first!==o.first||this.lazyLoadState.last!==o.last;c&&this.$emit("lazy-load",o),this.lazyLoadState=o}}},onScroll:function(t){var n=this;if(this.$emit("scroll",t),this.delay){if(this.scrollTimeout&&clearTimeout(this.scrollTimeout),this.isPageChanged()){if(!this.d_loading&&this.showLoader){var i=this.onScrollPositionChange(t),s=i.isRangeChanged,r=s||(this.step?this.isPageChanged():!1);r&&(this.d_loading=!0)}this.scrollTimeout=setTimeout(function(){n.onScrollChange(t),n.d_loading&&n.showLoader&&(!n.lazy||n.loading===void 0)&&(n.d_loading=!1,n.page=n.getPageByFirst())},this.delay)}}else this.onScrollChange(t)},onResize:function(){var t=this;this.resizeTimeout&&clearTimeout(this.resizeTimeout),this.resizeTimeout=setTimeout(function(){if(Y(t.element)){var n=t.isBoth(),i=t.isVertical(),s=t.isHorizontal(),r=[R(t.element),j(t.element)],l=r[0],a=r[1],u=l!==t.defaultWidth,d=a!==t.defaultHeight,o=n?u||d:s?u:i?d:!1;o&&(t.d_numToleratedItems=t.numToleratedItems,t.defaultWidth=l,t.defaultHeight=a,t.defaultContentWidth=R(t.content),t.defaultContentHeight=j(t.content),t.init())}},this.resizeDelay)},bindResizeListener:function(){var t=this;this.resizeListener||(this.resizeListener=this.onResize.bind(this),window.addEventListener("resize",this.resizeListener),window.addEventListener("orientationchange",this.resizeListener),this.resizeObserver=new ResizeObserver(function(){t.onResize()}),this.resizeObserver.observe(this.element))},unbindResizeListener:function(){this.resizeListener&&(window.removeEventListener("resize",this.resizeListener),window.removeEventListener("orientationchange",this.resizeListener),this.resizeListener=null),this.resizeObserver&&(this.resizeObserver.disconnect(),this.resizeObserver=null)},getOptions:function(t){var n=(this.items||[]).length,i=this.isBoth()?this.first.rows+t:this.first+t;return{index:i,count:n,first:i===0,last:i===n-1,even:i%2===0,odd:i%2!==0}},getLoaderOptions:function(t,n){var i=this.loaderArr.length;return _({index:t,count:i,first:t===0,last:t===i-1,even:t%2===0,odd:t%2!==0},n)},getPageByFirst:function(t){return Math.floor(((t??this.first)+this.d_numToleratedItems*4)/(this.step||1))},isPageChanged:function(t){return this.step&&!this.lazy?this.page!==this.getPageByFirst(t??this.first):!0},setContentEl:function(t){this.content=t||this.content||at(this.element,'[data-pc-section="content"]')},elementRef:function(t){this.element=t},contentRef:function(t){this.content=t}},computed:{containerClass:function(){return["p-virtualscroller",this.class,{"p-virtualscroller-inline":this.inline,"p-virtualscroller-both p-both-scroll":this.isBoth(),"p-virtualscroller-horizontal p-horizontal-scroll":this.isHorizontal()}]},contentClass:function(){return["p-virtualscroller-content",{"p-virtualscroller-loading":this.d_loading}]},loaderClass:function(){return["p-virtualscroller-loader",{"p-virtualscroller-loader-mask":!this.$slots.loader}]},loadedItems:function(){var t=this;return this.items&&!this.d_loading?this.isBoth()?this.items.slice(this.appendOnly?0:this.first.rows,this.last.rows).map(function(n){return t.columns?n:n.slice(t.appendOnly?0:t.first.cols,t.last.cols)}):this.isHorizontal()&&this.columns?this.items:this.items.slice(this.appendOnly?0:this.first,this.last):[]},loadedRows:function(){return this.d_loading?this.loaderDisabled?this.loaderArr:[]:this.loadedItems},loadedColumns:function(){if(this.columns){var t=this.isBoth(),n=this.isHorizontal();if(t||n)return this.d_loading&&this.loaderDisabled?t?this.loaderArr[0]:this.loaderArr:this.columns.slice(t?this.first.cols:this.first,t?this.last.cols:this.last)}return this.columns}},components:{SpinnerIcon:ft}},oe=["tabindex"];function le(e,t,n,i,s,r){var l=ut("SpinnerIcon");return e.disabled?(z(),S(U,{key:1},[L(e.$slots,"default"),L(e.$slots,"content",{items:e.items,rows:e.items,columns:r.loadedColumns})],64)):(z(),S("div",w({key:0,ref:r.elementRef,class:r.containerClass,tabindex:e.tabindex,style:e.style,onScroll:t[0]||(t[0]=function(){return r.onScroll&&r.onScroll.apply(r,arguments)})},e.ptmi("root")),[L(e.$slots,"content",{styleClass:r.contentClass,items:r.loadedItems,getItemOptions:r.getOptions,loading:s.d_loading,getLoaderOptions:r.getLoaderOptions,itemSize:e.itemSize,rows:r.loadedRows,columns:r.loadedColumns,contentRef:r.contentRef,spacerStyle:s.spacerStyle,contentStyle:s.contentStyle,vertical:r.isVertical(),horizontal:r.isHorizontal(),both:r.isBoth()},function(){return[J("div",w({ref:r.contentRef,class:r.contentClass,style:s.contentStyle},e.ptm("content")),[(z(!0),S(U,null,tt(r.loadedItems,function(a,u){return L(e.$slots,"item",{key:u,item:a,options:r.getOptions(u)})}),128))],16)]}),e.showSpacer?(z(),S("div",w({key:0,class:"p-virtualscroller-spacer",style:s.spacerStyle},e.ptm("spacer")),null,16)):Z("",!0),!e.loaderDisabled&&e.showLoader&&s.d_loading?(z(),S("div",w({key:1,class:r.loaderClass},e.ptm("loader")),[e.$slots&&e.$slots.loader?(z(!0),S(U,{key:0},tt(s.loaderArr,function(a,u){return L(e.$slots,"loader",{key:u,options:r.getLoaderOptions(u,r.isBoth()&&{numCols:e.d_numItemsInViewport.cols})})}),128)):Z("",!0),L(e.$slots,"loadingicon",{},function(){return[dt(l,w({spin:"",class:"p-virtualscroller-loading-icon"},e.ptm("loadingIcon")),null,16)]})],16)):Z("",!0)],16,oe))}se.render=le;export{he as C,fe as O,bt as a,jt as b,Nt as c,se as d,Qt as e,Pt as s};
