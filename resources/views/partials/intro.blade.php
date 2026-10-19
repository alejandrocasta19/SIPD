<div class="sipd-intro" id="sipd-intro" role="dialog" aria-modal="true" aria-label="Video de bienvenida de Cootranshuila">
    <div class="sipd-intro-video-wrap">
        <video class="sipd-intro-video" id="sipd-intro-video" muted playsinline preload="none" aria-label="Video institucional de bienvenida de Cootranshuila">
            <source data-src="{{ asset('images/' . rawurlencode('INTRO 2023 COOTRANSHUILA NO AUDIO-vidkit-2026-10-13.webm')) }}" type="video/webm">
        </video>
    </div>
    <button class="sipd-intro-skip" id="sipd-intro-skip" type="button">Omitir video <span aria-hidden="true">→</span></button>
</div>
<script>
    (function () {
        var intro = document.getElementById('sipd-intro');
        var video = document.getElementById('sipd-intro-video');
        var skip = document.getElementById('sipd-intro-skip');
        if (!intro || !video) {
            return;
        }

        var source = video.querySelector('source');
        var closing = false;
        var closeIntro = function () {
            if (closing) {
                return;
            }
            closing = true;
            video.pause();
            intro.classList.add('sipd-intro-leaving');
            window.setTimeout(function () {
                intro.classList.add('sipd-intro-hidden');
            }, 650);
        };

        skip.addEventListener('click', closeIntro);
        video.addEventListener('ended', closeIntro);
        video.addEventListener('error', function () {
            console.error('No se pudo cargar el video de bienvenida de Cootranshuila.');
            closeIntro();
        });

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            closeIntro();
            return;
        }

        if (source && source.dataset.src) {
            source.src = source.dataset.src;
            video.load();
        }

        video.playbackRate = 1.25;
        video.play().catch(function (error) {
            console.warn('No se pudo iniciar automáticamente el video de bienvenida.', error);
            closeIntro();
        });
    })();
</script>
