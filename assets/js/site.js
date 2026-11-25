/* Alora: one continuous scroll piece. Visitor-driven motion (scrub) always runs, even with
   "reduce motion" on; only things that move by themselves (smoothing, reveals) are switched off. */
(() => {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---- Bag: add without leaving the page ----------------------------------
  const toast = $('[data-toast]');
  let toastTimer;
  const say = (msg) => {
    toast.textContent = msg;
    toast.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('is-on'), 2600);
  };
  if (toast.textContent.trim()) toastTimer = setTimeout(() => toast.classList.remove('is-on'), 3200);

  const addToBag = async (id, qty = 1) => {
    const body = new FormData();
    body.set('csrf', window.ALORA.csrf);
    body.set('id', id);
    body.set('qty', qty);
    try {
      const r = await fetch('/alora-website/bag/', { method: 'POST', body, headers: { Accept: 'application/json' } });
      const j = await r.json();
      const c = $('[data-bag-count]');
      c.textContent = j.count;
      c.hidden = !j.count;
      say(j.message);
    } catch {
      say('Could not add that. Please try again.');
    }
  };
  document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-add]');
    if (!b) return;
    e.preventDefault();
    addToBag(b.dataset.add);
  });
  const form = $('[data-add-form]');
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    addToBag(form.id.value, form.qty.value);
  });

  // ---- Product gallery ------------------------------------------------------
  const main = $('[data-main]');
  $$('[data-thumb]').forEach((t) => t.addEventListener('click', () => {
    const p = t.dataset.thumb;
    main.src = `/alora-website/media/${p}-600.webp`;
    main.srcset = [600, 1200, 1600].map((w) => `/alora-website/media/${p}-${w}.webp ${w}w`).join(', ');
    $$('[data-thumb]').forEach((x) => x.removeAttribute('aria-current'));
    t.setAttribute('aria-current', 'true');
  }));

  // ---- Checkout: locker vs door delivery, live total --------------------------
  const money = (c) => 'R' + (c / 100).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  $$('[data-ship]').forEach((r) => r.addEventListener('change', () => {
    $$('[data-for-address]').forEach((box) => { box.hidden = box.dataset.forAddress !== r.dataset.address; });
    const total = $('[data-total]');
    $('[data-ship-cost]').textContent = money(+r.dataset.price);
    total.textContent = money(+total.dataset.subtotal + +r.dataset.price);
  }));

  // ---- Scroll choreography ---------------------------------------------------
  if (!window.gsap || !window.ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);

  if (!calm && window.Lenis) {
    const lenis = new Lenis({ lerp: 0.12, smoothWheel: true });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((t) => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
  }

  // Prepare an SVG path to be "drawn" by animating its dash offset.
  const drawable = (path) => {
    const len = path.getTotalLength();
    gsap.set(path, { strokeDasharray: len, strokeDashoffset: len });
    return path;
  };

  const film = $('[data-film]');
  const nav = $('[data-nav]');
  if (film) {
    const stage = $('.film__stage', film);
    const scene = (n) => $(`[data-scene="${n}"]`, film);
    const pieces = (...k) => k.map((c) => $(`.piece--${c}`, film)).filter(Boolean);
    const branchLines = $$('.film__branch .draw', film).map(drawable);
    const olives = $$('.film__branch .olive', film);

    gsap.set([2, 3, 4, 5].map(scene), { autoAlpha: 0, y: 40 });
    // Centred with percentages so pixel moves (y) can be layered on top.
    gsap.set(['.film__logo', '.piece--gift', '.film__glow'], { xPercent: -50, yPercent: -50, x: 0, y: 0 });
    gsap.set(olives, { scale: 0, transformOrigin: '50% 50%' });

    const out = () => ({ autoAlpha: 0, y: -40, duration: 0.5 });
    const tl = gsap.timeline({
      defaults: { ease: 'power2.inOut', duration: 1 },
      scrollTrigger: {
        trigger: film, pin: stage, start: 'top top', end: '+=520%', scrub: 0.6, anticipatePin: 1,
        // The nav is dark glass while it sits over the black film (until the film turns ivory).
        onUpdate: (s) => nav.classList.toggle('is-over-dark', s.progress < 0.93),
        onRefresh: (s) => nav.classList.toggle('is-over-dark', s.progress < 0.93),
      },
    });

    // 1 → 2: a soft gold light rises behind the logo, the olive branch grows.
    tl.fromTo('.film__glow', { autoAlpha: 0.35, scale: 0.8 }, { autoAlpha: 1, scale: 1.15, duration: 2, ease: 'none' }, 0)
      .to(scene(1), out(), 0.6)
      .to('.film__logo', { scale: 0.7, y: -70, autoAlpha: 0.35, duration: 1 }, 0.6)
      .to('.film__branch', { autoAlpha: 1, duration: 0.3 }, 0.8)
      .to(branchLines, { strokeDashoffset: 0, stagger: 0.08, duration: 1 }, 0.8)
      .to(olives, { scale: 1, stagger: 0.1, ease: 'back.out(2)', duration: 0.4 }, 1.6)
      .to(scene(2), { autoAlpha: 1, y: 0, duration: 0.6 }, 1.1)

    // 2 → 3: jewellery and nails float in.
      .to(scene(2), out(), 2.4)
      .to(['.film__logo', '.film__branch'], { autoAlpha: 0, duration: 0.5 }, 2.4)
      .fromTo(pieces('a', 'b', 'c'), { autoAlpha: 0, y: 120, scale: 0.85, rotate: (i) => [-6, 5, -3][i] },
        { autoAlpha: 1, y: 0, scale: 1, rotate: (i) => [-3, 3, -1][i], stagger: 0.18, duration: 1 }, 2.5)
      .to(scene(3), { autoAlpha: 1, y: 0, duration: 0.6 }, 2.9)

    // 3 → 4: the next pieces take their place.
      .to(scene(3), out(), 4.1)
      .to(pieces('a', 'b', 'c'), { autoAlpha: 0, y: -120, stagger: 0.08, duration: 0.8 }, 4.1)
      .fromTo(pieces('d', 'e', 'f'), { autoAlpha: 0, y: 120, scale: 0.85 },
        { autoAlpha: 1, y: 0, scale: 1, stagger: 0.18, duration: 1 }, 4.4)
      .to(scene(4), { autoAlpha: 1, y: 0, duration: 0.6 }, 4.7)

    // 4 → 5: the gift arrives in the light.
      .to(scene(4), out(), 5.9)
      .to(pieces('d', 'e', 'f'), { autoAlpha: 0, y: -120, stagger: 0.08, duration: 0.8 }, 5.9)
      .fromTo(pieces('gift'), { autoAlpha: 0, scale: 0.6 }, { autoAlpha: 1, scale: 1, duration: 1.1, ease: 'power3.out' }, 6.1)
      .to(scene(5), { autoAlpha: 1, y: 0, duration: 0.6 }, 6.5)

    // 5 → shop: black warms to ivory and the film hands over to the page.
      .to(scene(5), { autoAlpha: 0, duration: 0.5 }, 7.7)
      .to('.film__glow', { autoAlpha: 0, duration: 0.6 }, 7.7)
      // The gift drifts away softly; no shapes or flashes on the way out.
      .to(pieces('gift'), { autoAlpha: 0, y: -30, duration: 0.9, ease: 'sine.inOut' }, 7.8)
      // Ivory on ivory: the stage turns clear and "New in" underneath is simply there.
      .set(stage, { backgroundColor: 'transparent', pointerEvents: 'none', '--shade': 0 }, 9.1)
      .set($$('.film__glow, .film__pieces, .film__copy, .film__logo, .film__branch', stage), { autoAlpha: 0 }, 9.1)
      .to('.film__ivory', { autoAlpha: 0, duration: 0.4 }, 9.1);

    // The Flow film (if present) is scrubbed by the same scroll: the branch blooms through scenes 1–2,
    // the light holds behind the products in 3–5, then floods into ivory silk as the film hands over to the shop.
    const video = $('[data-film-video]', film);
    if (video) {
      // Held in memory as a blob: scrubbing then never waits on the network or on server range support.
      fetch(innerWidth >= 900 ? video.dataset.srcWide : video.dataset.srcNarrow)
        .then((r) => (r.ok ? r.blob() : Promise.reject(r.status)))
        .then((b) => { video.src = URL.createObjectURL(b); })
        .catch(() => {}); // no film: the poster stays and the rest of the scene still plays
      const play = { t: 0 };
      let want = 0, busy = false;
      const seek = () => {
        if (busy || video.readyState < 1) return;
        const t = Math.min(want, (video.duration || 16) - 0.05);
        if (Math.abs(video.currentTime - t) < 0.02) return;
        busy = true;
        video.currentTime = t;
      };
      video.addEventListener('seeked', () => { busy = false; seek(); });
      video.addEventListener('loadedmetadata', seek);
      const to = (t, duration, at) => tl.to(play, { t, duration, ease: 'none', onUpdate: () => { want = play.t; seek(); } }, at);
      to(5.5, 2.4, 0);       // scenes 1–2: the gold light blooms behind the branch
      to(10.0, 5.3, 2.4);    // scenes 3–5: the light holds, warm and dark enough for the words
      to(15.85, 1.4, 7.7);   // hand-over: light floods the frame and settles into ivory silk
      tl.to(stage, { '--shade': 0, duration: 0.8 }, 7.7); // lift the reading shade off the bright silk
      // The linen dissolves gently into the ivory page, so the first row of "New in" is never covered.
      tl.to(video, { autoAlpha: 0, duration: 0.9, ease: 'sine.inOut' }, 9.1);
    } else {
      // No film: the black stage fades evenly to ivory instead.
      tl.fromTo('.film__ivory', { autoAlpha: 0 }, { autoAlpha: 1, duration: 1.1, ease: 'sine.inOut' }, 8.0);
    }

    nav.classList.add('is-over-dark');
  }

  // Categories: vertical scroll walks sideways through the shop.
  const rail = $('[data-rail]');
  if (rail) {
    const track = $('[data-rail-track]', rail);
    const dist = () => Math.max(0, track.scrollWidth - innerWidth);
    gsap.to(track, {
      x: () => -dist(), ease: 'none',
      scrollTrigger: { trigger: rail, pin: $('.rail__pin', rail), start: 'top top', end: () => `+=${dist()}`, scrub: 0.6, invalidateOnRefresh: true },
    });
  }

  // Everything else glides into its place once, in reading order.
  if (!calm) {
    ScrollTrigger.batch('[data-reveal]', {
      start: 'top 90%',
      once: true,
      onEnter: (els) => gsap.to(els, { autoAlpha: 1, y: 0, duration: 0.9, ease: 'power3.out', stagger: 0.07 }),
    });
  }

  addEventListener('load', () => ScrollTrigger.refresh());
})();
