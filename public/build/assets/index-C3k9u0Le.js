import{B as X,o as r,l as c,E as h,D as s,e as v,X as C,p as f,t as O,C as M,I as V,ay as re,az as ae,aA as ce,P as H,K as ue,L as de,M as pe,N as he,O as fe,aq as me,H as x,R as B,S as be,aB as ge,J as ye,U as I,V as ve,j as y,m as T,k as A,n as N,i as F,f as S,s as k,Y as Oe,aC as Ie,_ as Se,q as ke}from"./app-qY9iQY0c.js";import{Z as U,s as we,a as Le,b as Ve}from"./index-ZmKqUJz2.js";import{s as Y,U as q,C as xe,R as Fe,a as Ce}from"./index-DEQ-YT_x.js";import{s as Ke}from"./index-l1gA3O77.js";import{b as Me,a as Te,s as Ae,e as Ee,c as De}from"./index-Dx--1kPn.js";import{s as Re}from"./index-hH9LBHjg.js";import{b as Pe}from"./index-CVfOes_D.js";import{O as He}from"./index-CaIWOFSS.js";var Be=function(e){var i=e.dt;return`
.p-chip {
    display: inline-flex;
    align-items: center;
    background: `.concat(i("chip.background"),`;
    color: `).concat(i("chip.color"),`;
    border-radius: `).concat(i("chip.border.radius"),`;
    padding: `).concat(i("chip.padding.y")," ").concat(i("chip.padding.x"),`;
    gap: `).concat(i("chip.gap"),`;
}

.p-chip-icon {
    color: `).concat(i("chip.icon.color"),`;
    font-size: `).concat(i("chip.icon.font.size"),`;
    width: `).concat(i("chip.icon.size"),`;
    height: `).concat(i("chip.icon.size"),`;
}

.p-chip-image {
    border-radius: 50%;
    width: `).concat(i("chip.image.width"),`;
    height: `).concat(i("chip.image.height"),`;
    margin-left: calc(-1 * `).concat(i("chip.padding.y"),`);
}

.p-chip:has(.p-chip-remove-icon) {
    padding-right: `).concat(i("chip.padding.y"),`;
}

.p-chip:has(.p-chip-image) {
    padding-top: calc(`).concat(i("chip.padding.y"),` / 2);
    padding-bottom: calc(`).concat(i("chip.padding.y"),` / 2);
}

.p-chip-remove-icon {
    cursor: pointer;
    font-size: `).concat(i("chip.remove.icon.size"),`;
    width: `).concat(i("chip.remove.icon.size"),`;
    height: `).concat(i("chip.remove.icon.size"),`;
    color: `).concat(i("chip.remove.icon.color"),`;
    border-radius: 50%;
    transition: outline-color `).concat(i("chip.transition.duration"),", box-shadow ").concat(i("chip.transition.duration"),`;
    outline-color: transparent;
}

.p-chip-remove-icon:focus-visible {
    box-shadow: `).concat(i("chip.remove.icon.focus.ring.shadow"),`;
    outline: `).concat(i("chip.remove.icon.focus.ring.width")," ").concat(i("chip.remove.icon.focus.ring.style")," ").concat(i("chip.remove.icon.focus.ring.color"),`;
    outline-offset: `).concat(i("chip.remove.icon.focus.ring.offset"),`;
}
`)},ze={root:"p-chip p-component",image:"p-chip-image",icon:"p-chip-icon",label:"p-chip-label",removeIcon:"p-chip-remove-icon"},Ge=X.extend({name:"chip",theme:Be,classes:ze}),Ue={name:"BaseChip",extends:Y,props:{label:{type:String,default:null},icon:{type:String,default:null},image:{type:String,default:null},removable:{type:Boolean,default:!1},removeIcon:{type:String,default:void 0}},style:Ge,provide:function(){return{$pcChip:this,$parentInstance:this}}},Q={name:"Chip",extends:Ue,inheritAttrs:!1,emits:["remove"],data:function(){return{visible:!0}},methods:{onKeydown:function(e){(e.key==="Enter"||e.key==="Backspace")&&this.close(e)},close:function(e){this.visible=!1,this.$emit("remove",e)}},components:{TimesCircleIcon:Pe}},je=["aria-label"],Ne=["src"];function qe(t,e,i,n,o,l){return o.visible?(r(),c("div",s({key:0,class:t.cx("root"),"aria-label":t.label},t.ptmi("root")),[h(t.$slots,"default",{},function(){return[t.image?(r(),c("img",s({key:0,src:t.image},t.ptm("image"),{class:t.cx("image")}),null,16,Ne)):t.$slots.icon?(r(),v(C(t.$slots.icon),s({key:1,class:t.cx("icon")},t.ptm("icon")),null,16,["class"])):t.icon?(r(),c("span",s({key:2,class:[t.cx("icon"),t.icon]},t.ptm("icon")),null,16)):f("",!0),t.label?(r(),c("div",s({key:3,class:t.cx("label")},t.ptm("label")),O(t.label),17)):f("",!0)]}),t.removable?h(t.$slots,"removeicon",{key:0,removeCallback:l.close,keydownCallback:l.onKeydown},function(){return[(r(),v(C(t.removeIcon?"span":"TimesCircleIcon"),s({tabindex:"0",class:[t.cx("removeIcon"),t.removeIcon],onClick:l.close,onKeydown:l.onKeydown},t.ptm("removeIcon")),null,16,["class","onClick","onKeydown"]))]}):f("",!0)],16,je)):f("",!0)}Q.render=qe;var $e=function(e){var i=e.dt;return`
.p-multiselect {
    display: inline-flex;
    cursor: pointer;
    position: relative;
    user-select: none;
    background: `.concat(i("multiselect.background"),`;
    border: 1px solid `).concat(i("multiselect.border.color"),`;
    transition: background `).concat(i("multiselect.transition.duration"),", color ").concat(i("multiselect.transition.duration"),", border-color ").concat(i("multiselect.transition.duration"),", outline-color ").concat(i("multiselect.transition.duration"),", box-shadow ").concat(i("multiselect.transition.duration"),`;
    border-radius: `).concat(i("multiselect.border.radius"),`;
    outline-color: transparent;
    box-shadow: `).concat(i("multiselect.shadow"),`;
}

.p-multiselect:not(.p-disabled):hover {
    border-color: `).concat(i("multiselect.hover.border.color"),`;
}

.p-multiselect:not(.p-disabled).p-focus {
    border-color: `).concat(i("multiselect.focus.border.color"),`;
    box-shadow: `).concat(i("multiselect.focus.ring.shadow"),`;
    outline: `).concat(i("multiselect.focus.ring.width")," ").concat(i("multiselect.focus.ring.style")," ").concat(i("multiselect.focus.ring.color"),`;
    outline-offset: `).concat(i("multiselect.focus.ring.offset"),`;
}

.p-multiselect.p-variant-filled {
    background: `).concat(i("multiselect.filled.background"),`;
}

.p-multiselect.p-variant-filled.p-focus {
    background: `).concat(i("multiselect.filled.focus.background"),`;
}

.p-multiselect.p-invalid {
    border-color: `).concat(i("multiselect.invalid.border.color"),`;
}

.p-multiselect.p-disabled {
    opacity: 1;
    background: `).concat(i("multiselect.disabled.background"),`;
}

.p-multiselect-dropdown {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: transparent;
    color: `).concat(i("multiselect.dropdown.color"),`;
    width: `).concat(i("multiselect.dropdown.width"),`;
    border-top-right-radius: `).concat(i("multiselect.border.radius"),`;
    border-bottom-right-radius: `).concat(i("multiselect.border.radius"),`;
}

.p-multiselect-label-container {
    overflow: hidden;
    flex: 1 1 auto;
    cursor: pointer;
}

.p-multiselect-label {
    display: flex;
    align-items-center;
    gap: calc(`).concat(i("multiselect.padding.y"),` / 2);
    white-space: nowrap;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: `).concat(i("multiselect.padding.y")," ").concat(i("multiselect.padding.x"),`;
    color: `).concat(i("multiselect.color"),`;
}

.p-multiselect-label.p-placeholder {
    color: `).concat(i("multiselect.placeholder.color"),`;
}

.p-multiselect.p-disabled .p-multiselect-label {
    color: `).concat(i("multiselect.disabled.color"),`;
}

.p-multiselect-label-empty {
    overflow: hidden;
    visibility: hidden;
}

.p-multiselect .p-multiselect-overlay {
    min-width: 100%;
}

.p-multiselect-overlay {
    position: absolute;
    top: 0;
    left: 0;
    background: `).concat(i("multiselect.overlay.background"),`;
    color: `).concat(i("multiselect.overlay.color"),`;
    border: 1px solid `).concat(i("multiselect.overlay.border.color"),`;
    border-radius: `).concat(i("multiselect.overlay.border.radius"),`;
    box-shadow: `).concat(i("multiselect.overlay.shadow"),`;
}

.p-multiselect-header {
    display: flex;
    align-items: center;
    padding: `).concat(i("multiselect.list.header.padding"),`;
}

.p-multiselect-header .p-checkbox {
    margin-right: `).concat(i("multiselect.option.gap"),`;
}

.p-multiselect-filter-container {
    flex: 1 1 auto;
}

.p-multiselect-filter {
    width: 100%;
}

.p-multiselect-list-container {
    overflow: auto;
}

.p-multiselect-list {
    margin: 0;
    padding: 0;
    list-style-type: none;
    padding: `).concat(i("multiselect.list.padding"),`;
    display: flex;
    flex-direction: column;
    gap: `).concat(i("multiselect.list.gap"),`
}

.p-multiselect-option {
    cursor: pointer;
    font-weight: normal;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: `).concat(i("multiselect.option.gap"),`;
    padding: `).concat(i("multiselect.option.padding"),`;
    border: 0 none;
    color: `).concat(i("multiselect.option.color"),`;
    background: transparent;
    transition: background `).concat(i("multiselect.transition.duration"),", color ").concat(i("multiselect.transition.duration"),", border-color ").concat(i("multiselect.transition.duration"),", box-shadow ").concat(i("multiselect.transition.duration"),", outline-color ").concat(i("multiselect.transition.duration"),`;
    border-radius: `).concat(i("multiselect.option.border.radius"),`
}

.p-multiselect-option:not(.p-multiselect-option-selected):not(.p-disabled).p-focus {
    background: `).concat(i("multiselect.option.focus.background"),`;
    color: `).concat(i("multiselect.option.focus.color"),`;
}

.p-multiselect-option.p-multiselect-option-selected {
    background: `).concat(i("multiselect.option.selected.background"),`;
    color: `).concat(i("multiselect.option.selected.color"),`;
}

.p-multiselect-option.p-multiselect-option-selected.p-focus {
    background: `).concat(i("multiselect.option.selected.focus.background"),`;
    color: `).concat(i("multiselect.option.selected.focus.color"),`;
}

.p-multiselect-option-group {
    cursor: auto;
    margin: 0;
    padding: `).concat(i("multiselect.option.group.padding"),`;
    background: `).concat(i("multiselect.option.group.background"),`;
    color: `).concat(i("multiselect.option.group.color"),`;
    font-weight: `).concat(i("multiselect.option.group.font.weight"),`;
}

.p-multiselect-empty-message {
    padding: `).concat(i("multiselect.empty.message.padding"),`;
}

.p-multiselect-label .p-chip {
    padding-top: calc(`).concat(i("multiselect.padding.y"),` / 2);
    padding-bottom: calc(`).concat(i("multiselect.padding.y"),` / 2);
    border-radius: `).concat(i("multiselect.chip.border.radius"),`;
}

.p-multiselect-label:has(.p-chip) {
    padding: calc(`).concat(i("multiselect.padding.y")," / 2) calc(").concat(i("multiselect.padding.x"),` / 2);
}

.p-multiselect-fluid {
    display: flex;
}
`)},We={root:function(e){var i=e.props;return{position:i.appendTo==="self"?"relative":void 0}}},Ze={root:function(e){var i=e.instance,n=e.props;return["p-multiselect p-component p-inputwrapper",{"p-multiselect-display-chip":n.display==="chip","p-disabled":n.disabled,"p-invalid":n.invalid,"p-variant-filled":n.variant?n.variant==="filled":i.$primevue.config.inputStyle==="filled"||i.$primevue.config.inputVariant==="filled","p-focus":i.focused,"p-inputwrapper-filled":n.modelValue&&n.modelValue.length,"p-inputwrapper-focus":i.focused||i.overlayVisible,"p-multiselect-open":i.overlayVisible,"p-multiselect-fluid":i.hasFluid}]},labelContainer:"p-multiselect-label-container",label:function(e){var i=e.instance,n=e.props;return["p-multiselect-label",{"p-placeholder":i.label===n.placeholder,"p-multiselect-label-empty":!n.placeholder&&(!n.modelValue||n.modelValue.length===0)}]},chipItem:"p-multiselect-chip-item",pcChip:"p-multiselect-chip",chipIcon:"p-multiselect-chip-icon",dropdown:"p-multiselect-dropdown",loadingIcon:"p-multiselect-loading-icon",dropdownIcon:"p-multiselect-dropdown-icon",overlay:"p-multiselect-overlay p-component",header:"p-multiselect-header",pcFilterContainer:"p-multiselect-filter-container",pcFilter:"p-multiselect-filter",listContainer:"p-multiselect-list-container",list:"p-multiselect-list",optionGroup:"p-multiselect-option-group",option:function(e){var i=e.instance,n=e.option,o=e.index,l=e.getItemOptions,d=e.props;return["p-multiselect-option",{"p-multiselect-option-selected":i.isSelected(n)&&d.highlightOnSelect,"p-focus":i.focusedOptionIndex===i.getOptionIndex(o,l),"p-disabled":i.isOptionDisabled(n)}]},emptyMessage:"p-multiselect-empty-message"},Je=X.extend({name:"multiselect",theme:$e,classes:Ze,inlineStyles:We}),Xe={name:"BaseMultiSelect",extends:Y,props:{modelValue:null,options:Array,optionLabel:null,optionValue:null,optionDisabled:null,optionGroupLabel:null,optionGroupChildren:null,scrollHeight:{type:String,default:"14rem"},placeholder:String,variant:{type:String,default:null},invalid:{type:Boolean,default:!1},disabled:{type:Boolean,default:!1},fluid:{type:Boolean,default:null},inputId:{type:String,default:null},panelClass:{type:String,default:null},panelStyle:{type:null,default:null},overlayClass:{type:String,default:null},overlayStyle:{type:null,default:null},dataKey:null,filter:Boolean,filterPlaceholder:String,filterLocale:String,filterMatchMode:{type:String,default:"contains"},filterFields:{type:Array,default:null},appendTo:{type:[String,Object],default:"body"},display:{type:String,default:"comma"},selectedItemsLabel:{type:String,default:"{0} items selected"},maxSelectedLabels:{type:Number,default:null},selectionLimit:{type:Number,default:null},showToggleAll:{type:Boolean,default:!0},loading:{type:Boolean,default:!1},checkboxIcon:{type:String,default:void 0},closeIcon:{type:String,default:void 0},dropdownIcon:{type:String,default:void 0},filterIcon:{type:String,default:void 0},loadingIcon:{type:String,default:void 0},removeTokenIcon:{type:String,default:void 0},chipIcon:{type:String,default:void 0},selectAll:{type:Boolean,default:null},resetFilterOnHide:{type:Boolean,default:!1},virtualScrollerOptions:{type:Object,default:null},autoOptionFocus:{type:Boolean,default:!1},autoFilterFocus:{type:Boolean,default:!1},focusOnHover:{type:Boolean,default:!0},highlightOnSelect:{type:Boolean,default:!1},filterMessage:{type:String,default:null},selectionMessage:{type:String,default:null},emptySelectionMessage:{type:String,default:null},emptyFilterMessage:{type:String,default:null},emptyMessage:{type:String,default:null},tabindex:{type:Number,default:0},ariaLabel:{type:String,default:null},ariaLabelledby:{type:String,default:null}},style:Je,provide:function(){return{$pcMultiSelect:this,$parentInstance:this}}};function E(t){"@babel/helpers - typeof";return E=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},E(t)}function $(t,e){var i=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter(function(o){return Object.getOwnPropertyDescriptor(t,o).enumerable})),i.push.apply(i,n)}return i}function W(t){for(var e=1;e<arguments.length;e++){var i=arguments[e]!=null?arguments[e]:{};e%2?$(Object(i),!0).forEach(function(n){_(t,n,i[n])}):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(i)):$(Object(i)).forEach(function(n){Object.defineProperty(t,n,Object.getOwnPropertyDescriptor(i,n))})}return t}function _(t,e,i){return(e=Ye(e))in t?Object.defineProperty(t,e,{value:i,enumerable:!0,configurable:!0,writable:!0}):t[e]=i,t}function Ye(t){var e=Qe(t,"string");return E(e)=="symbol"?e:e+""}function Qe(t,e){if(E(t)!="object"||!t)return t;var i=t[Symbol.toPrimitive];if(i!==void 0){var n=i.call(t,e||"default");if(E(n)!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}function Z(t){return it(t)||tt(t)||et(t)||_e()}function _e(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function et(t,e){if(t){if(typeof t=="string")return j(t,e);var i={}.toString.call(t).slice(8,-1);return i==="Object"&&t.constructor&&(i=t.constructor.name),i==="Map"||i==="Set"?Array.from(t):i==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i)?j(t,e):void 0}}function tt(t){if(typeof Symbol<"u"&&t[Symbol.iterator]!=null||t["@@iterator"]!=null)return Array.from(t)}function it(t){if(Array.isArray(t))return j(t)}function j(t,e){(e==null||e>t.length)&&(e=t.length);for(var i=0,n=Array(e);i<e;i++)n[i]=t[i];return n}var nt={name:"MultiSelect",extends:Xe,inheritAttrs:!1,emits:["update:modelValue","change","focus","blur","before-show","before-hide","show","hide","filter","selectall-change"],inject:{$pcFluid:{default:null}},outsideClickListener:null,scrollHandler:null,resizeListener:null,overlay:null,list:null,virtualScroller:null,startRangeIndex:-1,searchTimeout:null,searchValue:"",selectOnFocus:!1,data:function(){return{id:this.$attrs.id,clicked:!1,focused:!1,focusedOptionIndex:-1,filterValue:null,overlayVisible:!1}},watch:{"$attrs.id":function(e){this.id=e||q()},options:function(){this.autoUpdateModel()}},mounted:function(){this.id=this.id||q(),this.autoUpdateModel()},beforeUnmount:function(){this.unbindOutsideClickListener(),this.unbindResizeListener(),this.scrollHandler&&(this.scrollHandler.destroy(),this.scrollHandler=null),this.overlay&&(U.clear(this.overlay),this.overlay=null)},methods:{getOptionIndex:function(e,i){return this.virtualScrollerDisabled?e:i&&i(e).index},getOptionLabel:function(e){return this.optionLabel?M(e,this.optionLabel):e},getOptionValue:function(e){return this.optionValue?M(e,this.optionValue):e},getOptionRenderKey:function(e,i){return this.dataKey?M(e,this.dataKey):this.getOptionLabel(e)+"_".concat(i)},getHeaderCheckboxPTOptions:function(e){return this.ptm(e,{context:{selected:this.allSelected}})},getCheckboxPTOptions:function(e,i,n,o){return this.ptm(o,{context:{selected:this.isSelected(e),focused:this.focusedOptionIndex===this.getOptionIndex(n,i),disabled:this.isOptionDisabled(e)}})},isOptionDisabled:function(e){return this.maxSelectionLimitReached&&!this.isSelected(e)?!0:this.optionDisabled?M(e,this.optionDisabled):!1},isOptionGroup:function(e){return this.optionGroupLabel&&e.optionGroup&&e.group},getOptionGroupLabel:function(e){return M(e,this.optionGroupLabel)},getOptionGroupChildren:function(e){return M(e,this.optionGroupChildren)},getAriaPosInset:function(e){var i=this;return(this.optionGroupLabel?e-this.visibleOptions.slice(0,e).filter(function(n){return i.isOptionGroup(n)}).length:e)+1},show:function(e){this.$emit("before-show"),this.overlayVisible=!0,this.focusedOptionIndex=this.focusedOptionIndex!==-1?this.focusedOptionIndex:this.autoOptionFocus?this.findFirstFocusedOptionIndex():this.findSelectedOptionIndex(),e&&V(this.$refs.focusInput)},hide:function(e){var i=this,n=function(){i.$emit("before-hide"),i.overlayVisible=!1,i.clicked=!1,i.focusedOptionIndex=-1,i.searchValue="",i.resetFilterOnHide&&(i.filterValue=null),e&&V(i.$refs.focusInput)};setTimeout(function(){n()},0)},onFocus:function(e){this.disabled||(this.focused=!0,this.overlayVisible&&(this.focusedOptionIndex=this.focusedOptionIndex!==-1?this.focusedOptionIndex:this.autoOptionFocus?this.findFirstFocusedOptionIndex():this.findSelectedOptionIndex(),this.scrollInView(this.focusedOptionIndex)),this.$emit("focus",e))},onBlur:function(e){this.clicked=!1,this.focused=!1,this.focusedOptionIndex=-1,this.searchValue="",this.$emit("blur",e)},onKeyDown:function(e){var i=this;if(this.disabled){e.preventDefault();return}var n=e.metaKey||e.ctrlKey;switch(e.code){case"ArrowDown":this.onArrowDownKey(e);break;case"ArrowUp":this.onArrowUpKey(e);break;case"Home":this.onHomeKey(e);break;case"End":this.onEndKey(e);break;case"PageDown":this.onPageDownKey(e);break;case"PageUp":this.onPageUpKey(e);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(e);break;case"Escape":this.onEscapeKey(e);break;case"Tab":this.onTabKey(e);break;case"ShiftLeft":case"ShiftRight":this.onShiftKey(e);break;default:if(e.code==="KeyA"&&n){var o=this.visibleOptions.filter(function(l){return i.isValidOption(l)}).map(function(l){return i.getOptionValue(l)});this.updateModel(e,o),e.preventDefault();break}!n&&re(e.key)&&(!this.overlayVisible&&this.show(),this.searchOptions(e),e.preventDefault());break}this.clicked=!1},onContainerClick:function(e){this.disabled||this.loading||((!this.overlay||!this.overlay.contains(e.target))&&(this.overlayVisible?this.hide(!0):this.show(!0)),this.clicked=!0)},onFirstHiddenFocus:function(e){var i=e.relatedTarget===this.$refs.focusInput?ae(this.overlay,':not([data-p-hidden-focusable="true"])'):this.$refs.focusInput;V(i)},onLastHiddenFocus:function(e){var i=e.relatedTarget===this.$refs.focusInput?ce(this.overlay,':not([data-p-hidden-focusable="true"])'):this.$refs.focusInput;V(i)},onOptionSelect:function(e,i){var n=this,o=arguments.length>2&&arguments[2]!==void 0?arguments[2]:-1,l=arguments.length>3&&arguments[3]!==void 0?arguments[3]:!1;if(!(this.disabled||this.isOptionDisabled(i))){var d=this.isSelected(i),m=null;d?m=this.modelValue.filter(function(b){return!H(b,n.getOptionValue(i),n.equalityKey)}):m=[].concat(Z(this.modelValue||[]),[this.getOptionValue(i)]),this.updateModel(e,m),o!==-1&&(this.focusedOptionIndex=o),l&&V(this.$refs.focusInput)}},onOptionMouseMove:function(e,i){this.focusOnHover&&this.changeFocusedOptionIndex(e,i)},onOptionSelectRange:function(e){var i=this,n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:-1,o=arguments.length>2&&arguments[2]!==void 0?arguments[2]:-1;if(n===-1&&(n=this.findNearestSelectedOptionIndex(o,!0)),o===-1&&(o=this.findNearestSelectedOptionIndex(n)),n!==-1&&o!==-1){var l=Math.min(n,o),d=Math.max(n,o),m=this.visibleOptions.slice(l,d+1).filter(function(b){return i.isValidOption(b)}).map(function(b){return i.getOptionValue(b)});this.updateModel(e,m)}},onFilterChange:function(e){var i=e.target.value;this.filterValue=i,this.focusedOptionIndex=-1,this.$emit("filter",{originalEvent:e,value:i}),!this.virtualScrollerDisabled&&this.virtualScroller.scrollToIndex(0)},onFilterKeyDown:function(e){switch(e.code){case"ArrowDown":this.onArrowDownKey(e);break;case"ArrowUp":this.onArrowUpKey(e,!0);break;case"ArrowLeft":case"ArrowRight":this.onArrowLeftKey(e,!0);break;case"Home":this.onHomeKey(e,!0);break;case"End":this.onEndKey(e,!0);break;case"Enter":case"NumpadEnter":this.onEnterKey(e);break;case"Escape":this.onEscapeKey(e);break;case"Tab":this.onTabKey(e,!0);break}},onFilterBlur:function(){this.focusedOptionIndex=-1},onFilterUpdated:function(){this.overlayVisible&&this.alignOverlay()},onOverlayClick:function(e){He.emit("overlay-click",{originalEvent:e,target:this.$el})},onOverlayKeyDown:function(e){switch(e.code){case"Escape":this.onEscapeKey(e);break}},onArrowDownKey:function(e){if(!this.overlayVisible)this.show();else{var i=this.focusedOptionIndex!==-1?this.findNextOptionIndex(this.focusedOptionIndex):this.clicked?this.findFirstOptionIndex():this.findFirstFocusedOptionIndex();e.shiftKey&&this.onOptionSelectRange(e,this.startRangeIndex,i),this.changeFocusedOptionIndex(e,i)}e.preventDefault()},onArrowUpKey:function(e){var i=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;if(e.altKey&&!i)this.focusedOptionIndex!==-1&&this.onOptionSelect(e,this.visibleOptions[this.focusedOptionIndex]),this.overlayVisible&&this.hide(),e.preventDefault();else{var n=this.focusedOptionIndex!==-1?this.findPrevOptionIndex(this.focusedOptionIndex):this.clicked?this.findLastOptionIndex():this.findLastFocusedOptionIndex();e.shiftKey&&this.onOptionSelectRange(e,n,this.startRangeIndex),this.changeFocusedOptionIndex(e,n),!this.overlayVisible&&this.show(),e.preventDefault()}},onArrowLeftKey:function(e){var i=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;i&&(this.focusedOptionIndex=-1)},onHomeKey:function(e){var i=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;if(i){var n=e.currentTarget;e.shiftKey?n.setSelectionRange(0,e.target.selectionStart):(n.setSelectionRange(0,0),this.focusedOptionIndex=-1)}else{var o=e.metaKey||e.ctrlKey,l=this.findFirstOptionIndex();e.shiftKey&&o&&this.onOptionSelectRange(e,l,this.startRangeIndex),this.changeFocusedOptionIndex(e,l),!this.overlayVisible&&this.show()}e.preventDefault()},onEndKey:function(e){var i=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;if(i){var n=e.currentTarget;if(e.shiftKey)n.setSelectionRange(e.target.selectionStart,n.value.length);else{var o=n.value.length;n.setSelectionRange(o,o),this.focusedOptionIndex=-1}}else{var l=e.metaKey||e.ctrlKey,d=this.findLastOptionIndex();e.shiftKey&&l&&this.onOptionSelectRange(e,this.startRangeIndex,d),this.changeFocusedOptionIndex(e,d),!this.overlayVisible&&this.show()}e.preventDefault()},onPageUpKey:function(e){this.scrollInView(0),e.preventDefault()},onPageDownKey:function(e){this.scrollInView(this.visibleOptions.length-1),e.preventDefault()},onEnterKey:function(e){this.overlayVisible?this.focusedOptionIndex!==-1&&(e.shiftKey?this.onOptionSelectRange(e,this.focusedOptionIndex):this.onOptionSelect(e,this.visibleOptions[this.focusedOptionIndex])):(this.focusedOptionIndex=-1,this.onArrowDownKey(e)),e.preventDefault()},onEscapeKey:function(e){this.overlayVisible&&this.hide(!0),e.preventDefault()},onTabKey:function(e){var i=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;i||(this.overlayVisible&&this.hasFocusableElements()?(V(e.shiftKey?this.$refs.lastHiddenFocusableElementOnOverlay:this.$refs.firstHiddenFocusableElementOnOverlay),e.preventDefault()):(this.focusedOptionIndex!==-1&&this.onOptionSelect(e,this.visibleOptions[this.focusedOptionIndex]),this.overlayVisible&&this.hide(this.filter)))},onShiftKey:function(){this.startRangeIndex=this.focusedOptionIndex},onOverlayEnter:function(e){U.set("overlay",e,this.$primevue.config.zIndex.overlay),ue(e,{position:"absolute",top:"0",left:"0"}),this.alignOverlay(),this.scrollInView(),this.autoFilterFocus&&V(this.$refs.filterInput.$el)},onOverlayAfterEnter:function(){this.bindOutsideClickListener(),this.bindScrollListener(),this.bindResizeListener(),this.$emit("show")},onOverlayLeave:function(){this.unbindOutsideClickListener(),this.unbindScrollListener(),this.unbindResizeListener(),this.$emit("hide"),this.overlay=null},onOverlayAfterLeave:function(e){U.clear(e)},alignOverlay:function(){this.appendTo==="self"?de(this.overlay,this.$el):(this.overlay.style.minWidth=pe(this.$el)+"px",he(this.overlay,this.$el))},bindOutsideClickListener:function(){var e=this;this.outsideClickListener||(this.outsideClickListener=function(i){e.overlayVisible&&e.isOutsideClicked(i)&&e.hide()},document.addEventListener("click",this.outsideClickListener))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener),this.outsideClickListener=null)},bindScrollListener:function(){var e=this;this.scrollHandler||(this.scrollHandler=new xe(this.$refs.container,function(){e.overlayVisible&&e.hide()})),this.scrollHandler.bindScrollListener()},unbindScrollListener:function(){this.scrollHandler&&this.scrollHandler.unbindScrollListener()},bindResizeListener:function(){var e=this;this.resizeListener||(this.resizeListener=function(){e.overlayVisible&&!fe()&&e.hide()},window.addEventListener("resize",this.resizeListener))},unbindResizeListener:function(){this.resizeListener&&(window.removeEventListener("resize",this.resizeListener),this.resizeListener=null)},isOutsideClicked:function(e){return!(this.$el.isSameNode(e.target)||this.$el.contains(e.target)||this.overlay&&this.overlay.contains(e.target))},getLabelByValue:function(e){var i=this,n=this.optionGroupLabel?this.flatOptions(this.options):this.options||[],o=n.find(function(l){return!i.isOptionGroup(l)&&H(i.getOptionValue(l),e,i.equalityKey)});return o?this.getOptionLabel(o):null},getSelectedItemsLabel:function(){var e=/{(.*?)}/,i=this.selectedItemsLabel||this.$primevue.config.locale.selectionMessage;return e.test(i)?i.replace(i.match(e)[0],this.modelValue.length+""):i},onToggleAll:function(e){var i=this;if(this.selectAll!==null)this.$emit("selectall-change",{originalEvent:e,checked:!this.allSelected});else{var n=this.allSelected?[]:this.visibleOptions.filter(function(o){return i.isValidOption(o)}).map(function(o){return i.getOptionValue(o)});this.updateModel(e,n)}},removeOption:function(e,i){var n=this;e.stopPropagation();var o=this.modelValue.filter(function(l){return!H(l,i,n.equalityKey)});this.updateModel(e,o)},clearFilter:function(){this.filterValue=null},hasFocusableElements:function(){return me(this.overlay,':not([data-p-hidden-focusable="true"])').length>0},isOptionMatched:function(e){var i;return this.isValidOption(e)&&typeof this.getOptionLabel(e)=="string"&&((i=this.getOptionLabel(e))===null||i===void 0?void 0:i.toLocaleLowerCase(this.filterLocale).startsWith(this.searchValue.toLocaleLowerCase(this.filterLocale)))},isValidOption:function(e){return x(e)&&!(this.isOptionDisabled(e)||this.isOptionGroup(e))},isValidSelectedOption:function(e){return this.isValidOption(e)&&this.isSelected(e)},isEquals:function(e,i){return H(e,i,this.equalityKey)},isSelected:function(e){var i=this,n=this.getOptionValue(e);return(this.modelValue||[]).some(function(o){return i.isEquals(o,n)})},findFirstOptionIndex:function(){var e=this;return this.visibleOptions.findIndex(function(i){return e.isValidOption(i)})},findLastOptionIndex:function(){var e=this;return B(this.visibleOptions,function(i){return e.isValidOption(i)})},findNextOptionIndex:function(e){var i=this,n=e<this.visibleOptions.length-1?this.visibleOptions.slice(e+1).findIndex(function(o){return i.isValidOption(o)}):-1;return n>-1?n+e+1:e},findPrevOptionIndex:function(e){var i=this,n=e>0?B(this.visibleOptions.slice(0,e),function(o){return i.isValidOption(o)}):-1;return n>-1?n:e},findSelectedOptionIndex:function(){var e=this;if(this.hasSelectedOption){for(var i=function(){var d=e.modelValue[o],m=e.visibleOptions.findIndex(function(b){return e.isValidSelectedOption(b)&&e.isEquals(d,e.getOptionValue(b))});if(m>-1)return{v:m}},n,o=this.modelValue.length-1;o>=0;o--)if(n=i(),n)return n.v}return-1},findFirstSelectedOptionIndex:function(){var e=this;return this.hasSelectedOption?this.visibleOptions.findIndex(function(i){return e.isValidSelectedOption(i)}):-1},findLastSelectedOptionIndex:function(){var e=this;return this.hasSelectedOption?B(this.visibleOptions,function(i){return e.isValidSelectedOption(i)}):-1},findNextSelectedOptionIndex:function(e){var i=this,n=this.hasSelectedOption&&e<this.visibleOptions.length-1?this.visibleOptions.slice(e+1).findIndex(function(o){return i.isValidSelectedOption(o)}):-1;return n>-1?n+e+1:-1},findPrevSelectedOptionIndex:function(e){var i=this,n=this.hasSelectedOption&&e>0?B(this.visibleOptions.slice(0,e),function(o){return i.isValidSelectedOption(o)}):-1;return n>-1?n:-1},findNearestSelectedOptionIndex:function(e){var i=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1,n=-1;return this.hasSelectedOption&&(i?(n=this.findPrevSelectedOptionIndex(e),n=n===-1?this.findNextSelectedOptionIndex(e):n):(n=this.findNextSelectedOptionIndex(e),n=n===-1?this.findPrevSelectedOptionIndex(e):n)),n>-1?n:e},findFirstFocusedOptionIndex:function(){var e=this.findSelectedOptionIndex();return e<0?this.findFirstOptionIndex():e},findLastFocusedOptionIndex:function(){var e=this.findSelectedOptionIndex();return e<0?this.findLastOptionIndex():e},searchOptions:function(e){var i=this;this.searchValue=(this.searchValue||"")+e.key;var n=-1;x(this.searchValue)&&(this.focusedOptionIndex!==-1?(n=this.visibleOptions.slice(this.focusedOptionIndex).findIndex(function(o){return i.isOptionMatched(o)}),n=n===-1?this.visibleOptions.slice(0,this.focusedOptionIndex).findIndex(function(o){return i.isOptionMatched(o)}):n+this.focusedOptionIndex):n=this.visibleOptions.findIndex(function(o){return i.isOptionMatched(o)}),n===-1&&this.focusedOptionIndex===-1&&(n=this.findFirstFocusedOptionIndex()),n!==-1&&this.changeFocusedOptionIndex(e,n)),this.searchTimeout&&clearTimeout(this.searchTimeout),this.searchTimeout=setTimeout(function(){i.searchValue="",i.searchTimeout=null},500)},changeFocusedOptionIndex:function(e,i){this.focusedOptionIndex!==i&&(this.focusedOptionIndex=i,this.scrollInView(),this.selectOnFocus&&this.onOptionSelect(e,this.visibleOptions[i]))},scrollInView:function(){var e=this,i=arguments.length>0&&arguments[0]!==void 0?arguments[0]:-1;this.$nextTick(function(){var n=i!==-1?"".concat(e.id,"_").concat(i):e.focusedOptionId,o=be(e.list,'li[id="'.concat(n,'"]'));o?o.scrollIntoView&&o.scrollIntoView({block:"nearest",inline:"nearest"}):e.virtualScrollerDisabled||e.virtualScroller&&e.virtualScroller.scrollToIndex(i!==-1?i:e.focusedOptionIndex)})},autoUpdateModel:function(){if(this.selectOnFocus&&this.autoOptionFocus&&!this.hasSelectedOption){this.focusedOptionIndex=this.findFirstFocusedOptionIndex();var e=this.getOptionValue(this.visibleOptions[this.focusedOptionIndex]);this.updateModel(null,[e])}},updateModel:function(e,i){this.$emit("update:modelValue",i),this.$emit("change",{originalEvent:e,value:i})},flatOptions:function(e){var i=this;return(e||[]).reduce(function(n,o,l){n.push({optionGroup:o,group:!0,index:l});var d=i.getOptionGroupChildren(o);return d&&d.forEach(function(m){return n.push(m)}),n},[])},overlayRef:function(e){this.overlay=e},listRef:function(e,i){this.list=e,i&&i(e)},virtualScrollerRef:function(e){this.virtualScroller=e}},computed:{visibleOptions:function(){var e=this,i=this.optionGroupLabel?this.flatOptions(this.options):this.options||[];if(this.filterValue){var n=ge.filter(i,this.searchFields,this.filterValue,this.filterMatchMode,this.filterLocale);if(this.optionGroupLabel){var o=this.options||[],l=[];return o.forEach(function(d){var m=e.getOptionGroupChildren(d),b=m.filter(function(z){return n.includes(z)});b.length>0&&l.push(W(W({},d),{},_({},typeof e.optionGroupChildren=="string"?e.optionGroupChildren:"items",Z(b))))}),this.flatOptions(l)}return n}return i},label:function(){var e;if(this.modelValue&&this.modelValue.length){if(x(this.maxSelectedLabels)&&this.modelValue.length>this.maxSelectedLabels)return this.getSelectedItemsLabel();e="";for(var i=0;i<this.modelValue.length;i++)i!==0&&(e+=", "),e+=this.getLabelByValue(this.modelValue[i])}else e=this.placeholder;return e},chipSelectedItems:function(){return x(this.maxSelectedLabels)&&this.modelValue&&this.modelValue.length>this.maxSelectedLabels?this.modelValue.slice(0,this.maxSelectedLabels):this.modelValue},allSelected:function(){var e=this;return this.selectAll!==null?this.selectAll:x(this.visibleOptions)&&this.visibleOptions.every(function(i){return e.isOptionGroup(i)||e.isOptionDisabled(i)||e.isSelected(i)})},hasSelectedOption:function(){return x(this.modelValue)},equalityKey:function(){return this.optionValue?null:this.dataKey},searchFields:function(){return this.filterFields||[this.optionLabel]},maxSelectionLimitReached:function(){return this.selectionLimit&&this.modelValue&&this.modelValue.length===this.selectionLimit},filterResultMessageText:function(){return x(this.visibleOptions)?this.filterMessageText.replaceAll("{0}",this.visibleOptions.length):this.emptyFilterMessageText},filterMessageText:function(){return this.filterMessage||this.$primevue.config.locale.searchMessage||""},emptyFilterMessageText:function(){return this.emptyFilterMessage||this.$primevue.config.locale.emptySearchMessage||this.$primevue.config.locale.emptyFilterMessage||""},emptyMessageText:function(){return this.emptyMessage||this.$primevue.config.locale.emptyMessage||""},selectionMessageText:function(){return this.selectionMessage||this.$primevue.config.locale.selectionMessage||""},emptySelectionMessageText:function(){return this.emptySelectionMessage||this.$primevue.config.locale.emptySelectionMessage||""},selectedMessageText:function(){return this.hasSelectedOption?this.selectionMessageText.replaceAll("{0}",this.modelValue.length):this.emptySelectionMessageText},focusedOptionId:function(){return this.focusedOptionIndex!==-1?"".concat(this.id,"_").concat(this.focusedOptionIndex):null},ariaSetSize:function(){var e=this;return this.visibleOptions.filter(function(i){return!e.isOptionGroup(i)}).length},toggleAllAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria[this.allSelected?"selectAll":"unselectAll"]:void 0},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},listAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.listLabel:void 0},virtualScrollerDisabled:function(){return!this.virtualScrollerOptions},hasFluid:function(){return ye(this.fluid)?!!this.$pcFluid:this.fluid}},directives:{ripple:Fe},components:{InputText:we,Checkbox:Re,VirtualScroller:Me,Portal:Le,Chip:Q,IconField:Te,InputIcon:Ae,TimesIcon:Ve,SearchIcon:Ee,ChevronDownIcon:De,SpinnerIcon:Ce,CheckIcon:Ke}};function D(t){"@babel/helpers - typeof";return D=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(e){return typeof e}:function(e){return e&&typeof Symbol=="function"&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},D(t)}function J(t,e,i){return(e=lt(e))in t?Object.defineProperty(t,e,{value:i,enumerable:!0,configurable:!0,writable:!0}):t[e]=i,t}function lt(t){var e=ot(t,"string");return D(e)=="symbol"?e:e+""}function ot(t,e){if(D(t)!="object"||!t)return t;var i=t[Symbol.toPrimitive];if(i!==void 0){var n=i.call(t,e||"default");if(D(n)!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(e==="string"?String:Number)(t)}var st=["id","disabled","placeholder","tabindex","aria-label","aria-labelledby","aria-expanded","aria-controls","aria-activedescendant","aria-invalid"],rt=["id","aria-label"],at=["id"],ct=["id","aria-label","aria-selected","aria-disabled","aria-setsize","aria-posinset","onClick","onMousemove","data-p-selected","data-p-focused","data-p-disabled"];function ut(t,e,i,n,o,l){var d=I("Chip"),m=I("SpinnerIcon"),b=I("Checkbox"),z=I("InputText"),ee=I("SearchIcon"),te=I("InputIcon"),ie=I("IconField"),ne=I("VirtualScroller"),le=I("Portal"),oe=ve("ripple");return r(),c("div",s({ref:"container",class:t.cx("root"),style:t.sx("root"),onClick:e[7]||(e[7]=function(){return l.onContainerClick&&l.onContainerClick.apply(l,arguments)})},t.ptmi("root")),[y("div",s({class:"p-hidden-accessible"},t.ptm("hiddenInputContainer"),{"data-p-hidden-accessible":!0}),[y("input",s({ref:"focusInput",id:t.inputId,type:"text",readonly:"",disabled:t.disabled,placeholder:t.placeholder,tabindex:t.disabled?-1:t.tabindex,role:"combobox","aria-label":t.ariaLabel,"aria-labelledby":t.ariaLabelledby,"aria-haspopup":"listbox","aria-expanded":o.overlayVisible,"aria-controls":o.id+"_list","aria-activedescendant":o.focused?l.focusedOptionId:void 0,"aria-invalid":t.invalid||void 0,onFocus:e[0]||(e[0]=function(){return l.onFocus&&l.onFocus.apply(l,arguments)}),onBlur:e[1]||(e[1]=function(){return l.onBlur&&l.onBlur.apply(l,arguments)}),onKeydown:e[2]||(e[2]=function(){return l.onKeyDown&&l.onKeyDown.apply(l,arguments)})},t.ptm("hiddenInput")),null,16,st)],16),y("div",s({class:t.cx("labelContainer")},t.ptm("labelContainer")),[y("div",s({class:t.cx("label")},t.ptm("label")),[h(t.$slots,"value",{value:t.modelValue,placeholder:t.placeholder},function(){return[t.display==="comma"?(r(),c(T,{key:0},[A(O(l.label||"empty"),1)],64)):t.display==="chip"?(r(),c(T,{key:1},[(r(!0),c(T,null,N(l.chipSelectedItems,function(a){return r(),c("span",s({key:l.getLabelByValue(a),class:t.cx("chipItem"),ref_for:!0},t.ptm("chipItem")),[h(t.$slots,"chip",{value:a,removeCallback:function(w){return l.removeOption(w,a)}},function(){return[F(d,{class:k(t.cx("pcChip")),label:l.getLabelByValue(a),removeIcon:t.chipIcon||t.removeTokenIcon,removable:"",unstyled:t.unstyled,onRemove:function(w){return l.removeOption(w,a)},pt:t.ptm("pcChip")},{removeicon:S(function(){return[h(t.$slots,t.$slots.chipicon?"chipicon":"removetokenicon",{class:k(t.cx("chipIcon")),item:a,removeCallback:function(w){return l.removeOption(w,a)}})]}),_:2},1032,["class","label","removeIcon","unstyled","onRemove","pt"])]})],16)}),128)),!t.modelValue||t.modelValue.length===0?(r(),c(T,{key:0},[A(O(t.placeholder||"empty"),1)],64)):f("",!0)],64)):f("",!0)]})],16)],16),y("div",s({class:t.cx("dropdown")},t.ptm("dropdown")),[t.loading?h(t.$slots,"loadingicon",{key:0,class:k(t.cx("loadingIcon"))},function(){return[t.loadingIcon?(r(),c("span",s({key:0,class:[t.cx("loadingIcon"),"pi-spin",t.loadingIcon],"aria-hidden":"true"},t.ptm("loadingIcon")),null,16)):(r(),v(m,s({key:1,class:t.cx("loadingIcon"),spin:"","aria-hidden":"true"},t.ptm("loadingIcon")),null,16,["class"]))]}):h(t.$slots,"dropdownicon",{key:1,class:k(t.cx("dropdownIcon"))},function(){return[(r(),v(C(t.dropdownIcon?"span":"ChevronDownIcon"),s({class:[t.cx("dropdownIcon"),t.dropdownIcon],"aria-hidden":"true"},t.ptm("dropdownIcon")),null,16,["class"]))]})],16),F(le,{appendTo:t.appendTo},{default:S(function(){return[F(Oe,s({name:"p-connected-overlay",onEnter:l.onOverlayEnter,onAfterEnter:l.onOverlayAfterEnter,onLeave:l.onOverlayLeave,onAfterLeave:l.onOverlayAfterLeave},t.ptm("transition")),{default:S(function(){return[o.overlayVisible?(r(),c("div",s({key:0,ref:l.overlayRef,style:[t.panelStyle,t.overlayStyle],class:[t.cx("overlay"),t.panelClass,t.overlayClass],onClick:e[5]||(e[5]=function(){return l.onOverlayClick&&l.onOverlayClick.apply(l,arguments)}),onKeydown:e[6]||(e[6]=function(){return l.onOverlayKeyDown&&l.onOverlayKeyDown.apply(l,arguments)})},t.ptm("overlay")),[y("span",s({ref:"firstHiddenFocusableElementOnOverlay",role:"presentation","aria-hidden":"true",class:"p-hidden-accessible p-hidden-focusable",tabindex:0,onFocus:e[3]||(e[3]=function(){return l.onFirstHiddenFocus&&l.onFirstHiddenFocus.apply(l,arguments)})},t.ptm("hiddenFirstFocusableEl"),{"data-p-hidden-accessible":!0,"data-p-hidden-focusable":!0}),null,16),h(t.$slots,"header",{value:t.modelValue,options:l.visibleOptions}),t.showToggleAll&&t.selectionLimit==null||t.filter?(r(),c("div",s({key:0,class:t.cx("header")},t.ptm("header")),[t.showToggleAll&&t.selectionLimit==null?(r(),v(b,{key:0,modelValue:l.allSelected,binary:!0,disabled:t.disabled,variant:t.variant,"aria-label":l.toggleAllAriaLabel,onChange:l.onToggleAll,unstyled:t.unstyled,pt:l.getHeaderCheckboxPTOptions("pcHeaderCheckbox")},{icon:S(function(a){return[t.$slots.headercheckboxicon?(r(),v(C(t.$slots.headercheckboxicon),{key:0,checked:a.checked,class:k(a.class)},null,8,["checked","class"])):a.checked?(r(),v(C(t.checkboxIcon?"span":"CheckIcon"),s({key:1,class:[a.class,J({},t.checkboxIcon,a.checked)]},l.getHeaderCheckboxPTOptions("pcHeaderCheckbox.icon")),null,16,["class"])):f("",!0)]}),_:1},8,["modelValue","disabled","variant","aria-label","onChange","unstyled","pt"])):f("",!0),t.filter?(r(),v(ie,{key:1,class:k(t.cx("pcFilterContainer")),unstyled:t.unstyled,pt:t.ptm("pcFilterContainer")},{default:S(function(){return[F(z,{ref:"filterInput",value:o.filterValue,onVnodeMounted:l.onFilterUpdated,onVnodeUpdated:l.onFilterUpdated,class:k(t.cx("pcFilter")),placeholder:t.filterPlaceholder,disabled:t.disabled,variant:t.variant,unstyled:t.unstyled,role:"searchbox",autocomplete:"off","aria-owns":o.id+"_list","aria-activedescendant":l.focusedOptionId,onKeydown:l.onFilterKeyDown,onBlur:l.onFilterBlur,onInput:l.onFilterChange,pt:t.ptm("pcFilter")},null,8,["value","onVnodeMounted","onVnodeUpdated","class","placeholder","disabled","variant","unstyled","aria-owns","aria-activedescendant","onKeydown","onBlur","onInput","pt"]),F(te,s({unstyled:t.unstyled},t.ptm("pcFilterIconContainer")),{default:S(function(){return[h(t.$slots,"filtericon",{},function(){return[t.filterIcon?(r(),c("span",s({key:0,class:t.filterIcon},t.ptm("filterIcon")),null,16)):(r(),v(ee,Ie(s({key:1},t.ptm("filterIcon"))),null,16))]})]}),_:3},16,["unstyled"])]}),_:3},8,["class","unstyled","pt"])):f("",!0),t.filter?(r(),c("span",s({key:2,role:"status","aria-live":"polite",class:"p-hidden-accessible"},t.ptm("hiddenFilterResult"),{"data-p-hidden-accessible":!0}),O(l.filterResultMessageText),17)):f("",!0)],16)):f("",!0),y("div",s({class:t.cx("listContainer"),style:{"max-height":l.virtualScrollerDisabled?t.scrollHeight:""}},t.ptm("listContainer")),[F(ne,s({ref:l.virtualScrollerRef},t.virtualScrollerOptions,{items:l.visibleOptions,style:{height:t.scrollHeight},tabindex:-1,disabled:l.virtualScrollerDisabled,pt:t.ptm("virtualScroller")}),Se({content:S(function(a){var K=a.styleClass,w=a.contentRef,R=a.items,g=a.getItemOptions,se=a.contentStyle,P=a.itemSize;return[y("ul",s({ref:function(p){return l.listRef(p,w)},id:o.id+"_list",class:[t.cx("list"),K],style:se,role:"listbox","aria-multiselectable":"true","aria-label":l.listAriaLabel},t.ptm("list")),[(r(!0),c(T,null,N(R,function(u,p){return r(),c(T,{key:l.getOptionRenderKey(u,l.getOptionIndex(p,g))},[l.isOptionGroup(u)?(r(),c("li",s({key:0,id:o.id+"_"+l.getOptionIndex(p,g),style:{height:P?P+"px":void 0},class:t.cx("optionGroup"),role:"option",ref_for:!0},t.ptm("optionGroup")),[h(t.$slots,"optiongroup",{option:u.optionGroup,index:l.getOptionIndex(p,g)},function(){return[A(O(l.getOptionGroupLabel(u.optionGroup)),1)]})],16,at)):ke((r(),c("li",s({key:1,id:o.id+"_"+l.getOptionIndex(p,g),style:{height:P?P+"px":void 0},class:t.cx("option",{option:u,index:p,getItemOptions:g}),role:"option","aria-label":l.getOptionLabel(u),"aria-selected":l.isSelected(u),"aria-disabled":l.isOptionDisabled(u),"aria-setsize":l.ariaSetSize,"aria-posinset":l.getAriaPosInset(l.getOptionIndex(p,g)),onClick:function(G){return l.onOptionSelect(G,u,l.getOptionIndex(p,g),!0)},onMousemove:function(G){return l.onOptionMouseMove(G,l.getOptionIndex(p,g))},ref_for:!0},l.getCheckboxPTOptions(u,g,p,"option"),{"data-p-selected":l.isSelected(u),"data-p-focused":o.focusedOptionIndex===l.getOptionIndex(p,g),"data-p-disabled":l.isOptionDisabled(u)}),[F(b,{modelValue:l.isSelected(u),binary:!0,tabindex:-1,variant:t.variant,unstyled:t.unstyled,pt:l.getCheckboxPTOptions(u,g,p,"pcOptionCheckbox")},{icon:S(function(L){return[t.$slots.optioncheckboxicon||t.$slots.itemcheckboxicon?(r(),v(C(t.$slots.optioncheckboxicon||t.$slots.itemcheckboxicon),{key:0,checked:L.checked,class:k(L.class)},null,8,["checked","class"])):L.checked?(r(),v(C(t.checkboxIcon?"span":"CheckIcon"),s({key:1,class:[L.class,J({},t.checkboxIcon,L.checked)],ref_for:!0},l.getCheckboxPTOptions(u,g,p,"pcOptionCheckbox.icon")),null,16,["class"])):f("",!0)]}),_:2},1032,["modelValue","variant","unstyled","pt"]),h(t.$slots,"option",{option:u,selected:l.isSelected(u),index:l.getOptionIndex(p,g)},function(){return[y("span",s({ref_for:!0},t.ptm("optionLabel")),O(l.getOptionLabel(u)),17)]})],16,ct)),[[oe]])],64)}),128)),o.filterValue&&(!R||R&&R.length===0)?(r(),c("li",s({key:0,class:t.cx("emptyMessage"),role:"option"},t.ptm("emptyMessage")),[h(t.$slots,"emptyfilter",{},function(){return[A(O(l.emptyFilterMessageText),1)]})],16)):!t.options||t.options&&t.options.length===0?(r(),c("li",s({key:1,class:t.cx("emptyMessage"),role:"option"},t.ptm("emptyMessage")),[h(t.$slots,"empty",{},function(){return[A(O(l.emptyMessageText),1)]})],16)):f("",!0)],16,rt)]}),_:2},[t.$slots.loader?{name:"loader",fn:S(function(a){var K=a.options;return[h(t.$slots,"loader",{options:K})]}),key:"0"}:void 0]),1040,["items","style","disabled","pt"])],16),h(t.$slots,"footer",{value:t.modelValue,options:l.visibleOptions}),!t.options||t.options&&t.options.length===0?(r(),c("span",s({key:1,role:"status","aria-live":"polite",class:"p-hidden-accessible"},t.ptm("hiddenEmptyMessage"),{"data-p-hidden-accessible":!0}),O(l.emptyMessageText),17)):f("",!0),y("span",s({role:"status","aria-live":"polite",class:"p-hidden-accessible"},t.ptm("hiddenSelectedMessage"),{"data-p-hidden-accessible":!0}),O(l.selectedMessageText),17),y("span",s({ref:"lastHiddenFocusableElementOnOverlay",role:"presentation","aria-hidden":"true",class:"p-hidden-accessible p-hidden-focusable",tabindex:0,onFocus:e[4]||(e[4]=function(){return l.onLastHiddenFocus&&l.onLastHiddenFocus.apply(l,arguments)})},t.ptm("hiddenLastFocusableEl"),{"data-p-hidden-accessible":!0,"data-p-hidden-focusable":!0}),null,16)],16)):f("",!0)]}),_:3},16,["onEnter","onAfterEnter","onLeave","onAfterLeave"])]}),_:3},8,["appendTo"])],16)}nt.render=ut;export{Q as a,nt as s};
