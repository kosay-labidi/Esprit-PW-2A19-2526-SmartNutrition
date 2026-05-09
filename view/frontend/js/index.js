/**
 * Index Page JavaScript
 * Page d'accueil GaiaLumen avec animations 3D
 */

console.log('🌿 GaiaLumen chargé - Vérification THREE.js:', typeof THREE !== 'undefined');

/* ═══════════════════════════════════════════════════════════
   PRELOADER
   ═══════════════════════════════════════════════════════════ */
(function(){
  const c=document.getElementById('pl-canvas');
  if(!c)return;
  const ctx=c.getContext('2d');
  c.width=140;c.height=140;
  const fill=document.getElementById('pl-fill');
  let a=0,start=null;
  function draw(ts){
    if(!start)start=ts;
    const p=Math.min((ts-start)/2000*100,100);
    if(fill)fill.style.width=p+'%';
    a+=.04;
    ctx.clearRect(0,0,140,140);
    ctx.save();ctx.translate(70,70);ctx.rotate(a);
    // Anneaux
    for(let i=0;i<3;i++){
      ctx.beginPath();ctx.arc(0,0,48-i*10,0,Math.PI*2);
      ctx.strokeStyle=`rgba(${i===0?'58,134,196':i===1?'91,62,150':'31,61,43'},${.6-i*.15})`;
      ctx.lineWidth=1.5;ctx.stroke();
    }
    ctx.rotate(-a*.3);
    // Feuille
    const g=ctx.createLinearGradient(-18,-26,18,26);
    g.addColorStop(0,'#1F3D2B');g.addColorStop(1,'#3A86C4');
    ctx.beginPath();ctx.fillStyle=g;
    ctx.moveTo(0,-26);ctx.bezierCurveTo(19,-12,22,7,0,26);
    ctx.bezierCurveTo(-22,7,-19,-12,0,-26);ctx.fill();
    ctx.beginPath();ctx.moveTo(0,-24);ctx.lineTo(0,24);
    ctx.strokeStyle='rgba(242,232,207,.5)';ctx.lineWidth=1;ctx.stroke();
    ctx.restore();
    if(p<100)requestAnimationFrame(draw);
    else setTimeout(()=>{
      const pl=document.getElementById('preloader');
      if(pl)pl.classList.add('hidden');
    },300);
  }
  requestAnimationFrame(draw);
})();

/* ═══════════════════════════════════════════════════════════
   CURSEUR
   ═══════════════════════════════════════════════════════════ */
(function(){
  const cur=document.getElementById('cursor');
  const trail=document.getElementById('cursor-trail');
  if(!cur||!trail)return;
  let mx=0,my=0,tx=0,ty=0;
  document.addEventListener('mousemove',e=>{
    mx=e.clientX;my=e.clientY;
    cur.style.left=mx+'px';cur.style.top=my+'px';
  });
  (function loop(){
    tx+=(mx-tx)*.12;ty+=(my-ty)*.12;
    trail.style.left=tx+'px';trail.style.top=ty+'px';
    requestAnimationFrame(loop);
  })();
  document.querySelectorAll('a,button,.module-card').forEach(el=>{
    el.addEventListener('mouseenter',()=>cur.classList.add('hover'));
    el.addEventListener('mouseleave',()=>cur.classList.remove('hover'));
  });
})();

/* ═══════════════════════════════════════════════════════════
   THEME TOGGLE
   ═══════════════════════════════════════════════════════════ */
(function(){
  const btn=document.getElementById('theme-toggle');
  const html=document.documentElement;
  const saved=localStorage.getItem('gaialumen-theme')||'dark';
  html.setAttribute('data-theme',saved);
  if(btn)btn.textContent=saved==='dark'?'☀️ Clair':'🌙 Sombre';
  if(btn)btn.addEventListener('click',()=>{
    const n=html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme',n);
    localStorage.setItem('gaialumen-theme',n);
    btn.textContent=n==='dark'?'☀️ Clair':'🌙 Sombre';
  });
})();

/* ═══════════════════════════════════════════════════════════
   NAVBAR SCROLL
   ═══════════════════════════════════════════════════════════ */
(function(){
  const nb=document.getElementById('navbar');
  window.addEventListener('scroll',()=>{
    if(nb)nb.classList.toggle('scrolled',window.scrollY>40);
  });
  const disc=document.getElementById('cta-discover');
  if(disc)disc.addEventListener('click',()=>{
    document.getElementById('modules')?.scrollIntoView({behavior:'smooth'});
  });
})();

/* ═══════════════════════════════════════════════════════════
   TYPEWRITER
   ═══════════════════════════════════════════════════════════ */
(function(){
  const el=document.getElementById('typewriter-text');
  if(!el)return;
  const slogans=[
    'Nourrissez votre corps, respectez la Terre.',
    'La nutrition intelligente au service de la planète.',
    'Chaque repas, un geste pour Gaïa.',
    'Mangez mieux. Vivez mieux. Impactez moins.'
  ];
  let si=0,ci=0,del=false;
  function type(){
    const txt=slogans[si];
    const blink='<span class="cursor-blink"></span>';
    if(!del){
      ci++;
      el.innerHTML=txt.slice(0,ci)+blink;
      if(ci===txt.length){del=true;setTimeout(type,2200);return;}
      setTimeout(type,55);
    }else{
      ci--;
      el.innerHTML=txt.slice(0,ci)+blink;
      if(ci===0){del=false;si=(si+1)%slogans.length;setTimeout(type,400);return;}
      setTimeout(type,28);
    }
  }
  setTimeout(type,2500);
})();

/* ═══════════════════════════════════════════════════════════
   SCROLL REVEAL
   ═══════════════════════════════════════════════════════════ */
(function(){
  const obs=new IntersectionObserver(entries=>{
    entries.forEach((e,i)=>{
      if(e.isIntersecting){
        setTimeout(()=>e.target.classList.add('visible'),i*80);
        obs.unobserve(e.target);
      }
    });
  },{threshold:.12});
  document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));
})();

/* ═══════════════════════════════════════════════════════════
   COUNTERS
   ═══════════════════════════════════════════════════════════ */
(function(){
  const obs=new IntersectionObserver(entries=>{
    entries.forEach(e=>{
      if(e.isIntersecting){
        const el=e.target,target=+el.dataset.target;
        const dur=2000,step=target/(dur/16);
        let cur=0;
        const t=setInterval(()=>{
          cur=Math.min(cur+step,target);
          el.textContent=Math.floor(cur).toLocaleString('fr-FR');
          if(cur>=target)clearInterval(t);
        },16);
        obs.unobserve(el);
      }
    });
  },{threshold:.5});
  document.querySelectorAll('.stat-number').forEach(el=>obs.observe(el));
})();

/* ═══════════════════════════════════════════════════════════
   CARD TILT 3D
   ═══════════════════════════════════════════════════════════ */
document.querySelectorAll('.module-card').forEach(card=>{
  card.addEventListener('mousemove',e=>{
    const r=card.getBoundingClientRect();
    const dx=(e.clientX-r.left-r.width/2)/(r.width/2);
    const dy=(e.clientY-r.top-r.height/2)/(r.height/2);
    card.style.transform=`perspective(800px) rotateY(${dx*10}deg) rotateX(${-dy*10}deg) scale(1.03)`;
    card.style.setProperty('--mx',((e.clientX-r.left)/r.width*100).toFixed(1)+'%');
    card.style.setProperty('--my',((e.clientY-r.top)/r.height*100).toFixed(1)+'%');
  });
  card.addEventListener('mouseleave',()=>{
    card.style.transform='';
    card.style.transition='transform .5s ease,box-shadow .3s,border-color .3s';
  });
  card.addEventListener('mouseenter',()=>{
    card.style.transition='box-shadow .3s,border-color .3s';
  });
});

/* ═══════════════════════════════════════════════════════════
   THREE.JS - HERO AVEC GLOBE TERRESTRE 3D
   ═══════════════════════════════════════════════════════════ */
window.addEventListener('load',function(){
  const canvas=document.getElementById('hero-canvas');
  if(!canvas||typeof THREE==='undefined'){
    console.error('Canvas ou THREE.js non disponible');
    return;
  }
  
  console.log('🌍 Initialisation scène 3D Hero...');
  
  const renderer=new THREE.WebGLRenderer({canvas,antialias:true,alpha:true});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio,2));
  
  const scene=new THREE.Scene();
  const camera=new THREE.PerspectiveCamera(50,canvas.offsetWidth/canvas.offsetHeight,.1,1000);
  camera.position.set(0,0,8);
  
  function resize(){
    const w=canvas.offsetWidth,h=canvas.offsetHeight;
    renderer.setSize(w,h);
    camera.aspect=w/h;
    camera.updateProjectionMatrix();
  }
  resize();
  window.addEventListener('resize',resize);
  
  // Lumières
  scene.add(new THREE.AmbientLight(0xffffff,.4));
  const dl=new THREE.DirectionalLight(0x3A86C4,1.2);
  dl.position.set(5,3,5);
  scene.add(dl);
  const pl=new THREE.PointLight(0x5B3E96,1.5,20);
  pl.position.set(-3,2,2);
  scene.add(pl);
  
  // Globe Terre (plus grand)
  const loader=new THREE.TextureLoader();
  const earth=new THREE.Mesh(
    new THREE.SphereGeometry(1.8,64,64),
    new THREE.MeshPhongMaterial({
      map:loader.load('https://threejs.org/examples/textures/planets/earth_atmos_2048.jpg'),
      specular:new THREE.Color(0x3A86C4),
      shininess:25
    })
  );
  scene.add(earth);
  
  const clouds=new THREE.Mesh(
    new THREE.SphereGeometry(1.83,64,64),
    new THREE.MeshPhongMaterial({
      map:loader.load('https://threejs.org/examples/textures/planets/earth_clouds_1024.png'),
      transparent:true,
      opacity:.35
    })
  );
  scene.add(clouds);
  
  // Atmosphère
  const atm=new THREE.Mesh(
    new THREE.SphereGeometry(1.3,64,64),
    new THREE.MeshPhongMaterial({
      color:0x3A86C4,
      transparent:true,
      opacity:.07,
      side:THREE.FrontSide
    })
  );
  scene.add(atm);
  
  // Particules lumineuses
  const PC=1800,pp=new Float32Array(PC*3),pc=new Float32Array(PC*3);
  const pal=[
    new THREE.Color(0x1F3D2B),
    new THREE.Color(0x3A86C4),
    new THREE.Color(0x5B3E96),
    new THREE.Color(0xF2E8CF)
  ];
  for(let i=0;i<PC;i++){
    const r=1.8+Math.random()*3;
    const th=Math.random()*Math.PI*2;
    const ph=Math.acos(2*Math.random()-1);
    pp[i*3]=r*Math.sin(ph)*Math.cos(th);
    pp[i*3+1]=r*Math.sin(ph)*Math.sin(th);
    pp[i*3+2]=r*Math.cos(ph);
    const c=pal[Math.floor(Math.random()*4)];
    pc[i*3]=c.r;pc[i*3+1]=c.g;pc[i*3+2]=c.b;
  }
  const pGeo=new THREE.BufferGeometry();
  pGeo.setAttribute('position',new THREE.BufferAttribute(pp,3));
  pGeo.setAttribute('color',new THREE.BufferAttribute(pc,3));
  const pts=new THREE.Points(
    pGeo,
    new THREE.PointsMaterial({
      size:.025,
      vertexColors:true,
      transparent:true,
      opacity:.75
    })
  );
  scene.add(pts);
  
  
  /* ═══════════════════════════════════════════════════════════
     ANIMATION LOOP
     ═══════════════════════════════════════════════════════════ */
  let t=0;
  let mx=0,my=0;
  document.addEventListener('mousemove',e=>{
    mx=(e.clientX/window.innerWidth-.5)*2;
    my=(e.clientY/window.innerHeight-.5)*2;
  });
  
  (function anim(){
    requestAnimationFrame(anim);
    t+=.01;
    
    // Globe
    earth.rotation.y+=.002;
    clouds.rotation.y+=.003;
    
    // Particules
    pts.rotation.y+=.0005;
    
    // Caméra suit la souris
    camera.position.x+=(mx*.5-camera.position.x)*.05;
    camera.position.y+=(-my*.5-camera.position.y)*.05;
    camera.lookAt(0,0,0);
    
    renderer.render(scene,camera);
  })();
  
  console.log('🎨 Scène 3D Hero initialisée avec succès!');
});

/* ═══════════════════════════════════════════════════════════
   THREE.JS - MODULES CARDS (Assiettes 3D)
   ═══════════════════════════════════════════════════════════ */
window.addEventListener('load',function(){
  function createModuleScene(canvasId,imgUrl,ringColor){
    const canvas=document.getElementById(canvasId);
    if(!canvas||typeof THREE==='undefined')return;
    
    const wrap=canvas.parentElement;
    const W=wrap.offsetWidth||300,H=180;
    canvas.width=W;canvas.height=H;
    
    const renderer=new THREE.WebGLRenderer({canvas,antialias:true,alpha:true});
    renderer.setPixelRatio(Math.min(window.devicePixelRatio,2));
    renderer.setSize(W,H);
    
    const scene=new THREE.Scene();
    const camera=new THREE.PerspectiveCamera(42,W/H,0.1,50);
    camera.position.set(0,2.2,3.8);
    camera.lookAt(0,0,0);
    
    function resize(){
      const w=wrap.offsetWidth||300;
      renderer.setSize(w,H);
      camera.aspect=w/H;
      camera.updateProjectionMatrix();
    }
    window.addEventListener('resize',resize);
    
    // Lumières
    scene.add(new THREE.AmbientLight(0xffffff,.7));
    const dl=new THREE.DirectionalLight(0xffffff,1.2);
    dl.position.set(3,5,3);
    scene.add(dl);
    const pl=new THREE.PointLight(ringColor,1.5,12);
    pl.position.set(-2,3,2);
    scene.add(pl);
    
    const group=new THREE.Group();
    
    // Assiette base
    group.add(new THREE.Mesh(
      new THREE.CylinderGeometry(1.1,.95,.12,64),
      new THREE.MeshPhysicalMaterial({
        color:0xF2E8CF,
        metalness:.05,
        roughness:.2,
        clearcoat:1
      })
    ));
    
    // Rebord
    const rim=new THREE.Mesh(
      new THREE.TorusGeometry(1.05,.07,16,64),
      new THREE.MeshPhysicalMaterial({
        color:0xd4c9a8,
        metalness:.1,
        roughness:.2,
        clearcoat:.8
      })
    );
    rim.rotation.x=Math.PI/2;
    rim.position.y=.06;
    group.add(rim);
    
    // Anneau déco
    const dec=new THREE.Mesh(
      new THREE.TorusGeometry(.82,.022,8,64),
      new THREE.MeshPhysicalMaterial({
        color:ringColor,
        metalness:.5,
        roughness:.1,
        emissive:ringColor,
        emissiveIntensity:.4
      })
    );
    dec.rotation.x=Math.PI/2;
    dec.position.y=.07;
    group.add(dec);
    
    // Photo plat
    const texLoader=new THREE.TextureLoader();
    texLoader.crossOrigin='anonymous';
    texLoader.load(imgUrl,function(tex){
      const dish=new THREE.Mesh(
        new THREE.CircleGeometry(.92,64),
        new THREE.MeshBasicMaterial({map:tex,side:THREE.FrontSide})
      );
      dish.rotation.x=-Math.PI/2;
      dish.position.y=.07;
      group.add(dish);
    });
    
    group.position.y=-.3;
    scene.add(group);
    
    // Particules
    const PC=250,pp=new Float32Array(PC*3),pc=new Float32Array(PC*3);
    const pal=[
      new THREE.Color(0x1F3D2B),
      new THREE.Color(0x3A86C4),
      new THREE.Color(0x5B3E96)
    ];
    for(let i=0;i<PC;i++){
      pp[i*3]=(Math.random()-.5)*7;
      pp[i*3+1]=(Math.random()-.5)*5;
      pp[i*3+2]=(Math.random()-.5)*5;
      const c=pal[Math.floor(Math.random()*3)];
      pc[i*3]=c.r;pc[i*3+1]=c.g;pc[i*3+2]=c.b;
    }
    const pGeo=new THREE.BufferGeometry();
    pGeo.setAttribute('position',new THREE.BufferAttribute(pp,3));
    pGeo.setAttribute('color',new THREE.BufferAttribute(pc,3));
    const pts=new THREE.Points(
      pGeo,
      new THREE.PointsMaterial({
        size:.04,
        vertexColors:true,
        transparent:true,
        opacity:.65
      })
    );
    scene.add(pts);
    
    // Anneau orbital
    const ring=new THREE.Mesh(
      new THREE.TorusGeometry(1.6,.012,8,80),
      new THREE.MeshBasicMaterial({
        color:ringColor,
        transparent:true,
        opacity:.35
      })
    );
    ring.rotation.x=Math.PI/2.3;
    scene.add(ring);
    
    let t=0;
    (function anim(){
      requestAnimationFrame(anim);
      t+=.016;
      group.rotation.y=t*.3;
      group.position.y=-.3+Math.sin(t*.7)*.1;
      ring.rotation.z+=.007;
      pts.rotation.y+=.004;
      renderer.render(scene,camera);
    })();
  }
  
  // Init 6 modules
  createModuleScene('mc1','https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',0x3A86C4);
  createModuleScene('mc2','https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&q=80',0x5B3E96);
  createModuleScene('mc3','https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&q=80',0x1F3D2B);
  createModuleScene('mc4','https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&q=80',0x5B3E96);
  createModuleScene('mc5','https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=400&q=80',0x3A86C4);
  createModuleScene('mc6','https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=400&q=80',0x1F3D2B);
  
  console.log('🍽️ Modules 3D initialisés');
});

/* ═══════════════════════════════════════════════════════════
   THREE.JS - FOOTER WAVES
   ═══════════════════════════════════════════════════════════ */
window.addEventListener('load',function(){
  const canvas=document.getElementById('footer-canvas');
  if(!canvas||typeof THREE==='undefined')return;
  
  const renderer=new THREE.WebGLRenderer({canvas,antialias:true,alpha:true});
  renderer.setPixelRatio(Math.min(window.devicePixelRatio,2));
  
  const scene=new THREE.Scene();
  const camera=new THREE.PerspectiveCamera(60,canvas.offsetWidth/200,.1,100);
  camera.position.set(0,2,5);
  camera.lookAt(0,0,0);
  
  function resize(){
    const w=canvas.offsetWidth;
    renderer.setSize(w,200);
    camera.aspect=w/200;
    camera.updateProjectionMatrix();
  }
  resize();
  window.addEventListener('resize',resize);
  
  scene.add(new THREE.AmbientLight(0xffffff,.3));
  const pl=new THREE.PointLight(0x3A86C4,2,20);
  pl.position.set(0,5,3);
  scene.add(pl);
  
  const wGeo=new THREE.PlaneGeometry(20,8,80,30);
  const wave=new THREE.Mesh(
    wGeo,
    new THREE.MeshPhongMaterial({
      color:0x1F3D2B,
      transparent:true,
      opacity:.6,
      side:THREE.DoubleSide
    })
  );
  wave.rotation.x=-Math.PI/3;
  scene.add(wave);
  
  const wGeo2=new THREE.PlaneGeometry(20,8,80,30);
  const wave2=new THREE.Mesh(
    wGeo2,
    new THREE.MeshPhongMaterial({
      color:0x5B3E96,
      transparent:true,
      opacity:.3,
      side:THREE.DoubleSide
    })
  );
  wave2.rotation.x=-Math.PI/3;
  wave2.position.z=.3;
  scene.add(wave2);
  
  let t=0;
  (function anim(){
    requestAnimationFrame(anim);
    t+=.02;
    
    const p=wGeo.attributes.position;
    const p2=wGeo2.attributes.position;
    
    for(let i=0;i<p.count;i++){
      const x=p.getX(i);
      const y=p.getY(i);
      p.setZ(i,Math.sin(x*.8+t)*.3+Math.sin(y*.6+t*1.2)*.2);
      p2.setZ(i,Math.sin(x*.6+t*.8+1)*.25+Math.cos(y*.5+t)*.15);
    }
    
    p.needsUpdate=true;
    p2.needsUpdate=true;
    wGeo.computeVertexNormals();
    wGeo2.computeVertexNormals();
    
    renderer.render(scene,camera);
  })();
  
  console.log('🌊 Vagues 3D footer initialisées');
});
