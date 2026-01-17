import{T as U}from"./index-CrFEKHH1.js";import{B as H,o,l as a,D as $,j as s,a as G,b as J,r as C,d as Z,e as W,f as D,i as f,g as y,Z as w,q as A,p,s as R,t as u,U as _,m as T,n as E,V as K,W as X,a1 as L,a0 as Y,x as ee,y as te,k as I}from"./app-gDNqx2_D.js";import{_ as se}from"./AppLayout-SGD7JHFV.js";import{E as ne}from"./jspdf.es.min--9NON6Sc.js";import oe from"./html2canvas.esm-CBrSDip1.js";import{s as re}from"./index-DS5Z58Bp.js";import{s as S}from"./index-BFXGy4uN.js";import{a as ae}from"./index-DnMOyY1I.js";import{s as ie}from"./index-D4DTux10.js";import{C as le,p as ce,a as pe,b as de,B as ue,c as fe,L as me,P as he,d as ge,A as ye,R as xe}from"./chart-B59wzfTt.js";import{B as z,L as ve,P as be,D as ke,R as _e}from"./index-BYlhvC2O.js";import{_ as Se}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./DropdownLink-DIW2SyDI.js";import"./index-E0OnojZQ.js";import"./index-CadKbAM9.js";import"./index-3Y3FmX7C.js";import"./index-Sf75UUsw.js";import"./index-qcYzN5_Q.js";import"./index-C57fO0mp.js";import"./index-psDJnd0s.js";import"./index-CaNPRaEQ.js";import"./index-qtJx-Jx6.js";import"./index-bSGFH6Vt.js";var Ce=function(m){var x=m.dt;return`
.p-progressspinner {
    position: relative;
    margin: 0 auto;
    width: 100px;
    height: 100px;
    display: inline-block;
}

.p-progressspinner::before {
    content: "";
    display: block;
    padding-top: 100%;
}

.p-progressspinner-spin {
    height: 100%;
    transform-origin: center center;
    width: 100%;
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    margin: auto;
    animation: p-progressspinner-rotate 2s linear infinite;
}

.p-progressspinner-circle {
    stroke-dasharray: 89, 200;
    stroke-dashoffset: 0;
    stroke: `.concat(x("progressspinner.color.1"),`;
    animation: p-progressspinner-dash 1.5s ease-in-out infinite, p-progressspinner-color 6s ease-in-out infinite;
    stroke-linecap: round;
}

@keyframes p-progressspinner-rotate {
    100% {
        transform: rotate(360deg);
    }
}
@keyframes p-progressspinner-dash {
    0% {
        stroke-dasharray: 1, 200;
        stroke-dashoffset: 0;
    }
    50% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -35px;
    }
    100% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -124px;
    }
}
@keyframes p-progressspinner-color {
    100%,
    0% {
        stroke: `).concat(x("progressspinner.color.1"),`;
    }
    40% {
        stroke: `).concat(x("progressspinner.color.2"),`;
    }
    66% {
        stroke: `).concat(x("progressspinner.color.3"),`;
    }
    80%,
    90% {
        stroke: `).concat(x("progressspinner.color.4"),`;
    }
}
`)},Te={root:"p-progressspinner",spin:"p-progressspinner-spin",circle:"p-progressspinner-circle"},Ee=H.extend({name:"progressspinner",theme:Ce,classes:Te}),$e={name:"BaseProgressSpinner",extends:ae,props:{strokeWidth:{type:String,default:"2"},fill:{type:String,default:"none"},animationDuration:{type:String,default:"2s"}},style:Ee,provide:function(){return{$pcProgressSpinner:this,$parentInstance:this}}},j={name:"ProgressSpinner",extends:$e,inheritAttrs:!1,computed:{svgStyle:function(){return{"animation-duration":this.animationDuration}}}},De=["fill","stroke-width"];function Pe(i,m,x,b,v,c){return o(),a("div",$({class:i.cx("root"),role:"progressbar"},i.ptmi("root")),[(o(),a("svg",$({class:i.cx("spin"),viewBox:"25 25 50 50",style:c.svgStyle},i.ptm("spin")),[s("circle",$({class:i.cx("circle"),cx:"50",cy:"50",r:"20",fill:i.fill,"stroke-width":i.strokeWidth,strokeMiterlimit:"10"},i.ptm("circle")),null,16,De)],16))],16)}j.render=Pe;const k=i=>(ee("data-v-e857986c"),i=i(),te(),i),Be={class:"bg-[#f1f5f9] min-h-screen p-8"},We={class:"flex justify-between items-center mb-10 max-w-7xl mx-auto"},Ae=k(()=>s("div",null,[s("h1",{class:"text-3xl font-black tracking-tighter text-slate-900 uppercase italic"},[I(" Quantum "),s("span",{class:"text-primary-600"},"Analytics")]),s("p",{class:"text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-1"},[s("i",{class:"pi pi-verified text-primary-500 mr-1"}),I(" Data Studio Reporting v11.5 ")])],-1)),Re={key:0,class:"flex gap-3 animate-in fade-in zoom-in duration-300"},Le={class:"flex bg-white rounded-xl shadow-sm border border-slate-200 p-1"},Ie={class:"mb-12 max-w-7xl mx-auto"},ze={class:"p-2"},je=["onClick"],qe=k(()=>s("div",{class:"icon-box"},[s("i",{class:"pi pi-chart-line text-xl"})],-1)),Ne={class:"content"},Ve={class:"title"},Fe={class:"subtitle"},Oe={class:"viewport-container flex flex-col items-center"},Qe={class:"flex items-center gap-8 mb-8 bg-white/90 backdrop-blur-xl border border-white px-6 py-3 rounded-2xl shadow-xl shadow-slate-200/50"},Me={class:"flex flex-col items-center"},Ue=k(()=>s("span",{class:"text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]"},"Document",-1)),He={class:"text-sm font-bold text-slate-800"},Ge={key:1,class:"w-full h-full"},Je=["src"],Ze={key:2,class:"w-full h-full flex-grow overflow-hidden"},we={class:"w-full text-left border-collapse"},Ke={class:"text-slate-700 text-xs"},Xe={key:3,class:"w-full h-full p-2 flex-grow"},Ye={key:4,class:"w-full h-full flex flex-col justify-center px-4"},et={class:"text-[9px] font-black text-slate-400 uppercase tracking-widest text-center mb-1"},tt={class:"flex items-baseline gap-1 justify-center"},st={key:0,class:"text-[10px] font-bold text-emerald-500"},nt={key:5,class:"w-full h-full pointer-events-none"},ot=["viewBox"],rt=["y1","x2","y2","stroke","stroke-width"],at=["fill","stroke","stroke-width"],it=["cx","cy","r","fill","stroke","stroke-width"],lt={key:6,class:"absolute inset-0 bg-white/40 backdrop-blur-[1px] flex items-center justify-center"},ct={key:1,class:"flex flex-col items-center justify-center min-h-[400px] text-center bg-white rounded-3xl border-2 border-dashed border-slate-200 w-full max-w-2xl mx-auto shadow-inner"},pt=k(()=>s("div",{class:"w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6"},[s("i",{class:"pi pi-clone text-4xl text-slate-200"})],-1)),dt=k(()=>s("h3",{class:"text-slate-400 font-black uppercase tracking-[0.3em] text-sm"},"Prêt pour l'analyse",-1)),ut=k(()=>s("p",{class:"text-slate-400 text-xs mt-2"},"Sélectionnez un modèle Quantum Omni pour commencer",-1)),ft=[pt,dt,ut],mt={__name:"Analytics",props:{reportTemplates:{type:Array,default:()=>[]}},setup(i){le.register(ce,pe,de,ue,fe,me,he,ge,ye,xe);const m=G(),x=J(),b=C(!1),v=C(null),c=C([]),h=C(0),P=t=>{var r;return((r=v.value)==null?void 0:r.id)===t},q=async t=>{if(!P(t.id)){b.value=!0,v.value=t;try{await new Promise(n=>setTimeout(n,400));const r=typeof t.content=="string"?JSON.parse(t.content):t.content;c.value=r,h.value=0,await B()}catch(r){console.error(r),m.add({severity:"error",summary:"Erreur",detail:"Modèle corrompu."}),c.value=[]}finally{b.value=!1}}},B=async()=>{if(!c.value.length)return;const t=[];c.value.forEach(r=>{r.widgets.forEach(n=>{var e;if(["chart","kpi","table"].includes(n.type)){n.isSyncing=!0;const d={type:n.type,config:{sources:n.dataSources||n.dataSource||null,timeScale:((e=n.config)==null?void 0:e.timeScale)||"days",chartType:n.chartType||null}},g=L.post(route("quantum.query"),d).then(l=>{(n.type==="chart"||n.type==="table")&&(n.data=l.data),n.type==="kpi"&&(n.config.value=l.data.value,l.data.trend&&(n.config.trend=l.data.trend))}).catch(l=>console.error("Sync Error:",l)).finally(()=>n.isSyncing=!1);t.push(g)}})}),await Promise.all(t)},N=async()=>{b.value=!0,m.add({severity:"info",summary:"Export",detail:"Préparation du rendu HD..."});const t=document.getElementById("report-canvas"),r=t.style.transform;try{const n=new ne("p","mm","a4");for(let e=0;e<c.value.length;e++){h.value=e,await Y(),await new Promise(l=>setTimeout(l,800)),t.style.transform="scale(1)";const g=(await oe(t,{scale:3,useCORS:!0,backgroundColor:null})).toDataURL("image/png");e>0&&n.addPage(),n.addImage(g,"PNG",0,0,210,297),t.style.transform=r}n.save(`Rapport_${v.value.name.replace(/\s+/g,"_")}.pdf`),m.add({severity:"success",summary:"Succès",detail:"Le PDF est prêt."})}catch{m.add({severity:"error",summary:"Erreur Export",detail:"Échec de la capture."})}finally{t.style.transform=r,b.value=!1}},V=()=>{x.require({message:"Envoyer ce rapport actualisé par email à votre adresse ?",header:"Diffusion Quantum",icon:"pi pi-envelope",accept:async()=>{try{await L.post(route("quantum.share"),{template_id:v.value.id,pages:c.value}),m.add({severity:"success",summary:"Envoyé",detail:"Email en cours d'envoi."})}catch{m.add({severity:"error",summary:"Erreur",detail:"Échec de l'envoi."})}}})},F=Z(()=>{const t=c.value[h.value];if(!t)return{};const r=t.format||{w:794,h:1123},n=t.orientation==="landscape";return{width:(n?r.h:r.w)+"px",height:(n?r.w:r.h)+"px",backgroundColor:t.background||"#ffffff",transform:"scale(0.75)",transformOrigin:"top center",transition:"all 0.3s ease"}}),O=t=>{var r,n,e,d,g,l;return{position:"absolute",left:t.x+"px",top:t.y+"px",width:t.w+"px",height:t.h+"px",zIndex:((r=t.style)==null?void 0:r.zIndex)||10,backgroundColor:((n=t.style)==null?void 0:n.backgroundColor)||"transparent",borderRadius:(((e=t.style)==null?void 0:e.borderRadius)||0)+"px",border:((d=t.style)==null?void 0:d.borderWidth)>0?`${t.style.borderWidth}px ${t.style.borderStyle||"solid"} ${t.style.borderColor||"#000"}`:"none",transform:`rotate(${((g=t.style)==null?void 0:g.rotation)||0}deg)`,padding:(((l=t.style)==null?void 0:l.padding)||0)+"px",overflow:"hidden",display:"flex",flexDirection:"column"}},Q=t=>({bar:z,line:ve,pie:be,doughnut:ke,radar:_e})[t]||z,M={responsive:!0,maintainAspectRatio:!1,animation:{duration:0},plugins:{legend:{position:"bottom",labels:{boxWidth:10,font:{size:10,weight:"bold"}}}}};return(t,r)=>{const n=U;return o(),W(se,null,{default:D(()=>[f(y(w),{title:"Quantum Analytics"}),f(y(ie)),s("div",Be,[s("header",We,[Ae,v.value?(o(),a("div",Re,[f(y(S),{icon:"pi pi-refresh",onClick:B,loading:b.value,label:"Actualiser",class:"p-button-sm p-button-outlined p-button-secondary bg-white font-bold"},null,8,["loading"]),s("div",Le,[A(f(y(S),{icon:"pi pi-file-pdf",onClick:N,class:"p-button-text p-button-sm p-button-secondary"},null,512),[[n,"Exporter en PDF HD",void 0,{bottom:!0}]]),A(f(y(S),{icon:"pi pi-envelope",onClick:V,class:"p-button-text p-button-sm p-button-secondary"},null,512),[[n,"Diffuser par Email",void 0,{bottom:!0}]])])])):p("",!0)]),s("section",Ie,[f(y(re),{value:i.reportTemplates,numVisible:4,numScroll:1,circular:!1,class:"custom-carousel"},{item:D(e=>[s("div",ze,[s("div",{onClick:d=>q(e.data),class:R(["card-v11 cursor-pointer transition-all duration-300",P(e.data.id)?"active":""])},[qe,s("div",Ne,[s("span",Ve,u(e.data.name),1),s("span",Fe,u(e.data.updated_at||"Template Standard"),1)])],10,je)])]),_:1},8,["value"])]),s("div",Oe,[f(X,{name:"quantum-fade",mode:"out-in"},{default:D(()=>[v.value&&c.value.length>0?(o(),a("div",{key:v.value.id,class:"flex flex-col items-center"},[s("div",Qe,[f(y(S),{icon:"pi pi-chevron-left",onClick:r[0]||(r[0]=e=>h.value--),disabled:h.value===0,class:"p-button-rounded p-button-text p-button-sm"},null,8,["disabled"]),s("div",Me,[Ue,s("span",He,"Page "+u(h.value+1)+" sur "+u(c.value.length),1)]),f(y(S),{icon:"pi pi-chevron-right",onClick:r[1]||(r[1]=e=>h.value++),disabled:h.value===c.value.length-1,class:"p-button-rounded p-button-text p-button-sm"},null,8,["disabled"])]),s("div",{id:"report-canvas",style:_(F.value),class:"relative bg-white shadow-[0_50px_100px_rgba(0,0,0,0.1)] overflow-hidden"},[(o(!0),a(T,null,E(c.value[h.value].widgets,e=>(o(),a("div",{key:e.id,style:_(O(e))},[e.type==="text"?(o(),a("div",{key:0,style:_({fontFamily:e.config.font,fontSize:e.config.size+"px",color:e.config.color,textAlign:e.config.align,fontWeight:e.config.weight,textTransform:e.config.uppercase?"uppercase":"none",fontStyle:e.config.italic?"italic":"normal",textDecoration:e.config.underline?"underline":"none"})},u(e.content),5)):p("",!0),e.type==="image"?(o(),a("div",Ge,[s("img",{src:e.imageUrl||e.content,alt:"Widget",class:"w-full h-full object-cover"},null,8,Je)])):p("",!0),e.type==="table"?(o(),a("div",Ze,[s("table",we,[s("thead",{style:_({background:e.config.headerBg||"#1e293b",color:e.config.headerColor||"#ffffff"})},[s("tr",null,[(o(!0),a(T,null,E(e.data.columns,d=>(o(),a("th",{key:d,class:"p-2 text-[10px] font-black uppercase"},u(d),1))),128))])],4),s("tbody",Ke,[(o(!0),a(T,null,E(e.data.rows,(d,g)=>(o(),a("tr",{key:g,class:R(e.config.striped&&g%2===0?"bg-slate-50":"bg-white")},[(o(!0),a(T,null,E(e.data.columns,l=>(o(),a("td",{key:l,class:"p-2 border-b border-slate-100"},u(d[l]),1))),128))],2))),128))])])])):p("",!0),e.type==="chart"?(o(),a("div",Xe,[(o(),W(K(Q(e.chartType)),{data:e.data,options:M},null,8,["data"]))])):p("",!0),e.type==="kpi"?(o(),a("div",Ye,[s("span",et,u(e.config.label),1),s("div",tt,[s("span",{class:"text-3xl font-black tracking-tighter",style:_({color:e.config.color})},u(e.config.prefix)+u(e.config.value),5),e.config.trend?(o(),a("span",st,u(e.config.trend),1)):p("",!0)])])):p("",!0),e.type==="shape"?(o(),a("div",nt,[(o(),a("svg",{width:"100%",height:"100%",viewBox:`0 0 ${e.w} ${e.h}`,preserveAspectRatio:"none"},[e.config.shapeType==="line"?(o(),a("line",{key:0,x1:"0",y1:e.config.strokeWidth/2,x2:e.w,y2:e.config.strokeWidth/2,stroke:e.config.strokeColor,"stroke-width":e.config.strokeWidth},null,8,rt)):p("",!0),e.config.shapeType==="rectangle"?(o(),a("rect",{key:1,x:"0",y:"0",width:"100%",height:"100%",fill:e.style.backgroundColor,stroke:e.config.strokeColor,"stroke-width":e.config.strokeWidth},null,8,at)):p("",!0),e.config.shapeType==="circle"?(o(),a("circle",{key:2,cx:e.w/2,cy:e.h/2,r:Math.min(e.w,e.h)/2-e.config.strokeWidth/2,fill:e.style.backgroundColor,stroke:e.config.strokeColor,"stroke-width":e.config.strokeWidth},null,8,it)):p("",!0)],8,ot))])):p("",!0),e.isSyncing?(o(),a("div",lt,[f(y(j),{style:{width:"24px",height:"24px"},strokeWidth:"6"})])):p("",!0)],4))),128))],4)])):(o(),a("div",ct,ft))]),_:1})])])]),_:1})}}},qt=Se(mt,[["__scopeId","data-v-e857986c"]]);export{qt as default};
