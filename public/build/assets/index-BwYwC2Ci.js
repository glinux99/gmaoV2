import{B as ne,C as fe,a5 as V,Q as H,a4 as R,R as b,S as pe,o as d,l as f,j as y,q as he,D as u,m as v,e as h,s as g,V as S,f as w,p as k,A as ye,k as j,t as P,n as W,E as m,i as O,H as T,az as be,aA as ge,aq as Z,J as me,K as ve,L as ke,M as Se,N as we,I as Ce,W as Oe,X as Te}from"./app-DlduAWKf.js";import{s as Ke,c as oe,a as q,R as re,U as J,Z as D,C as Ie,b as xe}from"./index-a9PRqMaA.js";import{s as Ne,a as Ee,e as Le,c as ie}from"./index-BLVnNnMn.js";import{a as Ae}from"./index-D4BnT4fz.js";import{O as Me}from"./index-DEclfOQi.js";import{s as je}from"./index-BQ2NgWYf.js";import{a as Pe}from"./index-8z2yMFcP.js";import{s as Fe,a as Ve}from"./index-C4OTDIwl.js";var De=function(t){var n=t.dt;return`
.p-tree {
    background: `.concat(n("tree.background"),`;
    color: `).concat(n("tree.color"),`;
    padding: `).concat(n("tree.padding"),`;
}

.p-tree-root-children,
.p-tree-node-children {
    display: flex;
    list-style-type: none;
    flex-direction: column;
    margin: 0;
    gap: `).concat(n("tree.gap"),`;
}

.p-tree-root-children {
    padding: `).concat(n("tree.gap"),` 0 0 0;
}

.p-tree-node-children {
    padding: `).concat(n("tree.gap")," 0 0 ").concat(n("tree.indent"),`;
}

.p-tree-node {
    padding: 0;
    outline: 0 none;
}

.p-tree-node-content {
    border-radius: `).concat(n("tree.node.border.radius"),`;
    padding: `).concat(n("tree.node.padding"),`;
    display: flex;
    align-items: center;
    outline-color: transparent;
    color: `).concat(n("tree.node.color"),`;
    gap: `).concat(n("tree.node.gap"),`;
    transition: background `).concat(n("tree.transition.duration"),", color ").concat(n("tree.transition.duration"),", outline-color ").concat(n("tree.transition.duration"),", box-shadow ").concat(n("tree.transition.duration"),`;
}

.p-tree-node:focus-visible > .p-tree-node-content {
    box-shadow: `).concat(n("tree.node.focus.ring.shadow"),`;
    outline: `).concat(n("tree.node.focus.ring.width")," ").concat(n("tree.node.focus.ring.style")," ").concat(n("tree.node.focus.ring.color"),`;
    outline-offset: `).concat(n("tree.node.focus.ring.offset"),`;
}

.p-tree-node-content.p-tree-node-selectable:not(.p-tree-node-selected):hover {
    background: `).concat(n("tree.node.hover.background"),`;
    color: `).concat(n("tree.node.hover.color"),`;
}

.p-tree-node-content.p-tree-node-selectable:not(.p-tree-node-selected):hover .p-tree-node-icon {
    color: `).concat(n("tree.node.icon.hover.color"),`;
}

.p-tree-node-content.p-tree-node-selected {
    background: `).concat(n("tree.node.selected.background"),`;
    color: `).concat(n("tree.node.selected.color"),`;
}

.p-tree-node-content.p-tree-node-selected .p-tree-node-toggle-button {
    color: inherit;
}

.p-tree-node-toggle-button {
    cursor: pointer;
    user-select: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    flex-shrink: 0;
    width: `).concat(n("tree.node.toggle.button.size"),`;
    height: `).concat(n("tree.node.toggle.button.size"),`;
    color: `).concat(n("tree.node.toggle.button.color"),`;
    border: 0 none;
    background: transparent;
    border-radius: `).concat(n("tree.node.toggle.button.border.radius"),`;
    transition: background `).concat(n("tree.transition.duration"),", color ").concat(n("tree.transition.duration"),", border-color ").concat(n("tree.transition.duration"),", outline-color ").concat(n("tree.transition.duration"),", box-shadow ").concat(n("tree.transition.duration"),`;
    outline-color: transparent;
    padding: 0;
}

.p-tree-node-toggle-button:enabled:hover {
    background: `).concat(n("tree.node.toggle.button.hover.background"),`;
    color: `).concat(n("tree.node.toggle.button.hover.color"),`;
}

.p-tree-node-content.p-tree-node-selected .p-tree-node-toggle-button:hover {
    background: `).concat(n("tree.node.toggle.button.selected.hover.background"),`;
    color: `).concat(n("tree.node.toggle.button.selected.hover.color"),`;
}

.p-tree-root {
    overflow: auto;
}

.p-tree-node-selectable {
    cursor: pointer;
    user-select: none;
}

.p-tree-node-leaf > .p-tree-node-content .p-tree-node-toggle-button {
    visibility: hidden;
}

.p-tree-node-icon {
    color: `).concat(n("tree.node.icon.color"),`;
    transition: color `).concat(n("tree.transition.duration"),`;
}

.p-tree-node-content.p-tree-node-selected .p-tree-node-icon {
    color: `).concat(n("tree.node.icon.selected.color"),`;
}

.p-tree-filter-input {
    width: 100%;
}

.p-tree-loading {
    position: relative;
    height: 100%;
}

.p-tree-loading-icon {
    font-size: `).concat(n("tree.loading.icon.size"),`;
    width: `).concat(n("tree.loading.icon.size"),`;
    height: `).concat(n("tree.loading.icon.size"),`;
}

.p-tree .p-tree-mask {
    position: absolute;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.p-tree-flex-scrollable {
    display: flex;
    flex: 1;
    height: 100%;
    flex-direction: column;
}

.p-tree-flex-scrollable .p-tree-root {
    flex: 1;
}
`)},Be={root:function(t){var n=t.props;return["p-tree p-component",{"p-tree-selectable":n.selectionMode!=null,"p-tree-loading":n.loading,"p-tree-flex-scrollable":n.scrollHeight==="flex"}]},mask:"p-tree-mask p-overlay-mask",loadingIcon:"p-tree-loading-icon",pcFilterInput:"p-tree-filter-input",wrapper:"p-tree-root",rootChildren:"p-tree-root-children",node:function(t){var n=t.instance;return["p-tree-node",{"p-tree-node-leaf":n.leaf}]},nodeContent:function(t){var n=t.instance;return["p-tree-node-content",n.node.styleClass,{"p-tree-node-selectable":n.selectable,"p-tree-node-selected":n.checkboxMode&&n.$parentInstance.highlightOnSelect?n.checked:n.selected}]},nodeToggleButton:"p-tree-node-toggle-button",nodeToggleIcon:"p-tree-node-toggle-icon",nodeCheckbox:"p-tree-node-checkbox",nodeIcon:"p-tree-node-icon",nodeLabel:"p-tree-node-label",nodeChildren:"p-tree-node-children"},He=ne.extend({name:"tree",theme:De,classes:Be}),Re={name:"BaseTree",extends:q,props:{value:{type:null,default:null},expandedKeys:{type:null,default:null},selectionKeys:{type:null,default:null},selectionMode:{type:String,default:null},metaKeySelection:{type:Boolean,default:!1},loading:{type:Boolean,default:!1},loadingIcon:{type:String,default:void 0},loadingMode:{type:String,default:"mask"},filter:{type:Boolean,default:!1},filterBy:{type:String,default:"label"},filterMode:{type:String,default:"lenient"},filterPlaceholder:{type:String,default:null},filterLocale:{type:String,default:void 0},highlightOnSelect:{type:Boolean,default:!1},scrollHeight:{type:String,default:null},level:{type:Number,default:0},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:He,provide:function(){return{$pcTree:this,$parentInstance:this}}};function x(e){"@babel/helpers - typeof";return x=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},x(e)}function Q(e,t){var n=typeof Symbol<"u"&&e[Symbol.iterator]||e["@@iterator"];if(!n){if(Array.isArray(e)||(n=le(e))||t){n&&(e=n);var o=0,i=function(){};return{s:i,n:function(){return o>=e.length?{done:!0}:{done:!1,value:e[o++]}},e:function(l){throw l},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var r,s=!0,c=!1;return{s:function(){n=n.call(e)},n:function(){var l=n.next();return s=l.done,l},e:function(l){c=!0,r=l},f:function(){try{s||n.return==null||n.return()}finally{if(c)throw r}}}}function X(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter(function(i){return Object.getOwnPropertyDescriptor(e,i).enumerable})),n.push.apply(n,o)}return n}function G(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]!=null?arguments[t]:{};t%2?X(Object(n),!0).forEach(function(o){ze(e,o,n[o])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):X(Object(n)).forEach(function(o){Object.defineProperty(e,o,Object.getOwnPropertyDescriptor(n,o))})}return e}function ze(e,t,n){return(t=$e(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function $e(e){var t=Ue(e,"string");return x(t)=="symbol"?t:t+""}function Ue(e,t){if(x(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var o=n.call(e,t||"default");if(x(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}function K(e){return Ze(e)||qe(e)||le(e)||We()}function We(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function le(e,t){if(e){if(typeof e=="string")return z(e,t);var n={}.toString.call(e).slice(8,-1);return n==="Object"&&e.constructor&&(n=e.constructor.name),n==="Map"||n==="Set"?Array.from(e):n==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?z(e,t):void 0}}function qe(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function Ze(e){if(Array.isArray(e))return z(e)}function z(e,t){(t==null||t>e.length)&&(t=e.length);for(var n=0,o=Array(t);n<t;n++)o[n]=e[n];return o}var ae={name:"TreeNode",hostName:"Tree",extends:q,emits:["node-toggle","node-click","checkbox-change"],props:{node:{type:null,default:null},expandedKeys:{type:null,default:null},loadingMode:{type:String,default:"mask"},selectionKeys:{type:null,default:null},selectionMode:{type:String,default:null},templates:{type:null,default:null},level:{type:Number,default:null},index:null},nodeTouched:!1,toggleClicked:!1,mounted:function(){this.setAllNodesTabIndexes()},methods:{toggle:function(){this.$emit("node-toggle",this.node),this.toggleClicked=!0},label:function(t){return typeof t.label=="function"?t.label():t.label},onChildNodeToggle:function(t){this.$emit("node-toggle",t)},getPTOptions:function(t){return this.ptm(t,{context:{index:this.index,expanded:this.expanded,selected:this.selected,checked:this.checked,leaf:this.leaf}})},onClick:function(t){if(this.toggleClicked||V(t.target,'[data-pc-section="nodetogglebutton"]')||V(t.target.parentElement,'[data-pc-section="nodetogglebutton"]')){this.toggleClicked=!1;return}this.isCheckboxSelectionMode()?this.toggleCheckbox():this.$emit("node-click",{originalEvent:t,nodeTouched:this.nodeTouched,node:this.node}),this.nodeTouched=!1},onChildNodeClick:function(t){this.$emit("node-click",t)},onTouchEnd:function(){this.nodeTouched=!0},onKeyDown:function(t){if(this.isSameNode(t))switch(t.code){case"Tab":this.onTabKey(t);break;case"ArrowDown":this.onArrowDown(t);break;case"ArrowUp":this.onArrowUp(t);break;case"ArrowRight":this.onArrowRight(t);break;case"ArrowLeft":this.onArrowLeft(t);break;case"Enter":case"NumpadEnter":case"Space":this.onEnterKey(t);break}},onArrowDown:function(t){var n=t.target.getAttribute("data-pc-section")==="nodetogglebutton"?t.target.closest('[role="treeitem"]'):t.target,o=n.children[1];if(o)this.focusRowChange(n,o.children[0]);else if(n.nextElementSibling)this.focusRowChange(n,n.nextElementSibling);else{var i=this.findNextSiblingOfAncestor(n);i&&this.focusRowChange(n,i)}t.preventDefault()},onArrowUp:function(t){var n=t.target;if(n.previousElementSibling)this.focusRowChange(n,n.previousElementSibling,this.findLastVisibleDescendant(n.previousElementSibling));else{var o=this.getParentNodeElement(n);o&&this.focusRowChange(n,o)}t.preventDefault()},onArrowRight:function(t){var n=this;this.leaf||this.expanded||(t.currentTarget.tabIndex=-1,this.$emit("node-toggle",this.node),this.$nextTick(function(){n.onArrowDown(t)}))},onArrowLeft:function(t){var n=H(t.currentTarget,'[data-pc-section="nodetogglebutton"]');if(this.level===0&&!this.expanded)return!1;if(this.expanded&&!this.leaf)return n.click(),!1;var o=this.findBeforeClickableNode(t.currentTarget);o&&this.focusRowChange(t.currentTarget,o)},onEnterKey:function(t){this.setTabIndexForSelectionMode(t,this.nodeTouched),this.onClick(t),t.preventDefault()},onTabKey:function(){this.setAllNodesTabIndexes()},setAllNodesTabIndexes:function(){var t=R(this.$refs.currentNode.closest('[data-pc-section="rootchildren"]'),'[role="treeitem"]'),n=K(t).some(function(i){return i.getAttribute("aria-selected")==="true"||i.getAttribute("aria-checked")==="true"});if(K(t).forEach(function(i){i.tabIndex=-1}),n){var o=K(t).filter(function(i){return i.getAttribute("aria-selected")==="true"||i.getAttribute("aria-checked")==="true"});o[0].tabIndex=0;return}K(t)[0].tabIndex=0},setTabIndexForSelectionMode:function(t,n){if(this.selectionMode!==null){var o=K(R(this.$refs.currentNode.parentElement,'[role="treeitem"]'));t.currentTarget.tabIndex=n===!1?-1:0,o.every(function(i){return i.tabIndex===-1})&&(o[0].tabIndex=0)}},focusRowChange:function(t,n,o){t.tabIndex="-1",n.tabIndex="0",this.focusNode(o||n)},findBeforeClickableNode:function(t){var n=t.closest("ul").closest("li");if(n){var o=H(n,"button");return o&&o.style.visibility!=="hidden"?n:this.findBeforeClickableNode(t.previousElementSibling)}return null},toggleCheckbox:function(){var t=this.selectionKeys?G({},this.selectionKeys):{},n=!this.checked;this.propagateDown(this.node,n,t),this.$emit("checkbox-change",{node:this.node,check:n,selectionKeys:t})},propagateDown:function(t,n,o){if(n?o[t.key]={checked:!0,partialChecked:!1}:delete o[t.key],t.children&&t.children.length){var i=Q(t.children),r;try{for(i.s();!(r=i.n()).done;){var s=r.value;this.propagateDown(s,n,o)}}catch(c){i.e(c)}finally{i.f()}}},propagateUp:function(t){var n=t.check,o=G({},t.selectionKeys),i=0,r=!1,s=Q(this.node.children),c;try{for(s.s();!(c=s.n()).done;){var a=c.value;o[a.key]&&o[a.key].checked?i++:o[a.key]&&o[a.key].partialChecked&&(r=!0)}}catch(l){s.e(l)}finally{s.f()}n&&i===this.node.children.length?o[this.node.key]={checked:!0,partialChecked:!1}:(n||delete o[this.node.key],r||i>0&&i!==this.node.children.length?o[this.node.key]={checked:!1,partialChecked:!0}:delete o[this.node.key]),this.$emit("checkbox-change",{node:t.node,check:t.check,selectionKeys:o})},onChildCheckboxChange:function(t){this.$emit("checkbox-change",t)},findNextSiblingOfAncestor:function(t){var n=this.getParentNodeElement(t);return n?n.nextElementSibling?n.nextElementSibling:this.findNextSiblingOfAncestor(n):null},findLastVisibleDescendant:function(t){var n=t.children[1];if(n){var o=n.children[n.children.length-1];return this.findLastVisibleDescendant(o)}else return t},getParentNodeElement:function(t){var n=t.parentElement.parentElement;return V(n,"role")==="treeitem"?n:null},focusNode:function(t){t.focus()},isCheckboxSelectionMode:function(){return this.selectionMode==="checkbox"},isSameNode:function(t){return t.currentTarget&&(t.currentTarget.isSameNode(t.target)||t.currentTarget.isSameNode(t.target.closest('[role="treeitem"]')))}},computed:{hasChildren:function(){return this.node.children&&this.node.children.length>0},expanded:function(){return this.expandedKeys&&this.expandedKeys[this.node.key]===!0},leaf:function(){return this.node.leaf===!1?!1:!(this.node.children&&this.node.children.length)},selectable:function(){return this.node.selectable===!1?!1:this.selectionMode!=null},selected:function(){return this.selectionMode&&this.selectionKeys?this.selectionKeys[this.node.key]===!0:!1},checkboxMode:function(){return this.selectionMode==="checkbox"&&this.node.selectable!==!1},checked:function(){return this.selectionKeys?this.selectionKeys[this.node.key]&&this.selectionKeys[this.node.key].checked:!1},partialChecked:function(){return this.selectionKeys?this.selectionKeys[this.node.key]&&this.selectionKeys[this.node.key].partialChecked:!1},ariaChecked:function(){return this.selectionMode==="single"||this.selectionMode==="multiple"?this.selected:void 0},ariaSelected:function(){return this.checkboxMode?this.checked:void 0}},components:{Checkbox:Fe,ChevronDownIcon:ie,ChevronRightIcon:Pe,CheckIcon:je,MinusIcon:Ve,SpinnerIcon:oe},directives:{ripple:re}},Je=["aria-label","aria-selected","aria-expanded","aria-setsize","aria-posinset","aria-level","aria-checked","tabindex"],Qe=["data-p-selected","data-p-selectable"];function Xe(e,t,n,o,i,r){var s=b("SpinnerIcon"),c=b("Checkbox"),a=b("TreeNode",!0),l=pe("ripple");return d(),f("li",u({ref:"currentNode",class:e.cx("node"),role:"treeitem","aria-label":r.label(n.node),"aria-selected":r.ariaSelected,"aria-expanded":r.expanded,"aria-setsize":n.node.children?n.node.children.length:0,"aria-posinset":n.index+1,"aria-level":n.level,"aria-checked":r.ariaChecked,tabindex:n.index===0?0:-1,onKeydown:t[4]||(t[4]=function(){return r.onKeyDown&&r.onKeyDown.apply(r,arguments)})},n.level===1?r.getPTOptions("node"):e.ptm("nodeChildren")),[y("div",u({class:e.cx("nodeContent"),onClick:t[2]||(t[2]=function(){return r.onClick&&r.onClick.apply(r,arguments)}),onTouchend:t[3]||(t[3]=function(){return r.onTouchEnd&&r.onTouchEnd.apply(r,arguments)}),style:n.node.style},r.getPTOptions("nodeContent"),{"data-p-selected":r.checkboxMode?r.checked:r.selected,"data-p-selectable":r.selectable}),[he((d(),f("button",u({type:"button",class:e.cx("nodeToggleButton"),onClick:t[0]||(t[0]=function(){return r.toggle&&r.toggle.apply(r,arguments)}),tabindex:"-1","aria-hidden":"true"},r.getPTOptions("nodeToggleButton")),[n.node.loading&&n.loadingMode==="icon"?(d(),f(v,{key:0},[n.templates.nodetoggleicon||n.templates.nodetogglericon?(d(),h(S(n.templates.nodetoggleicon||n.templates.nodetogglericon),{key:0,class:g(e.cx("nodeToggleIcon"))},null,8,["class"])):(d(),h(s,u({key:1,spin:"",class:e.cx("nodetogglericon")},e.ptm("nodeToggleIcon")),null,16,["class"]))],64)):(d(),f(v,{key:1},[n.templates.nodetoggleicon||n.templates.togglericon?(d(),h(S(n.templates.nodetoggleicon||n.templates.togglericon),{key:0,node:n.node,expanded:r.expanded,class:g(e.cx("nodeToggleIcon"))},null,8,["node","expanded","class"])):r.expanded?(d(),h(S(n.node.expandedIcon?"span":"ChevronDownIcon"),u({key:1,class:e.cx("nodeToggleIcon")},r.getPTOptions("nodeToggleIcon")),null,16,["class"])):(d(),h(S(n.node.collapsedIcon?"span":"ChevronRightIcon"),u({key:2,class:e.cx("nodeToggleIcon")},r.getPTOptions("nodeToggleIcon")),null,16,["class"]))],64))],16)),[[l]]),r.checkboxMode?(d(),h(c,{key:0,modelValue:r.checked,binary:!0,indeterminate:r.partialChecked,class:g(e.cx("nodeCheckbox")),tabindex:-1,unstyled:e.unstyled,pt:r.getPTOptions("nodeCheckbox"),"data-p-partialchecked":r.partialChecked},{icon:w(function(p){return[n.templates.checkboxicon?(d(),h(S(n.templates.checkboxicon),{key:0,checked:p.checked,partialChecked:r.partialChecked,class:g(p.class)},null,8,["checked","partialChecked","class"])):k("",!0)]}),_:1},8,["modelValue","indeterminate","class","unstyled","pt","data-p-partialchecked"])):k("",!0),n.templates.nodeicon?(d(),h(S(n.templates.nodeicon),u({key:1,node:n.node,class:[e.cx("nodeIcon")]},r.getPTOptions("nodeIcon")),null,16,["node","class"])):(d(),f("span",u({key:2,class:[e.cx("nodeIcon"),n.node.icon]},r.getPTOptions("nodeIcon")),null,16)),y("span",u({class:e.cx("nodeLabel")},r.getPTOptions("nodeLabel"),{onKeydown:t[1]||(t[1]=ye(function(){},["stop"]))}),[n.templates[n.node.type]||n.templates.default?(d(),h(S(n.templates[n.node.type]||n.templates.default),{key:0,node:n.node,selected:r.checkboxMode?r.checked:r.selected},null,8,["node","selected"])):(d(),f(v,{key:1},[j(P(r.label(n.node)),1)],64))],16)],16,Qe),r.hasChildren&&r.expanded?(d(),f("ul",u({key:0,class:e.cx("nodeChildren"),role:"group"},e.ptm("nodeChildren")),[(d(!0),f(v,null,W(n.node.children,function(p){return d(),h(a,{key:p.key,node:p,templates:n.templates,level:n.level+1,loadingMode:n.loadingMode,expandedKeys:n.expandedKeys,onNodeToggle:r.onChildNodeToggle,onNodeClick:r.onChildNodeClick,selectionMode:n.selectionMode,selectionKeys:n.selectionKeys,onCheckboxChange:r.propagateUp,unstyled:e.unstyled,pt:e.pt},null,8,["node","templates","level","loadingMode","expandedKeys","onNodeToggle","onNodeClick","selectionMode","selectionKeys","onCheckboxChange","unstyled","pt"])}),128))],16)):k("",!0)],16,Je)}ae.render=Xe;function N(e){"@babel/helpers - typeof";return N=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},N(e)}function B(e,t){var n=typeof Symbol<"u"&&e[Symbol.iterator]||e["@@iterator"];if(!n){if(Array.isArray(e)||(n=se(e))||t){n&&(e=n);var o=0,i=function(){};return{s:i,n:function(){return o>=e.length?{done:!0}:{done:!1,value:e[o++]}},e:function(l){throw l},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var r,s=!0,c=!1;return{s:function(){n=n.call(e)},n:function(){var l=n.next();return s=l.done,l},e:function(l){c=!0,r=l},f:function(){try{s||n.return==null||n.return()}finally{if(c)throw r}}}}function Ge(e){return et(e)||_e(e)||se(e)||Ye()}function Ye(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function se(e,t){if(e){if(typeof e=="string")return $(e,t);var n={}.toString.call(e).slice(8,-1);return n==="Object"&&e.constructor&&(n=e.constructor.name),n==="Map"||n==="Set"?Array.from(e):n==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?$(e,t):void 0}}function _e(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function et(e){if(Array.isArray(e))return $(e)}function $(e,t){(t==null||t>e.length)&&(t=e.length);for(var n=0,o=Array(t);n<t;n++)o[n]=e[n];return o}function Y(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter(function(i){return Object.getOwnPropertyDescriptor(e,i).enumerable})),n.push.apply(n,o)}return n}function C(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]!=null?arguments[t]:{};t%2?Y(Object(n),!0).forEach(function(o){tt(e,o,n[o])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):Y(Object(n)).forEach(function(o){Object.defineProperty(e,o,Object.getOwnPropertyDescriptor(n,o))})}return e}function tt(e,t,n){return(t=nt(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function nt(e){var t=ot(e,"string");return N(t)=="symbol"?t:t+""}function ot(e,t){if(N(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var o=n.call(e,t||"default");if(N(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var ce={name:"Tree",extends:Re,inheritAttrs:!1,emits:["node-expand","node-collapse","update:expandedKeys","update:selectionKeys","node-select","node-unselect","filter"],data:function(){return{d_expandedKeys:this.expandedKeys||{},filterValue:null}},watch:{expandedKeys:function(t){this.d_expandedKeys=t}},methods:{onNodeToggle:function(t){var n=t.key;this.d_expandedKeys[n]?(delete this.d_expandedKeys[n],this.$emit("node-collapse",t)):(this.d_expandedKeys[n]=!0,this.$emit("node-expand",t)),this.d_expandedKeys=C({},this.d_expandedKeys),this.$emit("update:expandedKeys",this.d_expandedKeys)},onNodeClick:function(t){if(this.selectionMode!=null&&t.node.selectable!==!1){var n=t.nodeTouched?!1:this.metaKeySelection,o=n?this.handleSelectionWithMetaKey(t):this.handleSelectionWithoutMetaKey(t);this.$emit("update:selectionKeys",o)}},onCheckboxChange:function(t){this.$emit("update:selectionKeys",t.selectionKeys),t.check?this.$emit("node-select",t.node):this.$emit("node-unselect",t.node)},handleSelectionWithMetaKey:function(t){var n=t.originalEvent,o=t.node,i=n.metaKey||n.ctrlKey,r=this.isNodeSelected(o),s;return r&&i?(this.isSingleSelectionMode()?s={}:(s=C({},this.selectionKeys),delete s[o.key]),this.$emit("node-unselect",o)):(this.isSingleSelectionMode()?s={}:this.isMultipleSelectionMode()&&(s=i?this.selectionKeys?C({},this.selectionKeys):{}:{}),s[o.key]=!0,this.$emit("node-select",o)),s},handleSelectionWithoutMetaKey:function(t){var n=t.node,o=this.isNodeSelected(n),i;return this.isSingleSelectionMode()?o?(i={},this.$emit("node-unselect",n)):(i={},i[n.key]=!0,this.$emit("node-select",n)):o?(i=C({},this.selectionKeys),delete i[n.key],this.$emit("node-unselect",n)):(i=this.selectionKeys?C({},this.selectionKeys):{},i[n.key]=!0,this.$emit("node-select",n)),i},isSingleSelectionMode:function(){return this.selectionMode==="single"},isMultipleSelectionMode:function(){return this.selectionMode==="multiple"},isNodeSelected:function(t){return this.selectionMode&&this.selectionKeys?this.selectionKeys[t.key]===!0:!1},isChecked:function(t){return this.selectionKeys?this.selectionKeys[t.key]&&this.selectionKeys[t.key].checked:!1},isNodeLeaf:function(t){return t.leaf===!1?!1:!(t.children&&t.children.length)},onFilterKeydown:function(t){(t.code==="Enter"||t.code==="NumpadEnter")&&t.preventDefault(),this.$emit("filter",{originalEvent:t,value:t.target.value})},findFilteredNodes:function(t,n){if(t){var o=!1;if(t.children){var i=Ge(t.children);t.children=[];var r=B(i),s;try{for(r.s();!(s=r.n()).done;){var c=s.value,a=C({},c);this.isFilterMatched(a,n)&&(o=!0,t.children.push(a))}}catch(l){r.e(l)}finally{r.f()}}if(o)return!0}},isFilterMatched:function(t,n){var o=n.searchFields,i=n.filterText,r=n.strict,s=!1,c=B(o),a;try{for(c.s();!(a=c.n()).done;){var l=a.value,p=String(fe(t,l)).toLocaleLowerCase(this.filterLocale);p.indexOf(i)>-1&&(s=!0)}}catch(F){c.e(F)}finally{c.f()}return(!s||r&&!this.isNodeLeaf(t))&&(s=this.findFilteredNodes(t,{searchFields:o,filterText:i,strict:r})||s),s}},computed:{filteredValue:function(){var t=[],n=this.filterBy.split(","),o=this.filterValue.trim().toLocaleLowerCase(this.filterLocale),i=this.filterMode==="strict",r=B(this.value),s;try{for(r.s();!(s=r.n()).done;){var c=s.value,a=C({},c),l={searchFields:n,filterText:o,strict:i};(i&&(this.findFilteredNodes(a,l)||this.isFilterMatched(a,l))||!i&&(this.isFilterMatched(a,l)||this.findFilteredNodes(a,l)))&&t.push(a)}}catch(p){r.e(p)}finally{r.f()}return t},valueToRender:function(){return this.filterValue&&this.filterValue.trim().length>0?this.filteredValue:this.value}},components:{TreeNode:ae,InputText:Ke,InputIcon:Ne,IconField:Ee,SearchIcon:Le,SpinnerIcon:oe}},rt=["aria-labelledby","aria-label"];function it(e,t,n,o,i,r){var s=b("SpinnerIcon"),c=b("InputText"),a=b("SearchIcon"),l=b("InputIcon"),p=b("IconField"),F=b("TreeNode");return d(),f("div",u({class:e.cx("root")},e.ptmi("root")),[e.loading&&e.loadingMode==="mask"?(d(),f("div",u({key:0,class:e.cx("mask")},e.ptm("mask")),[m(e.$slots,"loadingicon",{class:g(e.cx("loadingIcon"))},function(){return[e.loadingIcon?(d(),f("i",u({key:0,class:[e.cx("loadingIcon"),"pi-spin",e.loadingIcon]},e.ptm("loadingIcon")),null,16)):(d(),h(s,u({key:1,spin:"",class:e.cx("loadingIcon")},e.ptm("loadingIcon")),null,16,["class"]))]})],16)):k("",!0),e.filter?(d(),h(p,{key:1,unstyled:e.unstyled,pt:e.ptm("pcFilterContainer")},{default:w(function(){return[O(c,{modelValue:i.filterValue,"onUpdate:modelValue":t[0]||(t[0]=function(A){return i.filterValue=A}),autocomplete:"off",class:g(e.cx("pcFilter")),placeholder:e.filterPlaceholder,unstyled:e.unstyled,onKeydown:r.onFilterKeydown,pt:e.ptm("pcFilter")},null,8,["modelValue","class","placeholder","unstyled","onKeydown","pt"]),O(l,{unstyled:e.unstyled,pt:e.ptm("pcFilterIconContainer")},{default:w(function(){return[m(e.$slots,e.$slots.filtericon?"filtericon":"searchicon",{class:g(e.cx("filterIcon"))},function(){return[O(a,u({class:e.cx("filterIcon")},e.ptm("filterIcon")),null,16,["class"])]})]}),_:3},8,["unstyled","pt"])]}),_:3},8,["unstyled","pt"])):k("",!0),y("div",u({class:e.cx("wrapper"),style:{maxHeight:e.scrollHeight}},e.ptm("wrapper")),[y("ul",u({class:e.cx("rootChildren"),role:"tree","aria-labelledby":e.ariaLabelledby,"aria-label":e.ariaLabel},e.ptm("rootChildren")),[(d(!0),f(v,null,W(r.valueToRender,function(A,ue){return d(),h(F,{key:A.key,node:A,templates:e.$slots,level:e.level+1,index:ue,expandedKeys:i.d_expandedKeys,onNodeToggle:r.onNodeToggle,onNodeClick:r.onNodeClick,selectionMode:e.selectionMode,selectionKeys:e.selectionKeys,onCheckboxChange:r.onCheckboxChange,loadingMode:e.loadingMode,unstyled:e.unstyled,pt:e.pt},null,8,["node","templates","level","index","expandedKeys","onNodeToggle","onNodeClick","selectionMode","selectionKeys","onCheckboxChange","loadingMode","unstyled","pt"])}),128))],16,rt)],16)],16)}ce.render=it;var lt=function(t){var n=t.dt;return`
.p-treeselect {
    display: inline-flex;
    cursor: pointer;
    position: relative;
    user-select: none;
    background: `.concat(n("treeselect.background"),`;
    border: 1px solid `).concat(n("treeselect.border.color"),`;
    transition: background `).concat(n("treeselect.transition.duration"),", color ").concat(n("treeselect.transition.duration"),", border-color ").concat(n("treeselect.transition.duration"),", outline-color ").concat(n("treeselect.transition.duration"),", box-shadow ").concat(n("treeselect.transition.duration"),`;
    border-radius: `).concat(n("treeselect.border.radius"),`;
    outline-color: transparent;
    box-shadow: `).concat(n("treeselect.shadow"),`;
}

.p-treeselect:not(.p-disabled):hover {
    border-color: `).concat(n("treeselect.hover.border.color"),`;
}

.p-treeselect:not(.p-disabled).p-focus {
    border-color: `).concat(n("treeselect.focus.border.color"),`;
    box-shadow: `).concat(n("treeselect.focus.ring.shadow"),`;
    outline: `).concat(n("treeselect.focus.ring.width")," ").concat(n("treeselect.focus.ring.style")," ").concat(n("treeselect.focus.ring.color"),`;
    outline-offset: `).concat(n("treeselect.focus.ring.offset"),`;
}

.p-treeselect.p-variant-filled {
    background: `).concat(n("treeselect.filled.background"),`;
}

.p-treeselect.p-variant-filled.p-focus {
    background: `).concat(n("treeselect.filled.focus.background"),`;
}

.p-treeselect.p-invalid {
    border-color: `).concat(n("treeselect.invalid.border.color"),`;
}

.p-treeselect.p-disabled {
    opacity: 1;
    background: `).concat(n("treeselect.disabled.background"),`;
}

.p-treeselect-dropdown {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: transparent;
    color: `).concat(n("treeselect.dropdown.color"),`;
    width: `).concat(n("treeselect.dropdown.width"),`;
    border-top-right-radius: `).concat(n("border.radius.md"),`;
    border-bottom-right-radius: `).concat(n("border.radius.md"),`;
}

.p-treeselect-label-container {
    overflow: hidden;
    flex: 1 1 auto;
    cursor: pointer;
}

.p-treeselect-label {
    display: flex;
    align-items-center;
    gap: calc(`).concat(n("treeselect.padding.y"),` / 2);
    white-space: nowrap;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: `).concat(n("treeselect.padding.y")," ").concat(n("treeselect.padding.x"),`;
    color: `).concat(n("treeselect.color"),`;
}

.p-treeselect-label.p-placeholder {
    color: `).concat(n("treeselect.placeholder.color"),`;
}

.p-treeselect.p-disabled .p-treeselect-label {
    color: `).concat(n("treeselect.disabled.color"),`;
}

.p-treeselect-label-empty {
    overflow: hidden;
    visibility: hidden;
}

.p-treeselect .p-treeselect-overlay {
    min-width: 100%;
}

.p-treeselect-overlay {
    position: absolute;
    top: 0;
    left: 0;
    background: `).concat(n("treeselect.overlay.background"),`;
    color: `).concat(n("treeselect.overlay.color"),`;
    border: 1px solid `).concat(n("treeselect.overlay.border.color"),`;
    border-radius: `).concat(n("treeselect.overlay.border.radius"),`;
    box-shadow: `).concat(n("treeselect.overlay.shadow"),`;
    overflow: hidden;
}


.p-treeselect-tree-container {
    overflow: auto;
}

.p-treeselect-empty-message {
    padding: `).concat(n("treeselect.empty.message.padding"),`;
    background: transparent;
}

.p-treeselect-fluid {
    display: flex;
}

.p-treeselect-overlay .p-tree {
    padding: `).concat(n("treeselect.tree.padding"),`;
}

.p-treeselect-label .p-chip {
    padding-top: calc(`).concat(n("treeselect.padding.y"),` / 2);
    padding-bottom: calc(`).concat(n("treeselect.padding.y"),` / 2);
    border-radius: `).concat(n("treeselect.chip.border.radius"),`;
}

.p-treeselect-label:has(.p-chip) {
    padding: calc(`).concat(n("treeselect.padding.y")," / 2) calc(").concat(n("treeselect.padding.x"),` / 2);
}
`)},at={root:function(t){var n=t.props;return{position:n.appendTo==="self"?"relative":void 0}}},st={root:function(t){var n=t.instance,o=t.props;return["p-treeselect p-component p-inputwrapper",{"p-treeselect-display-chip":o.display==="chip","p-disabled":o.disabled,"p-invalid":o.invalid,"p-focus":n.focused,"p-variant-filled":o.variant?o.variant==="filled":n.$primevue.config.inputStyle==="filled"||n.$primevue.config.inputVariant==="filled","p-inputwrapper-filled":!n.emptyValue,"p-inputwrapper-focus":n.focused||n.overlayVisible,"p-treeselect-open":n.overlayVisible,"p-treeselect-fluid":n.hasFluid}]},labelContainer:"p-treeselect-label-container",label:function(t){var n=t.instance,o=t.props;return["p-treeselect-label",{"p-placeholder":n.label===o.placeholder,"p-treeselect-label-empty":!o.placeholder&&n.emptyValue}]},chip:"p-treeselect-chip-item",pcChip:"p-treeselect-chip",dropdown:"p-treeselect-dropdown",dropdownIcon:"p-treeselect-dropdown-icon",panel:"p-treeselect-overlay p-component",treeContainer:"p-treeselect-tree-container",emptyMessage:"p-treeselect-empty-message"},ct=ne.extend({name:"treeselect",theme:lt,classes:st,inlineStyles:at}),dt={name:"BaseTreeSelect",extends:q,props:{modelValue:null,options:Array,scrollHeight:{type:String,default:"20rem"},placeholder:{type:String,default:null},invalid:{type:Boolean,default:!1},variant:{type:String,default:null},disabled:{type:Boolean,default:!1},tabindex:{type:Number,default:null},selectionMode:{type:String,default:"single"},appendTo:{type:[String,Object],default:"body"},emptyMessage:{type:String,default:null},display:{type:String,default:"comma"},metaKeySelection:{type:Boolean,default:!1},fluid:{type:Boolean,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},inputProps:{type:null,default:null},panelClass:{type:[String,Object],default:null},panelProps:{type:null,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:ct,provide:function(){return{$pcTreeSelect:this,$parentInstance:this}}};function E(e){"@babel/helpers - typeof";return E=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},E(e)}function _(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter(function(i){return Object.getOwnPropertyDescriptor(e,i).enumerable})),n.push.apply(n,o)}return n}function ee(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]!=null?arguments[t]:{};t%2?_(Object(n),!0).forEach(function(o){ut(e,o,n[o])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):_(Object(n)).forEach(function(o){Object.defineProperty(e,o,Object.getOwnPropertyDescriptor(n,o))})}return e}function ut(e,t,n){return(t=ft(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function ft(e){var t=pt(e,"string");return E(t)=="symbol"?t:t+""}function pt(e,t){if(E(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var o=n.call(e,t||"default");if(E(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}function I(e,t){var n=typeof Symbol<"u"&&e[Symbol.iterator]||e["@@iterator"];if(!n){if(Array.isArray(e)||(n=de(e))||t){n&&(e=n);var o=0,i=function(){};return{s:i,n:function(){return o>=e.length?{done:!0}:{done:!1,value:e[o++]}},e:function(l){throw l},f:i}}throw new TypeError(`Invalid attempt to iterate non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}var r,s=!0,c=!1;return{s:function(){n=n.call(e)},n:function(){var l=n.next();return s=l.done,l},e:function(l){c=!0,r=l},f:function(){try{s||n.return==null||n.return()}finally{if(c)throw r}}}}function ht(e){return gt(e)||bt(e)||de(e)||yt()}function yt(){throw new TypeError(`Invalid attempt to spread non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)}function de(e,t){if(e){if(typeof e=="string")return U(e,t);var n={}.toString.call(e).slice(8,-1);return n==="Object"&&e.constructor&&(n=e.constructor.name),n==="Map"||n==="Set"?Array.from(e):n==="Arguments"||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?U(e,t):void 0}}function bt(e){if(typeof Symbol<"u"&&e[Symbol.iterator]!=null||e["@@iterator"]!=null)return Array.from(e)}function gt(e){if(Array.isArray(e))return U(e)}function U(e,t){(t==null||t>e.length)&&(t=e.length);for(var n=0,o=Array(t);n<t;n++)o[n]=e[n];return o}var mt={name:"TreeSelect",extends:dt,inheritAttrs:!1,emits:["update:modelValue","before-show","before-hide","change","show","hide","node-select","node-unselect","node-expand","node-collapse","focus","blur"],inject:{$pcFluid:{default:null}},data:function(){return{id:this.$attrs.id,focused:!1,overlayVisible:!1,expandedKeys:{}}},watch:{"$attrs.id":function(t){this.id=t||J()},modelValue:{handler:function(){this.selfChange||this.updateTreeState(),this.selfChange=!1},immediate:!0},options:function(){this.updateTreeState()}},outsideClickListener:null,resizeListener:null,scrollHandler:null,overlay:null,selfChange:!1,selfClick:!1,beforeUnmount:function(){this.unbindOutsideClickListener(),this.unbindResizeListener(),this.scrollHandler&&(this.scrollHandler.destroy(),this.scrollHandler=null),this.overlay&&(D.clear(this.overlay),this.overlay=null)},mounted:function(){this.id=this.id||J(),this.updateTreeState()},methods:{show:function(){this.$emit("before-show"),this.overlayVisible=!0},hide:function(){this.$emit("before-hide"),this.overlayVisible=!1,this.$refs.focusInput.focus()},onFocus:function(t){this.focused=!0,this.$emit("focus",t)},onBlur:function(t){this.focused=!1,this.$emit("blur",t)},onClick:function(t){this.disabled||!this.disabled&&(!this.overlay||!this.overlay.contains(t.target))&&(this.overlayVisible?this.hide():this.show(),T(this.$refs.focusInput))},onSelectionChange:function(t){this.selfChange=!0,this.$emit("update:modelValue",t),this.$emit("change",t)},onNodeSelect:function(t){this.$emit("node-select",t),this.selectionMode==="single"&&this.hide()},onNodeUnselect:function(t){this.$emit("node-unselect",t)},onNodeToggle:function(t){this.expandedKeys=t},onFirstHiddenFocus:function(t){var n=t.relatedTarget===this.$refs.focusInput?be(this.overlay,':not([data-p-hidden-focusable="true"])'):this.$refs.focusInput;T(n)},onLastHiddenFocus:function(t){var n=t.relatedTarget===this.$refs.focusInput?ge(this.overlay,':not([data-p-hidden-focusable="true"])'):this.$refs.focusInput;T(n)},onKeyDown:function(t){switch(t.code){case"ArrowDown":this.onArrowDownKey(t);break;case"Space":case"Enter":case"NumpadEnter":this.onEnterKey(t);break;case"Escape":this.onEscapeKey(t);break;case"Tab":this.onTabKey(t);break}},onArrowDownKey:function(t){var n=this;this.overlayVisible||(this.show(),this.$nextTick(function(){var o=R(n.$refs.tree.$el,'[data-pc-section="treeitem"]'),i=ht(o).find(function(r){return r.getAttribute("tabindex")==="0"});T(i)}),t.preventDefault())},onEnterKey:function(t){this.overlayVisible?this.hide():this.onArrowDownKey(t),t.preventDefault()},onEscapeKey:function(t){this.overlayVisible&&(this.hide(),t.preventDefault())},onTabKey:function(t){var n=arguments.length>1&&arguments[1]!==void 0?arguments[1]:!1;n||this.overlayVisible&&this.hasFocusableElements()&&(T(this.$refs.firstHiddenFocusableElementOnOverlay),t.preventDefault())},hasFocusableElements:function(){return Z(this.overlay,':not([data-p-hidden-focusable="true"])').length>0},onOverlayEnter:function(t){D.set("overlay",t,this.$primevue.config.zIndex.overlay),me(t,{position:"absolute",top:"0",left:"0"}),this.alignOverlay(),this.focus()},onOverlayAfterEnter:function(){this.bindOutsideClickListener(),this.bindScrollListener(),this.bindResizeListener(),this.scrollValueInView(),this.$emit("show")},onOverlayLeave:function(){this.unbindOutsideClickListener(),this.unbindScrollListener(),this.unbindResizeListener(),this.$emit("hide"),this.overlay=null},onOverlayAfterLeave:function(t){D.clear(t)},focus:function(){var t=Z(this.overlay);t&&t.length>0&&t[0].focus()},alignOverlay:function(){this.appendTo==="self"?ve(this.overlay,this.$el):(this.overlay.style.minWidth=ke(this.$el)+"px",Se(this.overlay,this.$el))},bindOutsideClickListener:function(){var t=this;this.outsideClickListener||(this.outsideClickListener=function(n){t.overlayVisible&&!t.selfClick&&t.isOutsideClicked(n)&&t.hide(),t.selfClick=!1},document.addEventListener("click",this.outsideClickListener))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener),this.outsideClickListener=null)},bindScrollListener:function(){var t=this;this.scrollHandler||(this.scrollHandler=new Ie(this.$refs.container,function(){t.overlayVisible&&t.hide()})),this.scrollHandler.bindScrollListener()},unbindScrollListener:function(){this.scrollHandler&&this.scrollHandler.unbindScrollListener()},bindResizeListener:function(){var t=this;this.resizeListener||(this.resizeListener=function(){t.overlayVisible&&!we()&&t.hide()},window.addEventListener("resize",this.resizeListener))},unbindResizeListener:function(){this.resizeListener&&(window.removeEventListener("resize",this.resizeListener),this.resizeListener=null)},isOutsideClicked:function(t){return!(this.$el.isSameNode(t.target)||this.$el.contains(t.target)||this.overlay&&this.overlay.contains(t.target))},overlayRef:function(t){this.overlay=t},onOverlayClick:function(t){Me.emit("overlay-click",{originalEvent:t,target:this.$el}),this.selfClick=!0},onOverlayKeydown:function(t){t.code==="Escape"&&this.hide()},findSelectedNodes:function(t,n,o){if(t){if(this.isSelected(t,n)&&(o.push(t),delete n[t.key]),Object.keys(n).length&&t.children){var i=I(t.children),r;try{for(i.s();!(r=i.n()).done;){var s=r.value;this.findSelectedNodes(s,n,o)}}catch(p){i.e(p)}finally{i.f()}}}else{var c=I(this.options),a;try{for(c.s();!(a=c.n()).done;){var l=a.value;this.findSelectedNodes(l,n,o)}}catch(p){c.e(p)}finally{c.f()}}},isSelected:function(t,n){return this.selectionMode==="checkbox"?n[t.key]&&n[t.key].checked:n[t.key]},updateTreeState:function(){var t=ee({},this.modelValue);this.expandedKeys={},t&&this.options&&this.updateTreeBranchState(null,null,t)},updateTreeBranchState:function(t,n,o){if(t){if(this.isSelected(t,o)&&(this.expandPath(n),delete o[t.key]),Object.keys(o).length&&t.children){var i=I(t.children),r;try{for(i.s();!(r=i.n()).done;){var s=r.value;n.push(t.key),this.updateTreeBranchState(s,n,o)}}catch(p){i.e(p)}finally{i.f()}}}else{var c=I(this.options),a;try{for(c.s();!(a=c.n()).done;){var l=a.value;this.updateTreeBranchState(l,[],o)}}catch(p){c.e(p)}finally{c.f()}}},expandPath:function(t){if(t.length>0){var n=I(t),o;try{for(n.s();!(o=n.n()).done;){var i=o.value;this.expandedKeys[i]=!0}}catch(r){n.e(r)}finally{n.f()}}},scrollValueInView:function(){if(this.overlay){var t=H(this.overlay,'[data-p-selected="true"]');t&&t.scrollIntoView({block:"nearest",inline:"start"})}}},computed:{selectedNodes:function(){var t=[];if(this.modelValue&&this.options){var n=ee({},this.modelValue);this.findSelectedNodes(null,n,t)}return t},label:function(){var t=this.selectedNodes;return t.length?t.map(function(n){return n.label}).join(", "):this.placeholder},emptyMessageText:function(){return this.emptyMessage||this.$primevue.config.locale.emptyMessage},emptyValue:function(){return!this.modelValue||Object.keys(this.modelValue).length===0},emptyOptions:function(){return!this.options||this.options.length===0},listId:function(){return this.id+"_list"},hasFluid:function(){return Ce(this.fluid)?!!this.$pcFluid:this.fluid}},components:{TSTree:ce,Chip:Ae,Portal:xe,ChevronDownIcon:ie},directives:{ripple:re}};function L(e){"@babel/helpers - typeof";return L=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},L(e)}function te(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter(function(i){return Object.getOwnPropertyDescriptor(e,i).enumerable})),n.push.apply(n,o)}return n}function M(e){for(var t=1;t<arguments.length;t++){var n=arguments[t]!=null?arguments[t]:{};t%2?te(Object(n),!0).forEach(function(o){vt(e,o,n[o])}):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):te(Object(n)).forEach(function(o){Object.defineProperty(e,o,Object.getOwnPropertyDescriptor(n,o))})}return e}function vt(e,t,n){return(t=kt(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function kt(e){var t=St(e,"string");return L(t)=="symbol"?t:t+""}function St(e,t){if(L(e)!="object"||!e)return e;var n=e[Symbol.toPrimitive];if(n!==void 0){var o=n.call(e,t||"default");if(L(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var wt=["id","disabled","tabindex","aria-labelledby","aria-label","aria-expanded","aria-controls"],Ct=["aria-expanded"];function Ot(e,t,n,o,i,r){var s=b("Chip"),c=b("TSTree"),a=b("Portal");return d(),f("div",u({ref:"container",class:e.cx("root"),style:e.sx("root"),onClick:t[9]||(t[9]=function(){return r.onClick&&r.onClick.apply(r,arguments)})},e.ptmi("root")),[y("div",u({class:"p-hidden-accessible"},e.ptm("hiddenInputContainer"),{"data-p-hidden-accessible":!0}),[y("input",u({ref:"focusInput",id:e.inputId,type:"text",role:"combobox",class:e.inputClass,style:e.inputStyle,readonly:"",disabled:e.disabled,tabindex:e.disabled?-1:e.tabindex,"aria-labelledby":e.ariaLabelledby,"aria-label":e.ariaLabel,"aria-haspopup":"tree","aria-expanded":i.overlayVisible,"aria-controls":r.listId,onFocus:t[0]||(t[0]=function(l){return r.onFocus(l)}),onBlur:t[1]||(t[1]=function(l){return r.onBlur(l)}),onKeydown:t[2]||(t[2]=function(l){return r.onKeyDown(l)})},M(M({},e.inputProps),e.ptm("hiddenInput"))),null,16,wt)],16),y("div",u({class:e.cx("labelContainer")},e.ptm("labelContainer")),[y("div",u({class:e.cx("label")},e.ptm("label")),[m(e.$slots,"value",{value:r.selectedNodes,placeholder:e.placeholder},function(){return[e.display==="comma"?(d(),f(v,{key:0},[j(P(r.label||"empty"),1)],64)):e.display==="chip"?(d(),f(v,{key:1},[(d(!0),f(v,null,W(r.selectedNodes,function(l){return d(),f("div",u({key:l.key,class:e.cx("chipItem"),ref_for:!0},e.ptm("chipItem")),[O(s,{class:g(e.cx("pcChip")),label:l.label,unstyled:e.unstyled,pt:e.ptm("pcChip")},null,8,["class","label","unstyled","pt"])],16)}),128)),r.emptyValue?(d(),f(v,{key:0},[j(P(e.placeholder||"empty"),1)],64)):k("",!0)],64)):k("",!0)]})],16)],16),y("div",u({class:e.cx("dropdown"),role:"button","aria-haspopup":"tree","aria-expanded":i.overlayVisible},e.ptm("dropdown")),[m(e.$slots,e.$slots.dropdownicon?"dropdownicon":"triggericon",{class:g(e.cx("dropdownIcon"))},function(){return[(d(),h(S("ChevronDownIcon"),u({class:e.cx("dropdownIcon")},e.ptm("dropdownIcon")),null,16,["class"]))]})],16,Ct),O(a,{appendTo:e.appendTo},{default:w(function(){return[O(Oe,u({name:"p-connected-overlay",onEnter:r.onOverlayEnter,onAfterEnter:r.onOverlayAfterEnter,onLeave:r.onOverlayLeave,onAfterLeave:r.onOverlayAfterLeave},e.ptm("transition")),{default:w(function(){return[i.overlayVisible?(d(),f("div",u({key:0,ref:r.overlayRef,onClick:t[7]||(t[7]=function(){return r.onOverlayClick&&r.onOverlayClick.apply(r,arguments)}),class:[e.cx("panel"),e.panelClass],onKeydown:t[8]||(t[8]=function(){return r.onOverlayKeydown&&r.onOverlayKeydown.apply(r,arguments)})},M(M({},e.panelProps),e.ptm("panel"))),[y("span",u({ref:"firstHiddenFocusableElementOnOverlay",role:"presentation","aria-hidden":"true",class:"p-hidden-accessible p-hidden-focusable",tabindex:0,onFocus:t[3]||(t[3]=function(){return r.onFirstHiddenFocus&&r.onFirstHiddenFocus.apply(r,arguments)})},e.ptm("hiddenFirstFocusableEl"),{"data-p-hidden-accessible":!0,"data-p-hidden-focusable":!0}),null,16),m(e.$slots,"header",{value:e.modelValue,options:e.options}),y("div",u({class:e.cx("treeContainer"),style:{"max-height":e.scrollHeight}},e.ptm("treeContainer")),[O(c,{ref:"tree",id:r.listId,value:e.options,selectionMode:e.selectionMode,"onUpdate:selectionKeys":r.onSelectionChange,selectionKeys:e.modelValue,expandedKeys:i.expandedKeys,"onUpdate:expandedKeys":r.onNodeToggle,metaKeySelection:e.metaKeySelection,onNodeExpand:t[4]||(t[4]=function(l){return e.$emit("node-expand",l)}),onNodeCollapse:t[5]||(t[5]=function(l){return e.$emit("node-collapse",l)}),onNodeSelect:r.onNodeSelect,onNodeUnselect:r.onNodeUnselect,level:0,unstyled:e.unstyled,pt:e.ptm("pcTree")},Te({_:2},[e.$slots.itemtoggleicon?{name:"toggleicon",fn:w(function(l){return[m(e.$slots,"itemtoggleicon",{node:l.node,expanded:l.expanded,class:g(l.class)})]}),key:"0"}:e.$slots.itemtogglericon?{name:"togglericon",fn:w(function(l){return[m(e.$slots,"itemtogglericon",{node:l.node,expanded:l.expanded,class:g(l.class)})]}),key:"1"}:void 0,e.$slots.itemcheckboxicon?{name:"checkboxicon",fn:w(function(l){return[m(e.$slots,"itemcheckboxicon",{checked:l.checked,partialChecked:l.partialChecked,class:g(l.class)})]}),key:"2"}:void 0]),1032,["id","value","selectionMode","onUpdate:selectionKeys","selectionKeys","expandedKeys","onUpdate:expandedKeys","metaKeySelection","onNodeSelect","onNodeUnselect","unstyled","pt"]),r.emptyOptions?(d(),f("div",u({key:0,class:e.cx("emptyMessage")},e.ptm("emptyMessage")),[m(e.$slots,"empty",{},function(){return[j(P(r.emptyMessageText),1)]})],16)):k("",!0)],16),m(e.$slots,"footer",{value:e.modelValue,options:e.options}),y("span",u({ref:"lastHiddenFocusableElementOnOverlay",role:"presentation","aria-hidden":"true",class:"p-hidden-accessible p-hidden-focusable",tabindex:0,onFocus:t[6]||(t[6]=function(){return r.onLastHiddenFocus&&r.onLastHiddenFocus.apply(r,arguments)})},e.ptm("hiddenLastFocusableEl"),{"data-p-hidden-accessible":!0,"data-p-hidden-focusable":!0}),null,16)],16)):k("",!0)]}),_:3},16,["onEnter","onAfterEnter","onLeave","onAfterLeave"])]}),_:3},8,["appendTo"])],16)}mt.render=Ot;export{mt as s};
