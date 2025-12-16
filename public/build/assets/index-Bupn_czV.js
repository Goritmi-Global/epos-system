import{s as p,a as P,f as I}from"./index-DmJMBdeX.js";import{a0 as v,g as d,b as l,H as u,a3 as s,f as b,aV as T,aU as W,ag as w,aW as D,aX as f,an as H,b3 as L,a4 as N,m as y,l as x,a as m,k as $,ai as V,a6 as j,aS as g,d as K,j as _,F as S,ae as U}from"./app-O8d-RKSx.js";import{R as z}from"./index-DhD8bg6I.js";var M=`
    .p-tabs {
        display: flex;
        flex-direction: column;
    }

    .p-tablist {
        display: flex;
        position: relative;
        overflow: hidden;
        background: dt('tabs.tablist.background');
    }

    .p-tablist-viewport {
        overflow-x: auto;
        overflow-y: hidden;
        scroll-behavior: smooth;
        scrollbar-width: none;
        overscroll-behavior: contain auto;
    }

    .p-tablist-viewport::-webkit-scrollbar {
        display: none;
    }

    .p-tablist-tab-list {
        position: relative;
        display: flex;
        border-style: solid;
        border-color: dt('tabs.tablist.border.color');
        border-width: dt('tabs.tablist.border.width');
    }

    .p-tablist-content {
        flex-grow: 1;
    }

    .p-tablist-nav-button {
        all: unset;
        position: absolute !important;
        flex-shrink: 0;
        inset-block-start: 0;
        z-index: 2;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: dt('tabs.nav.button.background');
        color: dt('tabs.nav.button.color');
        width: dt('tabs.nav.button.width');
        transition:
            color dt('tabs.transition.duration'),
            outline-color dt('tabs.transition.duration'),
            box-shadow dt('tabs.transition.duration');
        box-shadow: dt('tabs.nav.button.shadow');
        outline-color: transparent;
        cursor: pointer;
    }

    .p-tablist-nav-button:focus-visible {
        z-index: 1;
        box-shadow: dt('tabs.nav.button.focus.ring.shadow');
        outline: dt('tabs.nav.button.focus.ring.width') dt('tabs.nav.button.focus.ring.style') dt('tabs.nav.button.focus.ring.color');
        outline-offset: dt('tabs.nav.button.focus.ring.offset');
    }

    .p-tablist-nav-button:hover {
        color: dt('tabs.nav.button.hover.color');
    }

    .p-tablist-prev-button {
        inset-inline-start: 0;
    }

    .p-tablist-next-button {
        inset-inline-end: 0;
    }

    .p-tablist-prev-button:dir(rtl),
    .p-tablist-next-button:dir(rtl) {
        transform: rotate(180deg);
    }

    .p-tab {
        flex-shrink: 0;
        cursor: pointer;
        user-select: none;
        position: relative;
        border-style: solid;
        white-space: nowrap;
        gap: dt('tabs.tab.gap');
        background: dt('tabs.tab.background');
        border-width: dt('tabs.tab.border.width');
        border-color: dt('tabs.tab.border.color');
        color: dt('tabs.tab.color');
        padding: dt('tabs.tab.padding');
        font-weight: dt('tabs.tab.font.weight');
        transition:
            background dt('tabs.transition.duration'),
            border-color dt('tabs.transition.duration'),
            color dt('tabs.transition.duration'),
            outline-color dt('tabs.transition.duration'),
            box-shadow dt('tabs.transition.duration');
        margin: dt('tabs.tab.margin');
        outline-color: transparent;
    }

    .p-tab:not(.p-disabled):focus-visible {
        z-index: 1;
        box-shadow: dt('tabs.tab.focus.ring.shadow');
        outline: dt('tabs.tab.focus.ring.width') dt('tabs.tab.focus.ring.style') dt('tabs.tab.focus.ring.color');
        outline-offset: dt('tabs.tab.focus.ring.offset');
    }

    .p-tab:not(.p-tab-active):not(.p-disabled):hover {
        background: dt('tabs.tab.hover.background');
        border-color: dt('tabs.tab.hover.border.color');
        color: dt('tabs.tab.hover.color');
    }

    .p-tab-active {
        background: dt('tabs.tab.active.background');
        border-color: dt('tabs.tab.active.border.color');
        color: dt('tabs.tab.active.color');
    }

    .p-tabpanels {
        background: dt('tabs.tabpanel.background');
        color: dt('tabs.tabpanel.color');
        padding: dt('tabs.tabpanel.padding');
        outline: 0 none;
    }

    .p-tabpanel:focus-visible {
        box-shadow: dt('tabs.tabpanel.focus.ring.shadow');
        outline: dt('tabs.tabpanel.focus.ring.width') dt('tabs.tabpanel.focus.ring.style') dt('tabs.tabpanel.focus.ring.color');
        outline-offset: dt('tabs.tabpanel.focus.ring.offset');
    }

    .p-tablist-active-bar {
        z-index: 1;
        display: block;
        position: absolute;
        inset-block-end: dt('tabs.active.bar.bottom');
        height: dt('tabs.active.bar.height');
        background: dt('tabs.active.bar.background');
        transition: 250ms cubic-bezier(0.35, 0, 0.25, 1);
    }
`,Z={root:function(e){var a=e.props;return["p-tabs p-component",{"p-tabs-scrollable":a.scrollable}]}},Q=v.extend({name:"tabs",style:M,classes:Z}),X={name:"BaseTabs",extends:p,props:{value:{type:[String,Number],default:void 0},lazy:{type:Boolean,default:!1},scrollable:{type:Boolean,default:!1},showNavigators:{type:Boolean,default:!0},tabindex:{type:Number,default:0},selectOnFocus:{type:Boolean,default:!1}},style:Q,provide:function(){return{$pcTabs:this,$parentInstance:this}}},q={name:"Tabs",extends:X,inheritAttrs:!1,emits:["update:value"],data:function(){return{d_value:this.value}},watch:{value:function(e){this.d_value=e}},methods:{updateValue:function(e){this.d_value!==e&&(this.d_value=e,this.$emit("update:value",e))},isVertical:function(){return this.orientation==="vertical"}}};function G(t,e,a,r,i,n){return l(),d("div",s({class:t.cx("root")},t.ptmi("root")),[u(t.$slots,"default")],16)}q.render=G;var E={name:"ChevronLeftIcon",extends:P};function J(t){return at(t)||et(t)||tt(t)||Y()}function Y(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function tt(t,e){if(t){if(typeof t=="string")return B(t,e);var a={}.toString.call(t).slice(8,-1);return a==="Object"&&t.constructor&&(a=t.constructor.name),a==="Map"||a==="Set"?Array.from(t):a==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(a)?B(t,e):void 0}}function et(t){if(typeof Symbol<"u"&&t[Symbol.iterator]!=null||t["@@iterator"]!=null)return Array.from(t)}function at(t){if(Array.isArray(t))return B(t)}function B(t,e){(e==null||e>t.length)&&(e=t.length);for(var a=0,r=Array(e);a<e;a++)r[a]=t[a];return r}function nt(t,e,a,r,i,n){return l(),d("svg",s({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},t.pti()),J(e[0]||(e[0]=[b("path",{d:"M9.61296 13C9.50997 13.0005 9.40792 12.9804 9.3128 12.9409C9.21767 12.9014 9.13139 12.8433 9.05902 12.7701L3.83313 7.54416C3.68634 7.39718 3.60388 7.19795 3.60388 6.99022C3.60388 6.78249 3.68634 6.58325 3.83313 6.43628L9.05902 1.21039C9.20762 1.07192 9.40416 0.996539 9.60724 1.00012C9.81032 1.00371 10.0041 1.08597 10.1477 1.22959C10.2913 1.37322 10.3736 1.56698 10.3772 1.77005C10.3808 1.97313 10.3054 2.16968 10.1669 2.31827L5.49496 6.99022L10.1669 11.6622C10.3137 11.8091 10.3962 12.0084 10.3962 12.2161C10.3962 12.4238 10.3137 12.6231 10.1669 12.7701C10.0945 12.8433 10.0083 12.9014 9.91313 12.9409C9.81801 12.9804 9.71596 13.0005 9.61296 13Z",fill:"currentColor"},null,-1)])),16)}E.render=nt;var O={name:"ChevronRightIcon",extends:P};function rt(t){return lt(t)||ot(t)||st(t)||it()}function it(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function st(t,e){if(t){if(typeof t=="string")return k(t,e);var a={}.toString.call(t).slice(8,-1);return a==="Object"&&t.constructor&&(a=t.constructor.name),a==="Map"||a==="Set"?Array.from(t):a==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(a)?k(t,e):void 0}}function ot(t){if(typeof Symbol<"u"&&t[Symbol.iterator]!=null||t["@@iterator"]!=null)return Array.from(t)}function lt(t){if(Array.isArray(t))return k(t)}function k(t,e){(e==null||e>t.length)&&(e=t.length);for(var a=0,r=Array(e);a<e;a++)r[a]=t[a];return r}function ct(t,e,a,r,i,n){return l(),d("svg",s({width:"14",height:"14",viewBox:"0 0 14 14",fill:"none",xmlns:"http://www.w3.org/2000/svg"},t.pti()),rt(e[0]||(e[0]=[b("path",{d:"M4.38708 13C4.28408 13.0005 4.18203 12.9804 4.08691 12.9409C3.99178 12.9014 3.9055 12.8433 3.83313 12.7701C3.68634 12.6231 3.60388 12.4238 3.60388 12.2161C3.60388 12.0084 3.68634 11.8091 3.83313 11.6622L8.50507 6.99022L3.83313 2.31827C3.69467 2.16968 3.61928 1.97313 3.62287 1.77005C3.62645 1.56698 3.70872 1.37322 3.85234 1.22959C3.99596 1.08597 4.18972 1.00371 4.3928 1.00012C4.59588 0.996539 4.79242 1.07192 4.94102 1.21039L10.1669 6.43628C10.3137 6.58325 10.3962 6.78249 10.3962 6.99022C10.3962 7.19795 10.3137 7.39718 10.1669 7.54416L4.94102 12.7701C4.86865 12.8433 4.78237 12.9014 4.68724 12.9409C4.59212 12.9804 4.49007 13.0005 4.38708 13Z",fill:"currentColor"},null,-1)])),16)}O.render=ct;var dt={root:"p-tablist",content:"p-tablist-content p-tablist-viewport",tabList:"p-tablist-tab-list",activeBar:"p-tablist-active-bar",prevButton:"p-tablist-prev-button p-tablist-nav-button",nextButton:"p-tablist-next-button p-tablist-nav-button"},ut=v.extend({name:"tablist",classes:dt}),bt={name:"BaseTabList",extends:p,props:{},style:ut,provide:function(){return{$pcTabList:this,$parentInstance:this}}},pt={name:"TabList",extends:bt,inheritAttrs:!1,inject:["$pcTabs"],data:function(){return{isPrevButtonEnabled:!1,isNextButtonEnabled:!0}},resizeObserver:void 0,watch:{showNavigators:function(e){e?this.bindResizeObserver():this.unbindResizeObserver()},activeValue:{flush:"post",handler:function(){this.updateInkBar()}}},mounted:function(){var e=this;setTimeout(function(){e.updateInkBar()},150),this.showNavigators&&(this.updateButtonState(),this.bindResizeObserver())},updated:function(){this.showNavigators&&this.updateButtonState()},beforeUnmount:function(){this.unbindResizeObserver()},methods:{onScroll:function(e){this.showNavigators&&this.updateButtonState(),e.preventDefault()},onPrevButtonClick:function(){var e=this.$refs.content,a=this.getVisibleButtonWidths(),r=T(e)-a,i=Math.abs(e.scrollLeft),n=r*.8,o=i-n,c=Math.max(o,0);e.scrollLeft=L(e)?-1*c:c},onNextButtonClick:function(){var e=this.$refs.content,a=this.getVisibleButtonWidths(),r=T(e)-a,i=Math.abs(e.scrollLeft),n=r*.8,o=i+n,c=e.scrollWidth-r,h=Math.min(o,c);e.scrollLeft=L(e)?-1*h:h},bindResizeObserver:function(){var e=this;this.resizeObserver=new ResizeObserver(function(){return e.updateButtonState()}),this.resizeObserver.observe(this.$refs.list)},unbindResizeObserver:function(){var e;(e=this.resizeObserver)===null||e===void 0||e.unobserve(this.$refs.list),this.resizeObserver=void 0},updateInkBar:function(){var e=this.$refs,a=e.content,r=e.inkbar,i=e.tabs;if(r){var n=w(a,'[data-pc-name="tab"][data-p-active="true"]');this.$pcTabs.isVertical()?(r.style.height=D(n)+"px",r.style.top=f(n).top-f(i).top+"px"):(r.style.width=H(n)+"px",r.style.left=f(n).left-f(i).left+"px")}},updateButtonState:function(){var e=this.$refs,a=e.list,r=e.content,i=r.scrollTop,n=r.scrollWidth,o=r.scrollHeight,c=r.offsetWidth,h=r.offsetHeight,C=Math.abs(r.scrollLeft),A=[T(r),W(r)],R=A[0],F=A[1];this.$pcTabs.isVertical()?(this.isPrevButtonEnabled=i!==0,this.isNextButtonEnabled=a.offsetHeight>=h&&parseInt(i)!==o-F):(this.isPrevButtonEnabled=C!==0,this.isNextButtonEnabled=a.offsetWidth>=c&&parseInt(C)!==n-R)},getVisibleButtonWidths:function(){var e=this.$refs,a=e.prevButton,r=e.nextButton,i=0;return this.showNavigators&&(i=(a?.offsetWidth||0)+(r?.offsetWidth||0)),i}},computed:{templates:function(){return this.$pcTabs.$slots},activeValue:function(){return this.$pcTabs.d_value},showNavigators:function(){return this.$pcTabs.showNavigators},prevButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.previous:void 0},nextButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.next:void 0},dataP:function(){return I({scrollable:this.$pcTabs.scrollable})}},components:{ChevronLeftIcon:E,ChevronRightIcon:O},directives:{ripple:z}},vt=["data-p"],ht=["aria-label","tabindex"],ft=["data-p"],gt=["aria-orientation"],yt=["aria-label","tabindex"];function mt(t,e,a,r,i,n){var o=N("ripple");return l(),d("div",s({ref:"list",class:t.cx("root"),"data-p":n.dataP},t.ptmi("root")),[n.showNavigators&&i.isPrevButtonEnabled?y((l(),d("button",s({key:0,ref:"prevButton",type:"button",class:t.cx("prevButton"),"aria-label":n.prevButtonAriaLabel,tabindex:n.$pcTabs.tabindex,onClick:e[0]||(e[0]=function(){return n.onPrevButtonClick&&n.onPrevButtonClick.apply(n,arguments)})},t.ptm("prevButton"),{"data-pc-group-section":"navigator"}),[(l(),m($(n.templates.previcon||"ChevronLeftIcon"),s({"aria-hidden":"true"},t.ptm("prevIcon")),null,16))],16,ht)),[[o]]):x("",!0),b("div",s({ref:"content",class:t.cx("content"),onScroll:e[1]||(e[1]=function(){return n.onScroll&&n.onScroll.apply(n,arguments)}),"data-p":n.dataP},t.ptm("content")),[b("div",s({ref:"tabs",class:t.cx("tabList"),role:"tablist","aria-orientation":n.$pcTabs.orientation||"horizontal"},t.ptm("tabList")),[u(t.$slots,"default"),b("span",s({ref:"inkbar",class:t.cx("activeBar"),role:"presentation","aria-hidden":"true"},t.ptm("activeBar")),null,16)],16,gt)],16,ft),n.showNavigators&&i.isNextButtonEnabled?y((l(),d("button",s({key:1,ref:"nextButton",type:"button",class:t.cx("nextButton"),"aria-label":n.nextButtonAriaLabel,tabindex:n.$pcTabs.tabindex,onClick:e[2]||(e[2]=function(){return n.onNextButtonClick&&n.onNextButtonClick.apply(n,arguments)})},t.ptm("nextButton"),{"data-pc-group-section":"navigator"}),[(l(),m($(n.templates.nexticon||"ChevronRightIcon"),s({"aria-hidden":"true"},t.ptm("nextIcon")),null,16))],16,yt)),[[o]]):x("",!0)],16,vt)}pt.render=mt;var $t={root:function(e){var a=e.instance,r=e.props;return["p-tab",{"p-tab-active":a.active,"p-disabled":r.disabled}]}},Tt=v.extend({name:"tab",classes:$t}),wt={name:"BaseTab",extends:p,props:{value:{type:[String,Number],default:void 0},disabled:{type:Boolean,default:!1},as:{type:[String,Object],default:"BUTTON"},asChild:{type:Boolean,default:!1}},style:Tt,provide:function(){return{$pcTab:this,$parentInstance:this}}},xt={name:"Tab",extends:wt,inheritAttrs:!1,inject:["$pcTabs","$pcTabList"],methods:{onFocus:function(){this.$pcTabs.selectOnFocus&&this.changeActiveValue()},onClick:function(){this.changeActiveValue()},onKeydown:function(e){switch(e.code){case"ArrowRight":this.onArrowRightKey(e);break;case"ArrowLeft":this.onArrowLeftKey(e);break;case"Home":this.onHomeKey(e);break;case"End":this.onEndKey(e);break;case"PageDown":this.onPageDownKey(e);break;case"PageUp":this.onPageUpKey(e);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(e);break}},onArrowRightKey:function(e){var a=this.findNextTab(e.currentTarget);a?this.changeFocusedTab(e,a):this.onHomeKey(e),e.preventDefault()},onArrowLeftKey:function(e){var a=this.findPrevTab(e.currentTarget);a?this.changeFocusedTab(e,a):this.onEndKey(e),e.preventDefault()},onHomeKey:function(e){var a=this.findFirstTab();this.changeFocusedTab(e,a),e.preventDefault()},onEndKey:function(e){var a=this.findLastTab();this.changeFocusedTab(e,a),e.preventDefault()},onPageDownKey:function(e){this.scrollInView(this.findLastTab()),e.preventDefault()},onPageUpKey:function(e){this.scrollInView(this.findFirstTab()),e.preventDefault()},onEnterKey:function(e){this.changeActiveValue()},findNextTab:function(e){var a=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,r=a?e:e.nextElementSibling;return r?g(r,"data-p-disabled")||g(r,"data-pc-section")==="activebar"?this.findNextTab(r):w(r,'[data-pc-name="tab"]'):null},findPrevTab:function(e){var a=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,r=a?e:e.previousElementSibling;return r?g(r,"data-p-disabled")||g(r,"data-pc-section")==="activebar"?this.findPrevTab(r):w(r,'[data-pc-name="tab"]'):null},findFirstTab:function(){return this.findNextTab(this.$pcTabList.$refs.tabs.firstElementChild,!0)},findLastTab:function(){return this.findPrevTab(this.$pcTabList.$refs.tabs.lastElementChild,!0)},changeActiveValue:function(){this.$pcTabs.updateValue(this.value)},changeFocusedTab:function(e,a){j(a),this.scrollInView(a)},scrollInView:function(e){var a;e==null||(a=e.scrollIntoView)===null||a===void 0||a.call(e,{block:"nearest"})}},computed:{active:function(){var e;return V((e=this.$pcTabs)===null||e===void 0?void 0:e.d_value,this.value)},id:function(){var e;return"".concat((e=this.$pcTabs)===null||e===void 0?void 0:e.$id,"_tab_").concat(this.value)},ariaControls:function(){var e;return"".concat((e=this.$pcTabs)===null||e===void 0?void 0:e.$id,"_tabpanel_").concat(this.value)},attrs:function(){return s(this.asAttrs,this.a11yAttrs,this.ptmi("root",this.ptParams))},asAttrs:function(){return this.as==="BUTTON"?{type:"button",disabled:this.disabled}:void 0},a11yAttrs:function(){return{id:this.id,tabindex:this.active?this.$pcTabs.tabindex:-1,role:"tab","aria-selected":this.active,"aria-controls":this.ariaControls,"data-pc-name":"tab","data-p-disabled":this.disabled,"data-p-active":this.active,onFocus:this.onFocus,onKeydown:this.onKeydown}},ptParams:function(){return{context:{active:this.active}}},dataP:function(){return I({active:this.active})}},directives:{ripple:z}};function Bt(t,e,a,r,i,n){var o=N("ripple");return t.asChild?u(t.$slots,"default",{key:1,dataP:n.dataP,class:_(t.cx("root")),active:n.active,a11yAttrs:n.a11yAttrs,onClick:n.onClick}):y((l(),m($(t.as),s({key:0,class:t.cx("root"),"data-p":n.dataP,onClick:n.onClick},n.attrs),{default:K(function(){return[u(t.$slots,"default")]}),_:3},16,["class","data-p","onClick"])),[[o]])}xt.render=Bt;var kt={root:"p-tabpanels"},Ct=v.extend({name:"tabpanels",classes:kt}),At={name:"BaseTabPanels",extends:p,props:{},style:Ct,provide:function(){return{$pcTabPanels:this,$parentInstance:this}}},Lt={name:"TabPanels",extends:At,inheritAttrs:!1};function St(t,e,a,r,i,n){return l(),d("div",s({class:t.cx("root"),role:"presentation"},t.ptmi("root")),[u(t.$slots,"default")],16)}Lt.render=St;var Pt={root:function(e){var a=e.instance;return["p-tabpanel",{"p-tabpanel-active":a.active}]}},It=v.extend({name:"tabpanel",classes:Pt}),Nt={name:"BaseTabPanel",extends:p,props:{value:{type:[String,Number],default:void 0},as:{type:[String,Object],default:"DIV"},asChild:{type:Boolean,default:!1},header:null,headerStyle:null,headerClass:null,headerProps:null,headerActionProps:null,contentStyle:null,contentClass:null,contentProps:null,disabled:Boolean},style:It,provide:function(){return{$pcTabPanel:this,$parentInstance:this}}},Vt={name:"TabPanel",extends:Nt,inheritAttrs:!1,inject:["$pcTabs"],computed:{active:function(){var e;return V((e=this.$pcTabs)===null||e===void 0?void 0:e.d_value,this.value)},id:function(){var e;return"".concat((e=this.$pcTabs)===null||e===void 0?void 0:e.$id,"_tabpanel_").concat(this.value)},ariaLabelledby:function(){var e;return"".concat((e=this.$pcTabs)===null||e===void 0?void 0:e.$id,"_tab_").concat(this.value)},attrs:function(){return s(this.a11yAttrs,this.ptmi("root",this.ptParams))},a11yAttrs:function(){var e;return{id:this.id,tabindex:(e=this.$pcTabs)===null||e===void 0?void 0:e.tabindex,role:"tabpanel","aria-labelledby":this.ariaLabelledby,"data-pc-name":"tabpanel","data-p-active":this.active}},ptParams:function(){return{context:{active:this.active}}}}};function Kt(t,e,a,r,i,n){var o,c;return n.$pcTabs?(l(),d(S,{key:1},[t.asChild?u(t.$slots,"default",{key:1,class:_(t.cx("root")),active:n.active,a11yAttrs:n.a11yAttrs}):(l(),d(S,{key:0},[!((o=n.$pcTabs)!==null&&o!==void 0&&o.lazy)||n.active?y((l(),m($(t.as),s({key:0,class:t.cx("root")},n.attrs),{default:K(function(){return[u(t.$slots,"default")]}),_:3},16,["class"])),[[U,(c=n.$pcTabs)!==null&&c!==void 0&&c.lazy?!0:n.active]]):x("",!0)],64))],64)):u(t.$slots,"default",{key:0})}Vt.render=Kt;export{pt as a,xt as b,Lt as c,Vt as d,q as s};
