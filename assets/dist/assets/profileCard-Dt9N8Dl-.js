const g=a=>`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${a}</svg>`;function L(a,s,t,e){return`M${Array.from({length:10},(u,c)=>{const i=c%2?e:t,n=c*Math.PI/5-Math.PI/2;return`${(a+Math.cos(n)*i).toFixed(2)} ${(s+Math.sin(n)*i).toFixed(2)}`}).join("L")}Z`}const P=Array.from({length:12},(a,s)=>{const t=s/12*Math.PI*2;return`<path d="${L(12+Math.sin(t)*8,12-Math.cos(t)*8,1.9,.8)}" fill="currentColor" stroke="none"/>`}).join(""),k={scales:g('<path d="M12 4v16M8 20h8M5 7h14"/><circle cx="12" cy="4.5" r="1"/><path d="M5 7l-2.6 6h5.2L5 7zM19 7l-2.6 6h5.2L19 7z"/><path d="M2.4 13a2.6 2.6 0 0 0 5.2 0M16.4 13a2.6 2.6 0 0 0 5.2 0"/>'),megaphone:g('<path d="M3 10.5v3a1 1 0 0 0 1 1h2.5l6.5 4.5v-14L6.5 9.5H4a1 1 0 0 0-1 1z"/><path d="M7 14.5l1 4.5h2.5l-.8-3.4"/><path d="M16.5 9.5a3.5 3.5 0 0 1 0 5M19 7a7 7 0 0 1 0 10"/>'),stars:g(P),hands:g('<circle cx="8" cy="8" r="2.6"/><circle cx="16" cy="8" r="2.6"/><path d="M3 19v-1a5 5 0 0 1 8.2-3.8M21 19v-1a5 5 0 0 0-8.2-3.8"/><path d="M12 21l-2.2-2.1a1.5 1.5 0 0 1 2.2-2 1.5 1.5 0 0 1 2.2 2z" fill="currentColor" stroke="none"/>')};function A(a){return k[a]||k.scales}function M(a,s="stamped",t=""){return`<span class="va-stamp is-${s}${t?` ${t}`:""}">${A(a)}</span>`}function p(a){const s=document.createElement("div");return s.textContent=a??"",s.innerHTML}const y=a=>String(a??"").padStart(2,"0");function I(a){return Object.entries((a==null?void 0:a.scenes)||{}).filter(([s,t])=>s!=="corridor"&&t.department).map(([s,t])=>{const e=(t.hotspots||[]).filter(d=>d.kind==="lesson");return{sceneId:s,number:t.department.number,label:t.department.label,icon:t.department.icon,total:e.length,done:e.filter(d=>d.status==="completed").length}}).sort((s,t)=>s.number-t.number)}const E={1:"egy",2:"két",3:"három",4:"négy",5:"öt",6:"hat"};function U(a){return E[a]||String(a)}function N(a,{withMap:s=!0}={}){const t=a.length||4;return`
		<div class="va-stamp-guide">
			<ol class="va-stamp-guide__steps">
				<li>
					<span class="va-stamp-guide__no" aria-hidden="true">1</span>
					<span><strong>Lépj be egy tanszékre</strong>
					Az Átriumból az ajtókon, a panel listájából vagy a térképről.</span>
				</li>
				<li>
					<span class="va-stamp-guide__no" aria-hidden="true">2</span>
					<span><strong>Végezd el a leckéket</strong>
					Sorban nyílnak meg – a pulzáló arany pont mindig a következőt mutatja.</span>
				</li>
				<li>
					<span class="va-stamp-guide__no" aria-hidden="true">3</span>
					<span><strong>Megkapod a pecsétet</strong>
					Az utolsó lecke után a tanszék pecsétje magától a helyére kerül az Útleveledben. Az oklevelet letöltheted, és e-mailben is elküldjük.</span>
				</li>
			</ol>
			${s&&a.length?`
			<p class="va-stamp-guide__label">Melyik pecsét hova kerül?</p>
			<ul class="va-stamp-guide__map">
				${a.map(d=>`
						<li>
							${M(d.icon,"stamped","va-stamp--sm")}
							<span class="va-stamp-guide__map-text">
								<strong>${p(d.label)}</strong>
								<small>${d.total?`${d.total} lecke után`:"a tanszék leckéi után"}</small>
							</span>
							<span class="va-stamp-guide__slot" aria-label="${y(d.number)}. pecséthely">
								<span aria-hidden="true">→</span> ${y(d.number)}. hely
							</span>
						</li>`).join("")}
			</ul>`:""}
			<p class="va-stamp-guide__reward">
				<span aria-hidden="true">🏅</span>
				<span>Mind a ${U(t)} pecsétért <strong>Mester Oklevél</strong> jár.</span>
			</p>
		</div>`}const B={"image/jpeg":"JPG","image/png":"PNG","image/webp":"WebP"};function F(a,s){const e=2*Math.PI*26,d=s?a/s:0;return`
		<span class="va-idcard__ring" aria-hidden="true">
			<svg viewBox="0 0 64 64">
				<circle class="va-idcard__ring-track" cx="32" cy="32" r="26" />
				<circle class="va-idcard__ring-value" cx="32" cy="32" r="26"
					stroke-dasharray="${e.toFixed(2)}"
					stroke-dashoffset="${(e*(1-d)).toFixed(2)}" />
			</svg>
			<span>${Math.round(d*100)}%</span>
		</span>`}function H(a){if(!a)return"";const s=(a.types||[]).map(e=>B[e]||e).join(", "),t=Math.round(a.maxBytes/1024/1024*10)/10;return`${s} · legfeljebb ${t} MB · legalább ${a.minSide}×${a.minSide} px`}function j(a){return a.avatarUrl?`<img src="${p(a.avatarUrl)}" alt="${p(a.displayName)} profilképe" />`:`<span class="va-idcard__initials" aria-hidden="true">${p(a.initials||"?")}</span>`}function R(a,{editable:s=!0,headingTag:t="h2"}={}){var i;const e=a.stamps||{done:0,total:0},d=a.courses||[],u=s?`
			<div class="va-idcard__photo-actions">
				<label class="va-idcard__upload">
					<input type="file" accept="${p((((i=a.avatarLimits)==null?void 0:i.types)||["image/jpeg","image/png","image/webp"]).join(","))}" data-avatar-input />
					<span aria-hidden="true">📷</span>
					<span data-avatar-upload-label>${a.avatarUrl?"Kép cseréje":"Kép feltöltése"}</span>
				</label>
				<button type="button" class="va-idcard__remove" data-avatar-remove ${a.avatarUrl?"":"hidden"}>Eltávolítás</button>
			</div>
			<p class="va-idcard__hint">${p(H(a.avatarLimits))}</p>
			<p class="va-idcard__feedback" role="status" aria-live="polite" data-avatar-status></p>`:"",c=d.map(n=>`
			<li class="${n.stamped?"is-stamped":""}">
				${M(n.icon,n.stamped?"stamped":"empty","va-stamp--xs")}
				<span class="va-idcard__dept-name">${p(n.label)}</span>
				<span class="va-idcard__dept-count">${n.completed} / ${n.total}</span>
				<span class="va-idcard__dept-bar" aria-hidden="true"><span style="width:${n.percentage}%"></span></span>
				<span class="va-sr-only">${n.stamped?"pecsét megszerezve":`${n.percentage}% kész`}</span>
			</li>`).join("");return`
		<article class="va-idcard" data-avatar-root>
			<span class="va-idcard__corner va-idcard__corner--tl" aria-hidden="true"></span>
			<span class="va-idcard__corner va-idcard__corner--tr" aria-hidden="true"></span>
			<span class="va-idcard__corner va-idcard__corner--bl" aria-hidden="true"></span>
			<span class="va-idcard__corner va-idcard__corner--br" aria-hidden="true"></span>
			<header class="va-idcard__head">
				<span class="va-label-caps">Profil</span>
				<span class="va-idcard__chip" aria-hidden="true">🇪🇺</span>
			</header>
			<div class="va-idcard__main">
				<div class="va-idcard__portrait">
					<div class="va-idcard__photo" data-avatar-photo>${j(a)}</div>
				</div>
				<dl class="va-idcard__fields">
					<div class="va-idcard__field">
						<dt class="va-label-caps">Név</dt>
						<dd><${t} class="va-idcard__name">${p(a.displayName)}</${t}></dd>
					</div>
					<div class="va-idcard__field">
						<dt class="va-label-caps">Felhasználó típusa</dt>
						<dd class="va-idcard__rank">${p(a.userType)}</dd>
					</div>
					<div class="va-idcard__field">
						<dt class="va-label-caps">Azonosító</dt>
						<dd class="va-idcard__id">${p(a.memberId)}</dd>
					</div>
				</dl>
			</div>
			${u?`<div class="va-idcard__upload-row">${u}</div>`:""}
			<div class="va-idcard__status">
				<div>
					<span class="va-label-caps">Gyűjtött pecsétek</span>
					<strong class="va-idcard__tally">${e.done} / ${e.total} <small>pecsét</small></strong>
					${a.registeredAt?`<span class="va-idcard__since">Tag ${p(a.registeredAt)} óta</span>`:""}
				</div>
				${F(e.done,e.total)}
			</div>
			${c?`<ul class="va-idcard__depts" aria-label="Előrehaladás tanszékenként">${c}</ul>`:""}
		</article>`}function O(a,{restUrl:s,restNonce:t,limits:e,data:d,onChange:u}){const c=a.querySelector("[data-avatar-root]")||a,i=c.querySelector("[data-avatar-input]"),n=c.querySelector("[data-avatar-remove]"),b=c.querySelector("[data-avatar-status]"),S=c.querySelector("[data-avatar-photo]"),z=c.querySelector("[data-avatar-upload-label]");if(!i)return;const _=(r,l="")=>{b.textContent=r,b.className=`va-idcard__feedback${l?` is-${l}`:""}`},h=r=>{c.classList.toggle("is-uploading",r),i.disabled=r,n&&(n.disabled=r)},$=r=>{d.avatarUrl=r,S.innerHTML=j(d),z.textContent=r?"Kép cseréje":"Kép feltöltése",n&&(n.hidden=!r),u==null||u(r)},x=r=>new Promise(l=>{const v=URL.createObjectURL(r),o=new Image;o.onload=()=>{l({width:o.naturalWidth,height:o.naturalHeight}),URL.revokeObjectURL(v)},o.onerror=()=>{l(null),URL.revokeObjectURL(v)},o.src=v}),w=async r=>{if(e!=null&&e.types&&!e.types.includes(r.type))return"Csak JPG, PNG vagy WebP képet tölthetsz fel.";if(e!=null&&e.maxBytes&&r.size>e.maxBytes)return"A kép túl nagy: legfeljebb 2 MB-os fájlt tölthetsz fel.";const l=await x(r);return l?e!=null&&e.minSide&&(l.width<e.minSide||l.height<e.minSide)?`A kép túl kicsi: legalább ${e.minSide}×${e.minSide} képpontos legyen.`:e!=null&&e.maxSide&&(l.width>e.maxSide||l.height>e.maxSide)?`A kép felbontása túl nagy: legfeljebb ${e.maxSide} képpont lehet egy oldala.`:"":"Ezt a képet nem sikerült beolvasni. Próbálkozz egy másikkal."},f=async(r,l)=>{const v=await fetch(`${s}/profile/avatar`,{method:r,headers:{"X-WP-Nonce":t},body:l,credentials:"same-origin"}),o=await v.json().catch(()=>({}));if(!v.ok)throw new Error(o.message||"A művelet most nem sikerült. Próbáld újra később.");return o};i.addEventListener("change",async()=>{var o;const r=(o=i.files)==null?void 0:o[0];if(i.value="",!r)return;const l=await w(r);if(l){_(l,"error");return}const v=new FormData;v.append("avatar",r),h(!0),_("Feltöltés…");try{const m=await f("POST",v);$(m.avatarUrl),_("Kész! Frissült a profilképed.","success")}catch(m){_(m.message,"error")}finally{h(!1)}}),n==null||n.addEventListener("click",async()=>{h(!0),_("Eltávolítás…");try{await f("DELETE"),$(""),_("A profilképedet eltávolítottuk.","success")}catch(r){_(r.message,"error")}finally{h(!1)}})}export{O as b,I as d,p as e,N as g,R as i,U as n,M as s};
