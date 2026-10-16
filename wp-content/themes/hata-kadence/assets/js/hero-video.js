(() => {
  const config = window.hataHeroVideo || {};
  const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');

  if (!config.src || motionPreference.matches || connection?.saveData) return;

  document.querySelectorAll('.hata-video-hero').forEach((hero) => {
    const button = hero.querySelector('.hata-video-toggle');
    const icon = button?.querySelector('.hata-video-toggle__icon');
    if (!button || !icon) return;

    const video = document.createElement('video');
    video.className = 'hata-hero-video';
    video.muted = true;
    video.loop = true;
    video.autoplay = true;
    video.playsInline = true;
    video.preload = 'metadata';
    video.poster = config.poster || '';
    video.src = config.src;
    video.setAttribute('aria-hidden', 'true');
    video.setAttribute('tabindex', '-1');
    hero.prepend(video);

    const setPaused = (paused) => {
      button.setAttribute('aria-pressed', paused ? 'false' : 'true');
      button.setAttribute('aria-label', button.dataset[paused ? 'playLabel' : 'pauseLabel']);
      icon.textContent = paused ? '▶' : 'Ⅱ';
    };

    button.addEventListener('click', () => {
      if (video.paused) video.play().catch(() => setPaused(true));
      else video.pause();
    });
    video.addEventListener('play', () => setPaused(false));
    video.addEventListener('pause', () => setPaused(true));
    video.addEventListener('loadeddata', () => {
      hero.classList.add('is-video-ready');
      button.hidden = false;
    }, { once: true });

    const disableVideo = () => {
      if (!motionPreference.matches && !connection?.saveData) return;
      video.pause();
      video.removeAttribute('src');
      video.load();
      video.remove();
      hero.classList.remove('is-video-ready');
      button.hidden = true;
      motionPreference.removeEventListener('change', disableVideo);
      connection?.removeEventListener?.('change', disableVideo);
    };
    motionPreference.addEventListener('change', disableVideo);
    connection?.addEventListener?.('change', disableVideo);
    video.play().catch(() => setPaused(true));
  });
})();
