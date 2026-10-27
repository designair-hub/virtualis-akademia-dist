import{T as ae,p as ne,i as se,t as oe}from"./Transition-CZ3wW0iy.js";import{g as Y,d as ie,e as L,s as le,n as R,b as re,i as de}from"./profileCard-Dt9N8Dl-.js";const ce={completed:"✓",locked:"🔒"},ue={completed:"kész modul",available:"aktuális feladat",locked:"zárolva"};function E(t,e,a){const n=document.createElement(t);return e&&(n.className=e),a!==void 0&&(n.textContent=a),n}function O(t){t.style.setProperty("--chip-shift","0px");const e=t.getBoundingClientRect(),a=10;let n=0;e.left<a?n=a-e.left:e.right>window.innerWidth-a&&(n=window.innerWidth-a-e.right),n&&t.style.setProperty("--chip-shift",`${n}px`)}function pe(t){const e=E("div","va-hotspot__node");return t.status==="available"?e.textContent=String(t.moduleNumber??""):t.status==="completed"||t.status==="locked"?e.textContent=ce[t.status]:t.kind==="door"&&(e.textContent="🚪"),e}function me(t,e){t.classList.add("va-hotspot",`va-hotspot--${e.kind}`),e.status&&t.classList.add(`va-hotspot--${e.status}`),e.isNextTask&&t.classList.add("va-hotspot--chip-open"),e.highlight&&(t.classList.add("va-hotspot--highlight"),t.addEventListener("animationend",()=>t.classList.remove("va-hotspot--highlight"),{once:!0}));const a=E("div","va-hotspot__pulsar");a.appendChild(E("div","va-hotspot__ring")),a.appendChild(E("div","va-hotspot__ring va-hotspot__ring--static")),a.appendChild(pe(e)),t.appendChild(a);let n;if(e.kind==="lesson"){const o=E("div","va-hotspot__leader");t.appendChild(o),n=E("div","va-hotspot__chip"),n.appendChild(E("span","va-hotspot__chip-eyebrow",ue[e.status]||"")),n.appendChild(E("span","va-hotspot__chip-title",e.title||"")),t.appendChild(n)}else n=E("div","va-hotspot__chip va-hotspot__chip--compact"),n.appendChild(E("span","va-hotspot__chip-title",e.title||"")),t.appendChild(n);t.addEventListener("mouseenter",()=>O(n)),window.addEventListener("resize",()=>O(n)),e.isNextTask&&requestAnimationFrame(()=>O(n))}function ve(t,e){if(t.classList.add("va-hotspot","va-hotspot--plaque","va-glass-panel"),e.imageUrl){const a=document.createElement("img");a.className="va-hotspot__plaque-image",a.src=e.imageUrl,a.alt=e.title||"",t.appendChild(a)}e.title&&t.appendChild(E("span","va-hotspot__plaque-title",e.title))}function X(t,e){if(e.kind==="plaque"){ve(t,e);return}me(t,e)}function he(t,e){const a=t.kind||"lesson";return{id:`${a}-${t.id??t.yaw}-${t.pitch}`,pitch:t.pitch,yaw:t.yaw,cssClass:`va-hotspot-wrapper va-hotspot-wrapper--${a}`,createTooltipFunc:n=>X(n,t),clickHandlerFunc:()=>e(t)}}function ge(t,e){return{id:`scene-${t.targetSceneId}`,pitch:t.pitch,yaw:t.yaw,cssClass:"va-hotspot-wrapper va-hotspot-wrapper--door",createTooltipFunc:a=>{X(a,{...t,kind:"door",status:null}),a.setAttribute("role","button"),a.setAttribute("tabindex","0"),a.setAttribute("aria-label",t.title||"Ajtó"),a.addEventListener("keydown",n=>{(n.key==="Enter"||n.key===" ")&&(n.preventDefault(),e(t))})},clickHandlerFunc:()=>e(t)}}const H=new Map,j={currentSceneId:null,muted:!1};function A(t,e){return H.has(t)||H.set(t,new Set),H.get(t).add(e),()=>{var a;return(a=H.get(t))==null?void 0:a.delete(e)}}function C(t,e){var a;(a=H.get(t))==null||a.forEach(n=>n(e))}function be(t){j.currentSceneId=t,C("scene-change",t)}function F(t){j.muted=t,C("mute-change",t)}const fe=100,Z=50,J=90;function K(t){const e=t.clientWidth/Math.max(t.clientHeight,1);if(e>=1)return null;const a=fe*Math.PI/180,o=2*Math.atan(Math.tan(a/2)*e)*180/Math.PI;return Math.min(J,Math.max(Z,o))}let U=null;function _e(){var t;if(U===null)try{const e=document.createElement("canvas").getContext("webgl");U=e?e.getParameter(e.MAX_TEXTURE_SIZE):0,(t=e==null?void 0:e.getExtension("WEBGL_lose_context"))==null||t.loseContext()}catch{U=0}return U}function Q(t){const e=_e();return t.panoramaFallbackUrl&&t.panoramaWidth&&e&&(t.panoramaWidth/2>e||t.panoramaHeight>e)?t.panoramaFallbackUrl:t.panoramaUrl}function ye(t,e,a,n){let o=0,s=!1;const l=(t.hotspots||[]).map(i=>{if(i.kind==="door")return ge(i,n);i.kind==="lesson"&&(o+=1);const d=i.status==="available"&&!s;return d&&(s=!0),he({...i,moduleNumber:i.kind==="lesson"?o:void 0,isNextTask:d,highlight:i.id===a},e)});return{type:"equirectangular",panorama:Q(t),...t.entryYaw!==void 0?{yaw:t.entryYaw,pitch:t.entryPitch}:{},hotSpots:l}}class ke{constructor(e,a,{onSceneChange:n,onSelectHotspot:o,overlayMount:s}){this.sceneData=a,this.onSceneChange=n,this.transition=new ae(s||document.body),this.busy=!1;const l=r=>this.goToScene(r.targetSceneId,{via:r}),i={};Object.entries(a.scenes).forEach(([r,b])=>{i[r]=ye(b,o,a.highlightHotspotId,l)});const d=K(e);this.pannellum=window.pannellum.viewer(e,{default:{firstScene:a.initialSceneId,autoLoad:!0,showFullscreenCtrl:!1,sceneFadeDuration:0,...d?{hfov:d,minHfov:Z,maxHfov:J}:{}},scenes:i}),this.ready=new Promise(r=>{this.pannellum.on("load",r),setTimeout(r,1e4)}),this.pannellum.on("scenechange",r=>this.handleSceneChange(r)),this.handleSceneChange(a.initialSceneId),this._container=e,this._handleOrientationChange=()=>{const r=K(e);this.pannellum.setHfov(r??100)},window.addEventListener("resize",this._handleOrientationChange),this.pannellum.on("load",()=>this.prefetchNeighbours()),window.addEventListener("pageshow",r=>{r.persisted&&(this.busy=!1)})}lessonLabel(e){var l;const a=this.sceneData.scenes[this.getCurrentSceneId()],o=((a==null?void 0:a.hotspots)||[]).filter(i=>i.kind==="lesson").findIndex(i=>i.id===(e==null?void 0:e.id)),s=((l=a==null?void 0:a.department)==null?void 0:l.label)||(a==null?void 0:a.title)||"";return{eyebrow:[o>=0?`${String(o+1).padStart(2,"0")}. lecke`:"Belépés a leckébe",s].filter(Boolean).join(" · "),title:(e==null?void 0:e.title)||""}}leaveTo(e,{hotspot:a,label:n,origin:o}={}){var i,d;if(this.busy||!e)return;this.busy=!0,C("transition-start",null);const s=this.pannellum.getHfov(),l=a&&!((d=(i=this.pannellum).isOrientationActive)!=null&&d.call(i))?()=>new Promise(r=>{this.pannellum.lookAt(a.pitch,a.yaw,Math.max(50,s*.7),700,r),setTimeout(r,850)}):null;this.transition.leave({url:e,label:n||this.lessonLabel(a),origin:l?null:o,focus:l,stage:this._container})}arrive(){const e=this.getCurrentSceneId(),a=this.transitionLabel(e);return a.eyebrow=e==="corridor"?"Vissza az Átriumba":"Vissza a terembe",C("transition-start",e),this.transition.intro(a,this.ready,450,this._container).finally(()=>C("transition-end",e))}prefetchNeighbours(){var o;if((o=navigator.connection)!=null&&o.saveData)return;const e=this.sceneData.scenes[this.getCurrentSceneId()],a=((e==null?void 0:e.hotspots)||[]).filter(s=>s.kind==="door").map(s=>this.sceneData.scenes[s.targetSceneId]).filter(Boolean).map(Q).filter(Boolean);this._prefetched=this._prefetched||new Set;const n=()=>{a.forEach(s=>{if(this._prefetched.has(s))return;this._prefetched.add(s);const l=new Image;l.crossOrigin="anonymous",l.src=s})};(window.requestIdleCallback||(s=>setTimeout(s,1200)))(n)}lookAtHotspot(e){var a,n;this.busy||!e||(n=(a=this.pannellum).isOrientationActive)!=null&&n.call(a)||this.pannellum.lookAt(e.pitch,e.yaw,this.pannellum.getHfov(),ne()?!1:700)}greet(e){const a=e.firstName?`, ${e.firstName}`:"",n=e.type==="new";return C("transition-start",this.getCurrentSceneId()),this.transition.intro({eyebrow:n?"Üdv a Virtuális Akadémián":"Üdv újra az Akadémián",title:`Szia${a}!`,text:n?["Örülünk, hogy látunk!"]:["Örülünk, hogy újra látunk!","Folytasd a tanulást ott, ahol abbahagytad."]},this.ready,n?1500:2e3,this._container).finally(()=>C("transition-end",this.getCurrentSceneId()))}transitionLabel(e){const a=this.sceneData.scenes[e];if(e==="corridor")return{eyebrow:"Vissza",title:(a==null?void 0:a.title)||"Átrium",meta:""};const n=((a==null?void 0:a.hotspots)||[]).filter(l=>l.kind==="lesson"),o=n.filter(l=>l.status==="completed").length,s=a==null?void 0:a.department;return{eyebrow:s?`Belépés · ${String(s.number).padStart(2,"0")}. tanszék`:"Belépés",title:(a==null?void 0:a.title)||"",meta:n.length?`${o} / ${n.length} lecke kész`:""}}handleSceneChange(e){var a;be(e),(a=this.onSceneChange)==null||a.call(this,this.sceneData.scenes[e],e)}getCurrentSceneId(){return this.pannellum.getScene()}goToScene(e,{via:a}={}){var v;const n=this.getCurrentSceneId();if(this.busy||n===e||!this.sceneData.scenes[e])return;const o=e==="corridor"?this.sceneData.scenes[n]:this.sceneData.scenes.corridor,s=((o==null?void 0:o.hotspots)||[]).find(p=>p.kind==="door"&&p.targetSceneId===e),l=a||(((v=this.sceneData.scenes[n])==null?void 0:v.hotspots)||[]).find(p=>p.kind==="door"&&p.targetSceneId===e),i=(s==null?void 0:s.targetPitch)??0,d=(s==null?void 0:s.targetYaw)??0,r=this.pannellum.getHfov(),b=this._container;this.busy=!0,C("transition-start",e);const u=()=>new Promise(p=>{var m,c;if(!l||(c=(m=this.pannellum).isOrientationActive)!=null&&c.call(m)){p();return}this.pannellum.lookAt(l.pitch,l.yaw,Math.max(50,r*.7),750,p),setTimeout(p,900)}),h=()=>new Promise(p=>{const m=()=>{var g,k;clearTimeout(c),(k=(g=this.pannellum).off)==null||k.call(g,"load",m),requestAnimationFrame(()=>requestAnimationFrame(p))},c=setTimeout(m,15e3);this.pannellum.on("load",m),this.pannellum.loadScene(e,i,d,r)});this.transition.run({label:this.transitionLabel(e),focus:u,swap:h,stage:b}).finally(()=>{this.busy=!1,C("transition-end",e)})}goToCorridor(){this.goToScene("corridor")}isOrientationSupported(){return this.pannellum.isOrientationSupported()}isOrientationActive(){return this.pannellum.isOrientationActive()}toggleOrientation(){this.pannellum.isOrientationActive()?this.pannellum.stopOrientation():this.pannellum.startOrientation()}}const V="va-audio-muted";class we{constructor(){this.audio=document.createElement("audio"),this.audio.loop=!0,this.audio.preload="none",document.body.appendChild(this.audio),this.unlocked=!1,this.pendingUrl=null,F(this.readStoredMute()),this.audio.muted=j.muted,document.addEventListener("click",()=>this.unlock(),{once:!0})}readStoredMute(){try{return window.localStorage.getItem(V)==="1"}catch{return!1}}writeStoredMute(e){try{window.localStorage.setItem(V,e?"1":"0")}catch{}}unlock(){this.unlocked=!0,this.pendingUrl&&(this.play(this.pendingUrl),this.pendingUrl=null)}play(e){if(!e){this.audio.pause(),this.audio.removeAttribute("src");return}if(!this.unlocked){this.pendingUrl=e;return}this.audio.src!==e&&(this.audio.src=e,this.audio.play().catch(()=>{}))}toggleMute(){const e=!j.muted;F(e),this.audio.muted=e,this.writeStoredMute(e)}}function Se(t,e){const a=document.createElement("button");a.type="button",a.className="va-hud-button va-hud-button--mute";const n=()=>{a.textContent=j.muted?"🔇":"🔊",a.setAttribute("aria-label",j.muted?"Hang bekapcsolása":"Hang némítása")};a.addEventListener("click",()=>e.toggleMute()),A("mute-change",n),n(),t.appendChild(a)}function xe(t,e){const a=document.createElement("div");a.className="va-hud-divider",t.appendChild(a);const n=document.createElement("button");n.type="button",n.className="va-hud-button va-hud-button--help",n.textContent="❓",n.setAttribute("aria-label","Súgó"),n.addEventListener("click",()=>e.show()),t.appendChild(n)}function Ee(t,e){const a=document.createElement("div");a.className="va-hud-divider",t.appendChild(a);const n=document.createElement("button");n.type="button",n.className="va-hud-button va-hud-button--fullscreen";const o=()=>{const s=document.fullscreenElement===e;n.textContent=s?"⤢":"⛶",n.setAttribute("aria-label",s?"Teljes képernyő bezárása":"Teljes képernyő")};n.addEventListener("click",()=>{var s;document.fullscreenElement?document.exitFullscreen():(s=e.requestFullscreen)==null||s.call(e)}),document.addEventListener("fullscreenchange",o),o(),t.appendChild(n)}function Le(t,e){if(!e.isOrientationSupported())return;const a=document.createElement("div");a.className="va-hud-divider",t.appendChild(a);const n=document.createElement("button");n.type="button",n.className="va-hud-button va-hud-button--gyro";const o=()=>{const s=e.isOrientationActive();n.classList.toggle("is-active",s),n.textContent="🧭",n.setAttribute("aria-label",s?"Giroszkópos nézet kikapcsolása":"Giroszkópos nézet bekapcsolása")};n.addEventListener("click",()=>{e.toggleOrientation(),o()}),o(),t.appendChild(n)}function $e(t,e,a,n){const o=document.createElement("div");o.className="va-hud-divider",t.appendChild(o);const s=document.createElement("button");s.type="button",s.className="va-hud-button va-hud-button--eu-info",s.textContent="🇪🇺",s.setAttribute("aria-label","A projektről"),s.addEventListener("click",()=>{a.open(n?{title:"A projektről",project:n}:{badge:"🇪🇺",title:"A projektről",text:e})}),t.appendChild(s)}function Ce(t,e,a){if(!e||!e.length)return;const n=document.createElement("div");n.className="va-hud-divider",t.appendChild(n);const o=document.createElement("button");o.type="button",o.className="va-hud-button va-hud-button--partners",o.textContent="🤝",o.setAttribute("aria-label","Partnereink"),o.addEventListener("click",()=>{a.open({badge:"🤝",title:"Partnereink",items:e.map(s=>({title:s.name,imageUrl:s.logo,link:s.url}))})}),t.appendChild(o)}class ze{constructor(e,a=document.body,{deferred:n=!1}={}){this.sceneData=e,this.overlay=this.buildOverlay(),a.appendChild(this.overlay),n||this.showIfUnseen()}showIfUnseen(){this.sceneData.onboardingSeen||this.show()}buildOverlay(){const e=document.createElement("div");return e.className="va-onboarding",e.innerHTML=`
			<div class="va-onboarding__panel va-glass-panel" role="dialog" aria-modal="true" aria-labelledby="va-onboarding-title">
				<div class="va-onboarding__grid-bg"></div>
				<span class="va-onboarding__corner va-onboarding__corner--tl"></span>
				<span class="va-onboarding__corner va-onboarding__corner--tr"></span>
				<span class="va-onboarding__corner va-onboarding__corner--bl"></span>
				<span class="va-onboarding__corner va-onboarding__corner--br"></span>
				<div class="va-onboarding__head">
					<div>
						<span class="va-label-caps va-onboarding__eyebrow">
							<span class="va-onboarding__eyebrow-dot"></span>
							<span data-role="eyebrow">Eligazítás · 1 / 2</span>
						</span>
						<h2 class="va-onboarding__title" id="va-onboarding-title" data-role="title">Eligazodás az Akadémián</h2>
					</div>
					<div class="va-onboarding__badge" data-role="badge" aria-hidden="true">360°</div>
				</div>
				<div class="va-onboarding__divider"></div>
				<div class="va-onboarding__step" data-step="1">
					<div class="va-onboarding__instructions">
						<div class="va-onboarding__instruction">
							<div class="va-onboarding__instruction-icon" aria-hidden="true">🖱️</div>
							<div>
								<h3>Körülnézés</h3>
								<p>Kattints és húzd (mobilon: húzd az ujjaddal) a 3D panorámán, hogy felfedezd a tanszékeket.</p>
							</div>
						</div>
						<div class="va-onboarding__instruction">
							<div class="va-onboarding__instruction-icon" aria-hidden="true">✨</div>
							<div>
								<h3>Leckepontok</h3>
								<p>A pulzáló, számozott arany pont a következő leckéd. Koppints vagy kattints rá a megnyitáshoz.</p>
							</div>
						</div>
						<div class="va-onboarding__instruction">
							<div class="va-onboarding__instruction-icon" aria-hidden="true">🧭</div>
							<div>
								<h3>Tanszéki panel és ajtók</h3>
								<p>A panelen látod, hol tartasz, és egy gombbal folytathatod. Másik terembe az ajtókon, a panel listáján vagy a térképen jutsz át.</p>
							</div>
						</div>
						<div class="va-onboarding__instruction">
							<div class="va-onboarding__instruction-icon" aria-hidden="true">🎖️</div>
							<div>
								<h3>Útlevél</h3>
								<p>Minden elvégzett tanszékért pecsét jár. A jobb felső <strong>Útlevél</strong> gombbal bármikor megnézheted, hol tartasz.</p>
							</div>
						</div>
					</div>
				</div>
				<div class="va-onboarding__step" data-step="2" hidden>
					<p class="va-onboarding__lead">Az Útleveledben minden tanszéknek saját pecséthelye van. Gyűjtsd össze mind a négyet!</p>
					<div data-role="guide"></div>
				</div>
				<div class="va-onboarding__actions">
					<span class="va-onboarding__dots" aria-hidden="true"><span class="is-active"></span><span></span></span>
					<button type="button" class="va-onboarding__back" data-action="back" hidden>← Vissza</button>
					<button type="button" class="va-onboarding__button" data-action="next">
						<span data-role="next-label">Tovább: pecsétgyűjtés</span>
						<span aria-hidden="true" data-role="next-icon">→</span>
					</button>
				</div>
			</div>
		`,e.querySelector('[data-role="guide"]').innerHTML=Y(ie(this.sceneData)),e.addEventListener("click",a=>{var o;const n=(o=a.target.closest("[data-action]"))==null?void 0:o.dataset.action;n==="back"?this.setStep(1):n==="next"&&(this.step===1?this.setStep(2):this.acknowledge())}),e.addEventListener("keydown",a=>{a.key==="Escape"&&this.acknowledge()}),e}setStep(e){const a=this.overlay,n=e===2;this.step=e,a.querySelectorAll(".va-onboarding__step").forEach(o=>{o.hidden=Number(o.dataset.step)!==e}),a.querySelector('[data-role="eyebrow"]').textContent=n?"Útlevél · 2 / 2":"Eligazítás · 1 / 2",a.querySelector('[data-role="title"]').textContent=n?"Pecsétgyűjtő játék":"Eligazodás az Akadémián",a.querySelector('[data-role="badge"]').textContent=n?"🎖️":"360°",a.querySelector('[data-role="next-label"]').textContent=n?"Megértettem, kezdjük":"Tovább: pecsétgyűjtés",a.querySelector('[data-role="next-icon"]').textContent=n?"🚀":"→",a.querySelector('[data-action="back"]').hidden=!n,a.querySelectorAll(".va-onboarding__dots span").forEach((o,s)=>{o.classList.toggle("is-active",s===e-1)}),a.querySelector(".va-onboarding__panel").scrollTop=0}show(e=1){this.setStep(e),this.overlay.classList.add("is-visible"),this.overlay.querySelector('[data-action="next"]').focus({preventScroll:!0})}hide(){this.overlay.classList.remove("is-visible")}acknowledge(){if(this.hide(),this.sceneData.onboardingSeen)return;this.sceneData.onboardingSeen=!0;const e=`${this.sceneData.restUrl}/onboarding/ack?_wpnonce=${encodeURIComponent(this.sceneData.restNonce)}`;if(navigator.sendBeacon){navigator.sendBeacon(e,new Blob([],{type:"application/x-www-form-urlencoded"}));return}fetch(e,{method:"POST"}).catch(()=>{})}}const I={x:50,y:50},ee="va-minimap-open";function Te(){try{return window.localStorage.getItem(ee)==="1"}catch{return!1}}function Me(t){try{window.localStorage.setItem(ee,t?"1":"0")}catch{}}function Ae(t,e,a){const n=document.createElement("div");n.className="va-minimap__screen",n.id="va-minimap-screen",t.appendChild(n);const o=document.createElementNS("http://www.w3.org/2000/svg","svg");o.setAttribute("viewBox","0 0 100 100"),o.classList.add("va-minimap__svg");const s=document.createElement("div");s.className="va-minimap__tooltip",t.appendChild(s);const l={},i="http://www.w3.org/2000/svg",d=(c,g)=>{const k=document.createElementNS(i,c);return Object.entries(g).forEach(([_,f])=>k.setAttribute(_,f)),k},r={w:28,h:22},b=18,u=(c,g,k)=>{var q;const _=c==="corridor",f=d("g",{class:`va-minimap__node ${_?"va-minimap__node--atrium":"va-minimap__node--room"}`}),x=_?b:r.w,y=_?b:r.h;f.appendChild(d("rect",{class:"va-minimap__room",x:g.x-x/2,y:g.y-y/2,width:x,height:y,rx:_?3:2}));const w=e.scenes[c],z=((w==null?void 0:w.hotspots)||[]).filter($=>$.kind==="lesson"),T=z.length>0&&z.every($=>$.status==="completed");if(_)f.appendChild(d("rect",{class:"va-minimap__atrium-core",x:g.x-3.5,y:g.y-3.5,width:7,height:7,transform:`rotate(45 ${g.x} ${g.y})`}));else{const $=d("text",{class:"va-minimap__room-label",x:g.x,y:g.y+.5});if($.textContent=String(((q=w==null?void 0:w.department)==null?void 0:q.number)||"").padStart(2,"0"),f.appendChild($),T){f.classList.add("is-complete");const B=d("text",{class:"va-minimap__room-check",x:g.x+x/2-4,y:g.y-y/2+5});B.textContent="✓",f.appendChild(B)}}if(o.appendChild(f),l[c]=f,!k)return;const M=`${k}${T?" (kész)":""}`;f.appendChild(d("title",{})).textContent=M,f.addEventListener("mouseenter",()=>{s.textContent=M,s.classList.add("is-visible")}),f.addEventListener("mouseleave",()=>{s.classList.remove("is-visible")}),a&&(f.classList.add("is-clickable"),f.setAttribute("role","button"),f.setAttribute("tabindex","0"),f.setAttribute("aria-label",M),f.addEventListener("click",()=>a(c)),f.addEventListener("keydown",$=>{($.key==="Enter"||$.key===" ")&&($.preventDefault(),a(c))}))};Object.values(e.minimap||{}).forEach(c=>{o.appendChild(d("line",{class:"va-minimap__path",x1:I.x,y1:I.y,x2:c.x,y2:c.y}))}),u("corridor",I,"Átrium"),Object.entries(e.minimap||{}).forEach(([c,g])=>{u(c,g,g.title)});const h=c=>{Object.entries(l).forEach(([g,k])=>{k.classList.toggle("is-active",g===c)})};A("scene-change",h),h(e.initialSceneId),n.appendChild(o);const v=document.createElement("button");v.type="button",v.className="va-minimap__toggle",v.setAttribute("aria-controls","va-minimap-screen"),v.innerHTML=`
		<svg class="va-minimap__toggle-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Zm0 2.2 6 2v11.6l-6-2V6.2Z" fill="currentColor"/></svg>
		<span class="va-minimap__toggle-label">Térkép</span>
		<span class="va-minimap__toggle-chevron" aria-hidden="true"></span>
	`,t.appendChild(v);const p=t.closest(".va-metaverse-root"),m=(c,g=!0)=>{t.classList.toggle("is-open",c),p==null||p.classList.toggle("is-map-open",c),v.setAttribute("aria-expanded",String(c)),v.setAttribute("aria-label",c?"Térkép bezárása":"Térkép megnyitása"),n.hidden=!c,g&&Me(c)};v.addEventListener("click",()=>m(!t.classList.contains("is-open"))),m(Te(),!1)}function je(){const t=document.createElement("div");return t.className="va-passport-modal",t.innerHTML=`
		<div class="va-passport-modal__panel va-glass-panel" role="dialog" aria-modal="true" aria-labelledby="va-passport-title" tabindex="-1">
			<button type="button" class="va-passport-modal__close" aria-label="Bezárás">✕</button>
			<div class="va-passport-modal__header">
				<span class="va-passport-modal__badge" aria-hidden="true">🎖️</span>
				<div>
					<span class="va-label-caps va-passport-modal__eyebrow">Pecsétgyűjtő játék</span>
					<h2 id="va-passport-title">Útlevél</h2>
				</div>
			</div>
			<div class="va-passport-modal__progress">
				<span class="va-label-caps">Gyűjtött pecsétek</span>
				<span class="va-passport-modal__progress-count" data-role="count">–</span>
				<span class="va-passport-modal__progress-bar" aria-hidden="true"><span data-role="bar"></span></span>
			</div>
			<details class="va-passport-modal__guide" data-role="guide">
				<summary>
					<span class="va-passport-modal__guide-icon" aria-hidden="true">?</span>
					<span>Hogyan működik a pecsétgyűjtés?</span>
					<span class="va-passport-modal__guide-chevron" aria-hidden="true"></span>
				</summary>
				<div data-role="guide-body"></div>
			</details>
			<div class="va-passport-modal__stamps" data-role="stamps"></div>
			<div class="va-passport-modal__master" data-role="master"></div>
			<p class="va-passport-modal__note" data-role="note" hidden>Az okleveleket a regisztrált e-mail-címedre is elküldtük PDF-mellékletként.</p>
		</div>
	`,t}function qe(t,e,{forceGuide:a}){const n=e.stamps||[],o=n.filter(l=>l.stamped).length;t.querySelector('[data-role="count"]').textContent=`${o} / ${n.length}`,t.querySelector('[data-role="bar"]').style.width=`${n.length?o/n.length*100:0}%`,t.querySelector('[data-role="guide-body"]').innerHTML=Y(n.map(l=>({number:l.number,label:l.label,icon:l.icon,total:l.lessonsTotal})),{withMap:!1}),t.querySelector('[data-role="guide"]').open=a||o===0,t.querySelector('[data-role="stamps"]').innerHTML=n.map(l=>{const i=String(l.number).padStart(2,"0"),d=l.lessonsTotal?`${l.lessonsDone} / ${l.lessonsTotal} lecke`:"",r=l.stamped?l.certificateUrl?`<a class="va-passport-stamp__action is-gold" href="${L(l.certificateUrl)}" download>Oklevél letöltése</a>`:"":l.sceneId?`<button type="button" class="va-passport-stamp__action" data-room="${L(l.sceneId)}">${l.lessonsDone?"Folytatás":"Irány a tanszék"} <span aria-hidden="true">→</span></button>`:"";return`
				<div class="va-passport-stamp ${l.stamped?"is-stamped":"is-empty"}">
					<span class="va-passport-stamp__dept">[ ${i}. hely ]</span>
					${l.stamped?'<span class="va-passport-stamp__check" aria-hidden="true">✓</span>':""}
					${le(l.icon,l.stamped?"stamped":"empty")}
					<span class="va-passport-stamp__label">${L(l.label)}</span>
					<span class="va-passport-stamp__status">${l.stamped?"Pecsét megszerezve":L(d||"Még nincs meg")}</span>
					${r}
				</div>`}).join("");const s=t.querySelector('[data-role="master"]');if(e.allStamped&&e.masterCertificateUrl)s.innerHTML=`<a class="va-passport-modal__master-link" href="${L(e.masterCertificateUrl)}" download>🏅 Mester Oklevél letöltése</a>`;else{const l=n.length-o;s.innerHTML=`<p>Gyűjtsd össze mind a ${R(n.length)} pecsétet a <strong>Mester Oklevélért</strong>! Még ${R(l)} hiányzik.</p>`}t.querySelector('[data-role="note"]').hidden=!o}function Ne(t,e=document.body,{onGoToRoom:a}={}){const n=je();e.appendChild(n);let o=null;const s=()=>{var i;n.classList.remove("is-visible"),(i=o==null?void 0:o.focus)==null||i.call(o)};n.addEventListener("click",i=>{if(i.target===n||i.target.closest(".va-passport-modal__close")){s();return}const d=i.target.closest("[data-room]");d&&a&&(s(),a(d.dataset.room))}),n.addEventListener("keydown",i=>{i.key==="Escape"&&s()});const l=async({guide:i=!1}={})=>{o=document.activeElement,n.classList.add("is-visible"),n.querySelector(".va-passport-modal__panel").focus({preventScroll:!0});try{const r=await(await fetch(`${t.restUrl}/passport`,{headers:{"X-WP-Nonce":t.restNonce}})).json();qe(n,r,{forceGuide:i}),n.querySelector(".va-passport-modal__panel").scrollTop=0}catch{n.querySelector('[data-role="stamps"]').textContent="Az útlevél adatai jelenleg nem érhetők el."}};return{open:i=>l(i&&typeof i=="object"&&!(i instanceof Event)?i:{})}}function He(){const t=document.createElement("div");return t.className="va-profile-modal",t.innerHTML=`
		<div class="va-profile-modal__panel va-glass-panel" role="dialog" aria-modal="true" aria-label="Profilom" tabindex="-1">
			<button type="button" class="va-profile-modal__close" aria-label="Bezárás">✕</button>
			<div data-role="body"><p class="va-profile-modal__loading">Betöltés…</p></div>
		</div>
	`,t}function W(t){return t.map(([e,a])=>`
				<div class="va-profile-modal__field">
					<dt>${L(e)}</dt>
					<dd>${L(a||"—")}</dd>
				</div>`).join("")}function Ue(t,e){const a=e.participation||{},n=(a.disadvantageStatus||[]).join(", "),o=[["Felhasználónév",e.username],["E-mail-cím",e.email],["Születési dátum",e.birthDate],["Regisztráció",e.registeredAt]],s=[["Telefonszám",a.phoneNumber],["Születési hely",a.birthPlace],["Lakóhely",a.residence],["Állampolgárság",a.citizenship],["Hátrányos helyzet",n]];a.disadvantageOther&&s.push(["Egyéb",a.disadvantageOther]),t.querySelector('[data-role="body"]').innerHTML=`
		${de(e)}
		<details class="va-profile-modal__data">
			<summary>
				<span>Adataim</span>
				<span class="va-profile-modal__data-chevron" aria-hidden="true"></span>
			</summary>
			<dl class="va-profile-modal__fields">${W(o)}</dl>
			<p class="va-profile-modal__group">Pályázati adatok</p>
			<dl class="va-profile-modal__fields">${W(s)}</dl>
		</details>
		<div class="va-profile-modal__actions">
			${e.accountUrl?`<a class="va-profile-modal__action va-profile-modal__action--edit" href="${L(e.accountUrl)}">Profil szerkesztése</a>`:""}
			<a class="va-profile-modal__action va-profile-modal__action--logout" href="${L(e.logoutUrl)}">Kijelentkezés</a>
		</div>
	`}function Oe(t,e=document.body){const a=He();e.appendChild(a);let n=null;const o=()=>{var l;a.classList.remove("is-visible"),(l=n==null?void 0:n.focus)==null||l.call(n)};return a.addEventListener("click",l=>{(l.target===a||l.target.closest(".va-profile-modal__close"))&&o()}),a.addEventListener("keydown",l=>{l.key==="Escape"&&o()}),{open:async()=>{n=document.activeElement,a.classList.add("is-visible"),a.querySelector(".va-profile-modal__panel").focus({preventScroll:!0});try{const l=await fetch(`${t.restUrl}/profile`,{headers:{"X-WP-Nonce":t.restNonce}});if(!l.ok)throw new Error(String(l.status));const i=await l.json();Ue(a,i),re(a,{restUrl:t.restUrl,restNonce:t.restNonce,limits:i.avatarLimits,data:i,onChange:d=>C("avatar-change",d)})}catch{a.querySelector('[data-role="body"]').innerHTML='<p class="va-profile-modal__loading">A profiladatok jelenleg nem érhetők el. Próbáld újra később.</p>'}}}}function Ie(){const t=document.createElement("div");return t.className="va-info-modal",t.innerHTML=`
		<div class="va-info-modal__panel va-glass-panel">
			<button type="button" class="va-info-modal__close" aria-label="Bezárás">✕</button>
			<div class="va-info-modal__header">
				<span class="va-info-modal__badge" data-role="badge"></span>
				<h2 data-role="title"></h2>
			</div>
			<div class="va-info-modal__project" data-role="project" hidden></div>
			<div class="va-info-modal__image-wrap" data-role="image-wrap"></div>
			<p class="va-info-modal__text" data-role="text"></p>
			<ul class="va-info-modal__partners" data-role="items"></ul>
			<a class="va-info-modal__link" data-role="link" target="_blank" rel="noopener"></a>
		</div>
	`,t.querySelector(".va-info-modal__close").addEventListener("click",()=>t.classList.remove("is-visible")),t.addEventListener("click",e=>{e.target===t&&t.classList.remove("is-visible")}),t}function Pe(t=document.body){const e=Ie();return t.appendChild(e),{open:n=>{e.querySelector('[data-role="badge"]').textContent=n.badge||"",e.querySelector('[data-role="title"]').textContent=n.title||"",e.querySelector('[data-role="text"]').textContent=n.text||"",e.querySelector('[data-role="text"]').style.display=n.text?"":"none";const o=e.querySelector('[data-role="project"]'),s=n.project;if(e.classList.toggle("is-project",!!s),o.hidden=!s,o.innerHTML="",s){const b=u=>{const h=document.createElement("div");return h.textContent=u??"",h.innerHTML};o.innerHTML=`
				<div class="va-info-modal__project-main">
					<span class="va-info-modal__eu-logo"><img src="${b(s.logo)}" alt="Az Európai Unió támogatásával" /></span>
					<p class="va-info-modal__disclaimer">${b(s.disclaimer)}</p>
				</div>
				<dl class="va-info-modal__facts">
					${(s.details||[]).map(u=>`<div><dt>${b(u.label)}</dt><dd>${b(u.value)}</dd></div>`).join("")}
				</dl>`}const l=e.querySelector('[data-role="image-wrap"]');l.innerHTML=n.imageUrl?`<img src="${n.imageUrl}" alt="${n.title||""}" />`:"",l.style.display=n.imageUrl?"":"none";const i=e.querySelector('[data-role="items"]'),d=n.items||[];d.length?(i.innerHTML=d.map(b=>{const u=`
						<img class="va-info-modal__partner-logo" src="${b.imageUrl}" alt="${b.title||""}" />
						<span class="va-info-modal__partner-name">${b.title||""}</span>
					`;return b.link?`<li><a class="va-info-modal__partner" href="${b.link}" target="_blank" rel="noopener">${u}</a></li>`:`<li><span class="va-info-modal__partner">${u}</span></li>`}).join(""),i.style.display=""):(i.innerHTML="",i.style.display="none");const r=e.querySelector('[data-role="link"]');n.link?(r.href=n.link,r.textContent=n.linkLabel||"Tovább a partner oldalára",r.style.display=""):r.style.display="none",e.classList.add("is-visible")}}}function Be(t){const e=document.createElement("div");return e.className="va-info-modal va-contact-modal",e.innerHTML=`
		<div class="va-info-modal__panel va-glass-panel">
			<button type="button" class="va-info-modal__close" aria-label="Bezárás">✕</button>
			<div class="va-info-modal__header">
				<span class="va-info-modal__badge">✉️</span>
				<h2>Kapcsolatfelvétel</h2>
			</div>
			<form data-role="form">
				<label class="va-contact-form__field">
					<span class="va-label-caps">Név</span>
					<input type="text" name="name" data-role="name" required />
				</label>
				<label class="va-contact-form__field">
					<span class="va-label-caps">E-mail cím</span>
					<input type="email" name="email" data-role="email" required />
				</label>
				<label class="va-contact-form__field">
					<span class="va-label-caps">Üzenet</span>
					<textarea name="message" data-role="message" rows="5" required></textarea>
				</label>
				<label class="va-contact-form__checkbox">
					<input type="checkbox" name="consent" data-role="consent" required />
					<span>
						Elolvastam és elfogadom az
						${t?`<a href="${t}" target="_blank" rel="noopener">Adatkezelési tájékoztatóban</a>`:"Adatkezelési tájékoztatóban"}
						foglaltakat.
					</span>
				</label>
				<p class="va-contact-form__status" data-role="status"></p>
				<button type="submit" class="va-welcome__cta va-contact-form__submit" data-role="submit">Üzenet küldése</button>
			</form>
		</div>
	`,e.querySelector(".va-info-modal__close").addEventListener("click",()=>e.classList.remove("is-visible")),e.addEventListener("click",a=>{a.target===e&&e.classList.remove("is-visible")}),e}function Re(t,e=document.body){const a=Be(t.privacyPolicyUrl);e.appendChild(a);const n=a.querySelector('[data-role="form"]'),o=a.querySelector('[data-role="status"]'),s=a.querySelector('[data-role="submit"]');n.addEventListener("submit",async d=>{d.preventDefault(),o.textContent="",o.className="va-contact-form__status",s.disabled=!0,s.textContent="Küldés…";try{if(!(await fetch(`${t.restUrl}/contact`,{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":t.restNonce},body:JSON.stringify({name:a.querySelector('[data-role="name"]').value,email:a.querySelector('[data-role="email"]').value,message:a.querySelector('[data-role="message"]').value,consent:a.querySelector('[data-role="consent"]').checked})})).ok)throw new Error("request failed");o.textContent="Köszönjük, üzeneted megérkezett!",o.classList.add("is-success"),n.reset(),l()}catch{o.textContent="Az üzenet küldése sikertelen volt. Kérjük, próbáld újra.",o.classList.add("is-error")}finally{s.disabled=!1,s.textContent="Üzenet küldése"}});const l=()=>{a.querySelector('[data-role="name"]').value=t.profileName||"",a.querySelector('[data-role="email"]').value=t.profileEmail||""};return{open:async()=>{if(o.textContent="",o.className="va-contact-form__status",!t.profileName)try{const r=await(await fetch(`${t.restUrl}/profile`,{headers:{"X-WP-Nonce":t.restNonce}})).json();t.profileName=r.displayName||r.username||"",t.profileEmail=r.email||""}catch{}l(),a.classList.add("is-visible")}}}function Fe(t,e,a,n,o,s,l){var v;t.innerHTML=`
		<div class="va-header__brand va-glass-panel">
			<span class="va-header__brand-icon">🏛️</span>
			<div class="va-header__brand-text">
				<span class="va-header__brand-name">Virtuális Akadémia</span>
				<div class="va-header__breadcrumb">
					<button type="button" class="va-header__breadcrumb-corridor" data-role="corridor-link">Átrium</button>
					<span class="va-header__breadcrumb-sep" data-role="breadcrumb-sep">›</span>
					<span class="va-header__breadcrumb-current" data-role="room-name"></span>
				</div>
			</div>
		</div>
		<div class="va-header__actions">
			<button type="button" class="va-header__profile" aria-label="Profilom" data-role="profile"></button>
			<button type="button" class="va-header__contact" aria-label="Kapcsolatfelvétel">✉️</button>
			<a class="va-header__logout" aria-label="Kijelentkezés" href="${l||"#"}">⏻</a>
			<button type="button" class="va-header__passport">
				<span>🎖️</span>
				<span class="va-header__passport-label">Útlevél</span>
			</button>
		</div>
	`;const i=t.querySelector('[data-role="profile"]'),d=p=>{var c;const m=(c=e.me)==null?void 0:c.initials;i.classList.toggle("has-avatar",!!p),i.innerHTML=p?`<img src="${L(p)}" alt="" />`:`<span class="va-header__initials">${m?L(m):"👤"}</span>`};d((v=e.me)==null?void 0:v.avatarUrl),A("avatar-change",d);const r=t.querySelector('[data-role="corridor-link"]'),b=t.querySelector('[data-role="breadcrumb-sep"]'),u=t.querySelector('[data-role="room-name"]'),h=p=>{const m=p==="corridor",c=e.scenes[p];u.textContent=m?"":(c==null?void 0:c.title)||"",b.style.display=m?"none":"",r.classList.toggle("is-current",m),r.disabled=m};A("scene-change",h),h(e.initialSceneId),r.addEventListener("click",o),t.querySelector(".va-header__passport").addEventListener("click",a),t.querySelector(".va-header__profile").addEventListener("click",n),t.querySelector(".va-header__contact").addEventListener("click",s)}const te="va-room-panel-expanded",P=()=>window.matchMedia("(max-width: 640px)").matches,Ke=()=>window.matchMedia("(hover: hover) and (pointer: fine)").matches;function Ve(){try{const t=window.localStorage.getItem(te);if(t!==null)return t==="1"}catch{}return!P()}function We(t){try{window.localStorage.setItem(te,t?"1":"0")}catch{}}function S(t){const e=document.createElement("div");return e.textContent=t??"",e.innerHTML}function D(t,e){const a=e?t/e:0,n=2*Math.PI*16;return`
		<span class="va-ring${e&&t===e?" is-complete":""}" aria-hidden="true">
			<svg viewBox="0 0 40 40">
				<circle class="va-ring__track" cx="20" cy="20" r="16" />
				<circle class="va-ring__value" cx="20" cy="20" r="16"
					stroke-dasharray="${n.toFixed(2)}"
					stroke-dashoffset="${(n*(1-a)).toFixed(2)}" />
			</svg>
			<span class="va-ring__text">${t}/${e}</span>
		</span>`}function N(t){return((t==null?void 0:t.hotspots)||[]).filter(e=>e.kind==="lesson")}function De(t,e,{viewer:a,onOpenPassport:n,onOpenGuide:o}){const s=document.createElement("section");s.className="va-room-panel",s.setAttribute("aria-label","Tanszéki panel"),t.appendChild(s);let l=Ve();const i=(u,h=!0)=>{var v;l=u,s.classList.toggle("is-expanded",u),(v=s.querySelector(".va-room-panel__toggle"))==null||v.setAttribute("aria-expanded",String(u)),h&&We(u)},d=(u,h)=>{const v=h.department||{},p=N(h),m=p.filter(y=>y.status==="completed").length,c=p.findIndex(y=>y.status==="available"),g=p[c],k=p.length>0&&m===p.length;let _;k&&v.certificateUrl?_=`<a class="va-room-panel__cta is-gold" href="${S(v.certificateUrl)}">🏅 Oklevél letöltése</a>`:g?_=`<a class="va-room-panel__cta" data-lesson="${c}" href="${S(g.url)}">
				${m?"Folytatás":"Kezdés"} · ${c+1}. lecke <span aria-hidden="true">→</span></a>`:v.courseUrl?_=`<a class="va-room-panel__cta is-ghost" data-course href="${S(v.courseUrl)}">Tanfolyam megnyitása</a>`:_="";const f=p.map((y,w)=>{const z=w===c?"next":y.status,T=y.status==="completed"?"✓":y.status==="locked"?"🔒":String(w+1),M={completed:"kész",next:"következő",available:"elérhető",locked:"zárolva"}[z],q=`<span class="va-room-panel__badge" aria-hidden="true">${T}</span>
					<span class="va-room-panel__lesson-name">${S(y.title)}</span>
					<span class="va-sr-only">(${M})</span>`;return y.status==="locked"?`<li class="is-locked" data-index="${w}"><span class="va-room-panel__lesson">${q}</span></li>`:`<li class="is-${z}" data-index="${w}"><a class="va-room-panel__lesson" data-lesson="${w}" href="${S(y.url)}">${q}</a></li>`}).join(""),x=k&&v.certificateUrl?`<a class="va-room-panel__cert is-ready" href="${S(v.certificateUrl)}">
					<span class="va-room-panel__cert-icon" aria-hidden="true">🏅</span>
					<span><strong>Tanszéki oklevél</strong><small>Megszerezted – töltsd le PDF-ben.</small></span>
				</a>`:`<div class="va-room-panel__cert">
					<span class="va-room-panel__cert-icon" aria-hidden="true">🔒</span>
					<span><strong>Tanszéki oklevél</strong><small>Mind a ${p.length} lecke elvégzése után kapod meg.</small></span>
				</div>`;return{ring:D(m,p.length),eyebrow:v.number?`${String(v.number).padStart(2,"0")}. tanszék`:"Tanszék",title:v.label||h.title,cta:_,body:`
				${v.summary?`<p class="va-room-panel__summary">${S(v.summary)}</p>`:""}
				<p class="va-room-panel__section">Leckék</p>
				<ol class="va-room-panel__lessons">${f}</ol>
				${x}`}},r=()=>{var k;const u=Object.entries(e.scenes).filter(([_])=>_!=="corridor");let h=0,v=0,p=null;const m=u.map(([_,f])=>{var z,T;const x=N(f),y=x.filter(M=>M.status==="completed").length,w=x.length>0&&y===x.length;return h+=x.length,v+=y,!p&&!w&&y>0&&(p=[_,f]),`<li>
					<button type="button" class="va-room-panel__room${w?" is-complete":""}" data-room="${S(_)}">
						<span class="va-room-panel__badge" aria-hidden="true">${w?"✓":String(((z=f.department)==null?void 0:z.number)||"").padStart(2,"0")}</span>
						<span class="va-room-panel__room-text">
							<strong>${S(((T=f.department)==null?void 0:T.label)||f.title)}</strong>
							<small>${y} / ${x.length} lecke</small>
						</span>
						<span class="va-room-panel__bar" aria-hidden="true"><span style="width:${x.length?Math.round(y/x.length*100):0}%"></span></span>
					</button>
				</li>`}).join("");p||(p=u.find(([,_])=>N(_).some(f=>f.status!=="completed"))||null);const g=h>0&&v===h?e.masterCertificateUrl?`<a class="va-room-panel__cta is-gold" href="${S(e.masterCertificateUrl)}" download>🏅 Mester Oklevél letöltése</a>`:'<button type="button" class="va-room-panel__cta is-gold" data-action="passport">🏅 Mester Oklevél letöltése</button>':p?`<button type="button" class="va-room-panel__cta" data-room="${S(p[0])}">
						${v?"Folytatás":"Kezdés"} · ${S(((k=p[1].department)==null?void 0:k.label)||p[1].title)} <span aria-hidden="true">→</span></button>`:"";return{ring:D(v,h),eyebrow:"Átrium · Virtuális Akadémia",title:"Tanszékek",cta:g,body:`
				<p class="va-room-panel__summary">Válassz tanszéket, vagy lépj be egy ajtón a teremben. Minden elvégzett tanszékért pecsét és oklevél jár.
					<button type="button" class="va-room-panel__more" data-action="guide">Tudj meg többet <span aria-hidden="true">→</span></button></p>
				<ul class="va-room-panel__rooms">${m}</ul>`}},b=u=>{const h=e.scenes[u];if(!h)return;const v=u==="corridor"?r():d(u,h);s.dataset.scene=u,s.innerHTML=`
			<button type="button" class="va-room-panel__toggle" aria-expanded="${l}" aria-controls="va-room-panel-body">
				${v.ring}
				<span class="va-room-panel__titles">
					<span class="va-room-panel__eyebrow">${S(v.eyebrow)}</span>
					<span class="va-room-panel__title">${S(v.title)}</span>
				</span>
				<span class="va-room-panel__chevron" aria-hidden="true"></span>
				<span class="va-sr-only">${l?"Panel összecsukása":"Részletek"}</span>
			</button>
			${v.cta}
			<div class="va-room-panel__body" id="va-room-panel-body">${v.body}</div>
		`,i(l,!1);const p=N(h);let m=!1;s.addEventListener("pointermove",()=>m=!0,{once:!0}),Ke()&&a&&s.querySelectorAll(".va-room-panel__lessons li").forEach(c=>{c.addEventListener("mouseenter",()=>m&&a.lookAtHotspot(p[Number(c.dataset.index)]))})};return s.addEventListener("click",u=>{var m;const h=u.target.closest("a[data-lesson], a[data-course]");if(h&&a&&u.button===0&&!(u.metaKey||u.ctrlKey||u.shiftKey||u.altKey)){u.preventDefault(),P()&&i(!1,!1);const c=e.scenes[s.dataset.scene];if(h.dataset.lesson!==void 0){const g=N(c)[Number(h.dataset.lesson)];a.leaveTo(h.href,{hotspot:g})}else{const g=h.getBoundingClientRect();a.leaveTo(h.href,{label:{eyebrow:"Tanfolyam",title:((m=c==null?void 0:c.department)==null?void 0:m.label)||(c==null?void 0:c.title)||""},origin:{x:g.left+g.width/2,y:g.top+g.height/2}})}return}if(u.target.closest(".va-room-panel__toggle")){i(!l);return}const p=u.target.closest("[data-room]");if(p){P()&&i(!1),a==null||a.goToScene(p.dataset.room);return}if(u.target.closest('[data-action="passport"]')){n==null||n();return}u.target.closest('[data-action="guide"]')&&(o==null||o())}),A("scene-change",b),A("transition-start",()=>s.classList.add("is-leaving")),A("transition-end",()=>{s.classList.remove("is-leaving"),s.classList.add("is-entering"),s.addEventListener("animationend",()=>s.classList.remove("is-entering"),{once:!0})}),b(e.initialSceneId),s}function Ge(t,e,a){const n=document.createElement("div");n.className="va-context-menu",n.setAttribute("role","menu"),n.setAttribute("aria-label","Súgó"),n.hidden=!0,n.innerHTML=a.map((i,d)=>`
			<button type="button" role="menuitem" class="va-context-menu__item" data-index="${d}" tabindex="-1">
				<span class="va-context-menu__icon" aria-hidden="true">${i.icon}</span>
				<span>${i.label}</span>
			</button>`).join(""),e.appendChild(n);const o=[...n.querySelectorAll("button")],s=()=>{n.hidden||(n.hidden=!0)},l=(i,d)=>{var u;n.hidden=!1;const{width:r,height:b}=n.getBoundingClientRect();n.style.left=`${Math.max(8,Math.min(i,window.innerWidth-r-8))}px`,n.style.top=`${Math.max(8,Math.min(d,window.innerHeight-b-8))}px`,(u=o[0])==null||u.focus({preventScroll:!0})};t.addEventListener("contextmenu",i=>{i.preventDefault(),i.stopPropagation(),!window.matchMedia("(hover: none)").matches&&l(i.clientX,i.clientY)},!0),n.addEventListener("click",i=>{var r;const d=i.target.closest("button");d&&(s(),(r=a[Number(d.dataset.index)])==null||r.action())}),n.addEventListener("contextmenu",i=>i.preventDefault()),n.addEventListener("keydown",i=>{var r;const d=o.indexOf(document.activeElement);if(i.key==="Escape"||i.key==="Tab")i.preventDefault(),s(),(r=t.focus)==null||r.call(t,{preventScroll:!0});else if(i.key==="ArrowDown"||i.key==="ArrowUp"){i.preventDefault();const b=i.key==="ArrowDown"?1:-1;o[(d+b+o.length)%o.length].focus()}}),document.addEventListener("pointerdown",i=>{n.contains(i.target)||s()},!0),window.addEventListener("resize",s),window.addEventListener("blur",s)}function Ye(t){var n;const a=new URLSearchParams(window.location.search).get("va_room");if(a&&t.scenes[a])return a;if(((n=t.greeting)==null?void 0:n.type)==="return"){const o=Object.entries(t.scenes).find(([s,l])=>{if(s==="corridor")return!1;const i=(l.hotspots||[]).filter(r=>r.kind==="lesson"),d=i.filter(r=>r.status==="completed").length;return d>0&&d<i.length});if(o)return o[0]}return t.firstSceneId}function Xe(){const e=new URLSearchParams(window.location.search).get("va_hotspot");return e?Number(e):null}function Ze(t){const e=new URL(window.location.href);e.searchParams.set("va_room",t),window.history.replaceState({},"",e)}function G(){const t=document.getElementById("va-metaverse-root"),e=document.getElementById("va-metaverse-viewer"),a=document.getElementById("va-metaverse-hud"),n=document.getElementById("va-metaverse-header"),o=document.getElementById("va-metaverse-minimap"),s=window.vaSceneData;if(!e||!s||!s.firstSceneId)return;s.initialSceneId=Ye(s),s.highlightHotspotId=Xe();const l=new we,i=t||document.body,d=new ke(e,s,{onSceneChange:(m,c)=>{Ze(c),l.play(m.audioUrl)},onSelectHotspot:m=>{if(m.kind==="plaque"){v.open({title:m.title,imageUrl:m.imageUrl,link:m.link,linkLabel:"Tovább a partner oldalára"});return}if(m.kind==="lesson"&&m.url){d.leaveTo(m.url,{hotspot:m});return}m.url&&(window.location.href=m.url)},overlayMount:i}),r=new ze(s,i,{deferred:!!s.greeting}),b=se();oe(),s.greeting?d.greet(s.greeting).then(()=>r.showIfUnseen()):b&&d.arrive();const u=Ne(s,i,{onGoToRoom:m=>d.goToScene(m)}),h=Oe(s,i),v=Pe(i),p=Re(s,i);Ge(e,i,[{icon:"❓",label:"Súgó: eligazodás",action:()=>r.show(1)},{icon:"🎖️",label:"Súgó: pecsétgyűjtő játék",action:()=>r.show(2)}]),a&&(Se(a,l),xe(a,r),Le(a,d),$e(a,s.euInfoText,v,s.project),Ce(a,s.partners,v),t&&Ee(a,t)),n&&Fe(n,s,u.open,h.open,()=>d.goToCorridor(),p.open,s.logoutUrl),De(i,s,{viewer:d,onOpenPassport:u.open,onOpenGuide:()=>u.open({guide:!0})}),o&&Ae(o,s,m=>d.goToScene(m))}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",G):G();
