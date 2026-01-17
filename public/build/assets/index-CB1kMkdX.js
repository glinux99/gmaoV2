import{U as A,R as E,a as S}from"./index-CiHAvnIS.js";import{B as K,D as c,av as v,a5 as P,Q as B,H as $,aV as I,S as L,o as l,l as u,j as f,q as h,E as m,e as g,V as y,p,m as w,n as x,t as O,ac as D,O as V,f as j,s as N}from"./app-tNqL7mf1.js";import{s as F}from"./index-BbQv9taT.js";import{a as z}from"./index-XYa5dVaH.js";var R=function(e){var n=e.dt;return`
.p-tabview-tablist-container {
    position: relative;
}

.p-tabview-scrollable > .p-tabview-tablist-container {
    overflow: hidden;
}

.p-tabview-tablist-scroll-container {
    overflow-x: auto;
    overflow-y: hidden;
    scroll-behavior: smooth;
    scrollbar-width: none;
    overscroll-behavior: contain auto;
}

.p-tabview-tablist-scroll-container::-webkit-scrollbar {
    display: none;
}

.p-tabview-tablist {
    display: flex;
    margin: 0;
    padding: 0;
    list-style-type: none;
    flex: 1 1 auto;
    background: `.concat(n("tabview.tab.list.background"),`;
    border: 1px solid `).concat(n("tabview.tab.list.border.color"),`;
    border-width: 0 0 1px 0;
    position: relative;
}

.p-tabview-tab-header {
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    text-decoration: none;
    position: relative;
    overflow: hidden;
    border-style: solid;
    border-width: 0 0 1px 0;
    border-color: transparent transparent `).concat(n("tabview.tab.border.color"),` transparent;
    color: `).concat(n("tabview.tab.color"),`;
    padding: 1rem 1.125rem;
    font-weight: 600;
    border-top-right-radius: `).concat(n("border.radius.md"),`;
    border-top-left-radius: `).concat(n("border.radius.md"),`;
    transition: color `).concat(n("tabview.transition.duration"),", outline-color ").concat(n("tabview.transition.duration"),`;
    margin: 0 0 -1px 0;
    outline-color: transparent;
}

.p-tabview-tablist-item:not(.p-disabled) .p-tabview-tab-header:focus-visible {
    outline: `).concat(n("focus.ring.width")," ").concat(n("focus.ring.style")," ").concat(n("focus.ring.color"),`;
    outline-offset: -1px;
}

.p-tabview-tablist-item:not(.p-highlight):not(.p-disabled):hover > .p-tabview-tab-header {
    color: `).concat(n("tabview.tab.hover.color"),`;
}

.p-tabview-tablist-item.p-highlight > .p-tabview-tab-header {
    color: `).concat(n("tabview.tab.active.color"),`;
}

.p-tabview-tab-title {
    line-height: 1;
    white-space: nowrap;
}

.p-tabview-next-button,
.p-tabview-prev-button {
    position: absolute;
    top: 0;
    margin: 0;
    padding: 0;
    z-index: 2;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: `).concat(n("tabview.nav.button.background"),`;
    color: `).concat(n("tabview.nav.button.color"),`;
    width: 2.5rem;
    border-radius: 0;
    outline-color: transparent;
    transition: color `).concat(n("tabview.transition.duration"),", outline-color ").concat(n("tabview.transition.duration"),`;
    box-shadow: `).concat(n("tabview.nav.button.shadow"),`;
    border: none;
    cursor: pointer;
    user-select: none;
}

.p-tabview-next-button:focus-visible,
.p-tabview-prev-button:focus-visible {
    outline: `).concat(n("focus.ring.width")," ").concat(n("focus.ring.style")," ").concat(n("focus.ring.color"),`;
    outline-offset: `).concat(n("focus.ring.offset"),`;
}

.p-tabview-next-button:hover,
.p-tabview-prev-button:hover {
    color: `).concat(n("tabview.nav.button.hover.color"),`;
}

.p-tabview-prev-button {
    left: 0;
}

.p-tabview-next-button {
    right: 0;
}

.p-tabview-panels {
    background: `).concat(n("tabview.tab.panel.background"),`;
    color: `).concat(n("tabview.tab.panel.color"),`;
    padding: 0.875rem 1.125rem 1.125rem 1.125rem;
}

.p-tabview-ink-bar {
    z-index: 1;
    display: block;
    position: absolute;
    bottom: -1px;
    height: 1px;
    background: `).concat(n("tabview.tab.active.border.color"),`;
    transition: 250ms cubic-bezier(0.35, 0, 0.25, 1);
}
`)},U={root:function(e){var n=e.props;return["p-tabview p-component",{"p-tabview-scrollable":n.scrollable}]},navContainer:"p-tabview-tablist-container",prevButton:"p-tabview-prev-button",navContent:"p-tabview-tablist-scroll-container",nav:"p-tabview-tablist",tab:{header:function(e){var n=e.instance,a=e.tab,o=e.index;return["p-tabview-tablist-item",n.getTabProp(a,"headerClass"),{"p-tabview-tablist-item-active":n.d_activeIndex===o,"p-disabled":n.getTabProp(a,"disabled")}]},headerAction:"p-tabview-tab-header",headerTitle:"p-tabview-tab-title",content:function(e){var n=e.instance,a=e.tab;return["p-tabview-panel",n.getTabProp(a,"contentClass")]}},inkbar:"p-tabview-ink-bar",nextButton:"p-tabview-next-button",panelContainer:"p-tabview-panels"},W=K.extend({name:"tabview",theme:R,classes:U}),q={name:"BaseTabView",extends:S,props:{activeIndex:{type:Number,default:0},lazy:{type:Boolean,default:!1},scrollable:{type:Boolean,default:!1},tabindex:{type:Number,default:0},selectOnFocus:{type:Boolean,default:!1},prevButtonProps:{type:null,default:null},nextButtonProps:{type:null,default:null},prevIcon:{type:String,default:void 0},nextIcon:{type:String,default:void 0}},style:W,provide:function(){return{$pcTabs:void 0,$pcTabView:this,$parentInstance:this}}},M={name:"TabView",extends:q,inheritAttrs:!1,emits:["update:activeIndex","tab-change","tab-click"],data:function(){return{id:this.$attrs.id,d_activeIndex:this.activeIndex,isPrevButtonDisabled:!0,isNextButtonDisabled:!1}},watch:{"$attrs.id":function(e){this.id=e||A()},activeIndex:function(e){this.d_activeIndex=e,this.scrollInView({index:e})}},mounted:function(){console.warn("Deprecated since v4. Use Tabs component instead."),this.id=this.id||A(),this.updateInkBar(),this.scrollable&&this.updateButtonState()},updated:function(){this.updateInkBar(),this.scrollable&&this.updateButtonState()},methods:{isTabPanel:function(e){return e.type.name==="TabPanel"},isTabActive:function(e){return this.d_activeIndex===e},getTabProp:function(e,n){return e.props?e.props[n]:void 0},getKey:function(e,n){return this.getTabProp(e,"header")||n},getTabHeaderActionId:function(e){return"".concat(this.id,"_").concat(e,"_header_action")},getTabContentId:function(e){return"".concat(this.id,"_").concat(e,"_content")},getTabPT:function(e,n,a){var o=this.tabs.length,r={props:e.props,parent:{instance:this,props:this.$props,state:this.$data},context:{index:a,count:o,first:a===0,last:a===o-1,active:this.isTabActive(a)}};return c(this.ptm("tabpanel.".concat(n),{tabpanel:r}),this.ptm("tabpanel.".concat(n),r),this.ptmo(this.getTabProp(e,"pt"),n,r))},onScroll:function(e){this.scrollable&&this.updateButtonState(),e.preventDefault()},onPrevButtonClick:function(){var e=this.$refs.content,n=v(e),a=e.scrollLeft-n;e.scrollLeft=a<=0?0:a},onNextButtonClick:function(){var e=this.$refs.content,n=v(e)-this.getVisibleButtonWidths(),a=e.scrollLeft+n,o=e.scrollWidth-n;e.scrollLeft=a>=o?o:a},onTabClick:function(e,n,a){this.changeActiveIndex(e,n,a),this.$emit("tab-click",{originalEvent:e,index:a})},onTabKeyDown:function(e,n,a){switch(e.code){case"ArrowLeft":this.onTabArrowLeftKey(e);break;case"ArrowRight":this.onTabArrowRightKey(e);break;case"Home":this.onTabHomeKey(e);break;case"End":this.onTabEndKey(e);break;case"PageDown":this.onPageDownKey(e);break;case"PageUp":this.onPageUpKey(e);break;case"Enter":case"NumpadEnter":case"Space":this.onTabEnterKey(e,n,a);break}},onTabArrowRightKey:function(e){var n=this.findNextHeaderAction(e.target.parentElement);n?this.changeFocusedTab(e,n):this.onTabHomeKey(e),e.preventDefault()},onTabArrowLeftKey:function(e){var n=this.findPrevHeaderAction(e.target.parentElement);n?this.changeFocusedTab(e,n):this.onTabEndKey(e),e.preventDefault()},onTabHomeKey:function(e){var n=this.findFirstHeaderAction();this.changeFocusedTab(e,n),e.preventDefault()},onTabEndKey:function(e){var n=this.findLastHeaderAction();this.changeFocusedTab(e,n),e.preventDefault()},onPageDownKey:function(e){this.scrollInView({index:this.$refs.nav.children.length-2}),e.preventDefault()},onPageUpKey:function(e){this.scrollInView({index:0}),e.preventDefault()},onTabEnterKey:function(e,n,a){this.changeActiveIndex(e,n,a),e.preventDefault()},findNextHeaderAction:function(e){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,a=n?e:e.nextElementSibling;return a?P(a,"data-p-disabled")||P(a,"data-pc-section")==="inkbar"?this.findNextHeaderAction(a):B(a,'[data-pc-section="headeraction"]'):null},findPrevHeaderAction:function(e){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,a=n?e:e.previousElementSibling;return a?P(a,"data-p-disabled")||P(a,"data-pc-section")==="inkbar"?this.findPrevHeaderAction(a):B(a,'[data-pc-section="headeraction"]'):null},findFirstHeaderAction:function(){return this.findNextHeaderAction(this.$refs.nav.firstElementChild,!0)},findLastHeaderAction:function(){return this.findPrevHeaderAction(this.$refs.nav.lastElementChild,!0)},changeActiveIndex:function(e,n,a){!this.getTabProp(n,"disabled")&&this.d_activeIndex!==a&&(this.d_activeIndex=a,this.$emit("update:activeIndex",a),this.$emit("tab-change",{originalEvent:e,index:a}),this.scrollInView({index:a}))},changeFocusedTab:function(e,n){if(n&&($(n),this.scrollInView({element:n}),this.selectOnFocus)){var a=parseInt(n.parentElement.dataset.pcIndex,10),o=this.tabs[a];this.changeActiveIndex(e,o,a)}},scrollInView:function(e){var n=e.element,a=e.index,o=a===void 0?-1:a,r=n||this.$refs.nav.children[o];r&&r.scrollIntoView&&r.scrollIntoView({block:"nearest"})},updateInkBar:function(){var e=this.$refs.nav.children[this.d_activeIndex];this.$refs.inkbar.style.width=v(e)+"px",this.$refs.inkbar.style.left=I(e).left-I(this.$refs.nav).left+"px"},updateButtonState:function(){var e=this.$refs.content,n=e.scrollLeft,a=e.scrollWidth,o=v(e);this.isPrevButtonDisabled=n===0,this.isNextButtonDisabled=parseInt(n)===a-o},getVisibleButtonWidths:function(){var e=this.$refs,n=e.prevBtn,a=e.nextBtn;return[n,a].reduce(function(o,r){return r?o+v(r):o},0)}},computed:{tabs:function(){var e=this;return this.$slots.default().reduce(function(n,a){return e.isTabPanel(a)?n.push(a):a.children&&a.children instanceof Array&&a.children.forEach(function(o){e.isTabPanel(o)&&n.push(o)}),n},[])},prevButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.previous:void 0},nextButtonAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.next:void 0}},directives:{ripple:E},components:{ChevronLeftIcon:F,ChevronRightIcon:z}};function T(t){"@babel/helpers - typeof";return T=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},T(t)}function C(t,e){var n=Object.keys(t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(t);e&&(a=a.filter(function(o){return Object.getOwnPropertyDescriptor(t,o).enumerable})),n.push.apply(n,a)}return n}function d(t){for(var e=1;e<arguments.length;e++){var n=arguments[e]!=null?arguments[e]:{};e%2?C(Object(n),!0).forEach(function(a){Q(t,a,n[a])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(n)):C(Object(n)).forEach(function(a){Object.defineProperty(t,a,Object.getOwnPropertyDescriptor(n,a))})}return t}function Q(t,e,n){return(e=G(e))in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}function G(t){var e=J(t,"string");return T(e)=="symbol"?e:e+""}function J(t,e){if(T(t)!="object"||!t)return t;var n=t[Symbol.toPrimitive];if(n!==void 0){var a=n.call(t,e||"default");if(T(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var X=["tabindex","aria-label"],Y=["data-p-active","data-p-disabled","data-pc-index"],Z=["id","tabindex","aria-disabled","aria-selected","aria-controls","onClick","onKeydown"],_=["tabindex","aria-label"],ee=["id","aria-labelledby","data-pc-index","data-p-active"];function te(t,e,n,a,o,r){var b=L("ripple");return l(),u("div",c({class:t.cx("root"),role:"tablist"},t.ptmi("root")),[f("div",c({class:t.cx("navContainer")},t.ptm("navContainer")),[t.scrollable&&!o.isPrevButtonDisabled?h((l(),u("button",c({key:0,ref:"prevBtn",type:"button",class:t.cx("prevButton"),tabindex:t.tabindex,"aria-label":r.prevButtonAriaLabel,onClick:e[0]||(e[0]=function(){return r.onPrevButtonClick&&r.onPrevButtonClick.apply(r,arguments)})},d(d({},t.prevButtonProps),t.ptm("prevButton")),{"data-pc-group-section":"navbutton"}),[m(t.$slots,"previcon",{},function(){return[(l(),g(y(t.prevIcon?"span":"ChevronLeftIcon"),c({"aria-hidden":"true",class:t.prevIcon},t.ptm("prevIcon")),null,16,["class"]))]})],16,X)),[[b]]):p("",!0),f("div",c({ref:"content",class:t.cx("navContent"),onScroll:e[1]||(e[1]=function(){return r.onScroll&&r.onScroll.apply(r,arguments)})},t.ptm("navContent")),[f("ul",c({ref:"nav",class:t.cx("nav")},t.ptm("nav")),[(l(!0),u(w,null,x(r.tabs,function(i,s){return l(),u("li",c({key:r.getKey(i,s),style:r.getTabProp(i,"headerStyle"),class:t.cx("tab.header",{tab:i,index:s}),role:"presentation",ref_for:!0},d(d(d({},r.getTabProp(i,"headerProps")),r.getTabPT(i,"root",s)),r.getTabPT(i,"header",s)),{"data-pc-name":"tabpanel","data-p-active":o.d_activeIndex===s,"data-p-disabled":r.getTabProp(i,"disabled"),"data-pc-index":s}),[h((l(),u("a",c({id:r.getTabHeaderActionId(s),class:t.cx("tab.headerAction"),tabindex:r.getTabProp(i,"disabled")||!r.isTabActive(s)?-1:t.tabindex,role:"tab","aria-disabled":r.getTabProp(i,"disabled"),"aria-selected":r.isTabActive(s),"aria-controls":r.getTabContentId(s),onClick:function(k){return r.onTabClick(k,i,s)},onKeydown:function(k){return r.onTabKeyDown(k,i,s)},ref_for:!0},d(d({},r.getTabProp(i,"headerActionProps")),r.getTabPT(i,"headerAction",s))),[i.props&&i.props.header?(l(),u("span",c({key:0,class:t.cx("tab.headerTitle"),ref_for:!0},r.getTabPT(i,"headerTitle",s)),O(i.props.header),17)):p("",!0),i.children&&i.children.header?(l(),g(y(i.children.header),{key:1})):p("",!0)],16,Z)),[[b]])],16,Y)}),128)),f("li",c({ref:"inkbar",class:t.cx("inkbar"),role:"presentation","aria-hidden":"true"},t.ptm("inkbar")),null,16)],16)],16),t.scrollable&&!o.isNextButtonDisabled?h((l(),u("button",c({key:1,ref:"nextBtn",type:"button",class:t.cx("nextButton"),tabindex:t.tabindex,"aria-label":r.nextButtonAriaLabel,onClick:e[2]||(e[2]=function(){return r.onNextButtonClick&&r.onNextButtonClick.apply(r,arguments)})},d(d({},t.nextButtonProps),t.ptm("nextButton")),{"data-pc-group-section":"navbutton"}),[m(t.$slots,"nexticon",{},function(){return[(l(),g(y(t.nextIcon?"span":"ChevronRightIcon"),c({"aria-hidden":"true",class:t.nextIcon},t.ptm("nextIcon")),null,16,["class"]))]})],16,_)),[[b]]):p("",!0)],16),f("div",c({class:t.cx("panelContainer")},t.ptm("panelContainer")),[(l(!0),u(w,null,x(r.tabs,function(i,s){return l(),u(w,{key:r.getKey(i,s)},[!t.lazy||r.isTabActive(s)?h((l(),u("div",c({key:0,id:r.getTabContentId(s),style:r.getTabProp(i,"contentStyle"),class:t.cx("tab.content",{tab:i}),role:"tabpanel","aria-labelledby":r.getTabHeaderActionId(s),ref_for:!0},d(d(d({},r.getTabProp(i,"contentProps")),r.getTabPT(i,"root",s)),r.getTabPT(i,"content",s)),{"data-pc-name":"tabpanel","data-pc-index":s,"data-p-active":o.d_activeIndex===s}),[(l(),g(y(i)))],16,ee)),[[D,t.lazy?!0:r.isTabActive(s)]]):p("",!0)],64)}),128))],16)],16)}M.render=te;var ne={root:function(e){var n=e.instance;return["p-tabpanel",{"p-tabpanel-active":n.active}]}},ae=K.extend({name:"tabpanel",classes:ne}),re={name:"BaseTabPanel",extends:S,props:{value:{type:String,default:void 0},as:{type:String,default:"DIV"},asChild:{type:Boolean,default:!1},header:null,headerStyle:null,headerClass:null,headerProps:null,headerActionProps:null,contentStyle:null,contentClass:null,contentProps:null,disabled:Boolean},style:ae,provide:function(){return{$pcTabPanel:this,$parentInstance:this}}},ie={name:"TabPanel",extends:re,inheritAttrs:!1,inject:["$pcTabs"],computed:{active:function(){var e;return V((e=this.$pcTabs)===null||e===void 0?void 0:e.d_value,this.value)},id:function(){var e;return"".concat((e=this.$pcTabs)===null||e===void 0?void 0:e.id,"_tabpanel_").concat(this.value)},ariaLabelledby:function(){var e;return"".concat((e=this.$pcTabs)===null||e===void 0?void 0:e.id,"_tab_").concat(this.value)},attrs:function(){return c(this.a11yAttrs,this.ptmi("root",this.ptParams))},a11yAttrs:function(){var e;return{id:this.id,tabindex:(e=this.$pcTabs)===null||e===void 0?void 0:e.tabindex,role:"tabpanel","aria-labelledby":this.ariaLabelledby,"data-pc-name":"tabpanel","data-p-active":this.active}},ptParams:function(){return{context:{active:this.active}}}}};function oe(t,e,n,a,o,r){var b,i;return r.$pcTabs?(l(),u(w,{key:1},[t.asChild?m(t.$slots,"default",{key:1,class:N(t.cx("root")),active:r.active,a11yAttrs:r.a11yAttrs}):(l(),u(w,{key:0},[!((b=r.$pcTabs)!==null&&b!==void 0&&b.lazy)||r.active?h((l(),g(y(t.as),c({key:0,class:t.cx("root")},r.attrs),{default:j(function(){return[m(t.$slots,"default")]}),_:3},16,["class"])),[[D,(i=r.$pcTabs)!==null&&i!==void 0&&i.lazy?!0:r.active]]):p("",!0)],64))],64)):m(t.$slots,"default",{key:0})}ie.render=oe;export{M as a,ie as s};
