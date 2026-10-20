@php
    $hwMode = $hwMode ?? 'full';
@endphp
<div class="hw-scene hw-scene--{{ $hwMode }}" aria-hidden="true">
    <div class="hw-sky">
        <span class="hw-moon"></span>
        <span class="hw-star hw-star-1"></span>
        <span class="hw-star hw-star-2"></span>
        <span class="hw-star hw-star-3"></span>
        <span class="hw-star hw-star-4"></span>
        <span class="hw-star hw-star-5"></span>
    </div>

    <div class="hw-bats">
        @foreach ([1, 2, 3, 4] as $n)
            <svg class="hw-bat hw-bat-{{ $n }}" viewBox="0 0 64 28" fill="currentColor">
                <path d="M32 18c-2.2-7.4-6.4-11.2-10.6-11.2 1.8 3.6 1.6 7.2-.2 9.2C15.4 14.4 9.2 7.2 4 2.4c3.6 7.4 5.4 14.8 3.4 18.6 5.6-1.6 11.2.4 15.2 4.2 1.8-2 3.6-4.2 5.4-7.6 1.8 3.4 3.6 5.6 5.4 7.6 4-3.8 9.6-5.8 15.2-4.2-2-3.8-.2-11.2 3.4-18.6-5.2 4.8-11.4 12-17.2 13.6-1.8-2-2-5.6-.2-9.2C38.4 6.8 34.2 10.6 32 18z"/>
            </svg>
        @endforeach
    </div>

    <div class="hw-ghost hw-ghost-1">
        <span class="hw-ghost-eye"></span>
        <span class="hw-ghost-eye"></span>
        <span class="hw-ghost-mouth"></span>
    </div>
    <div class="hw-ghost hw-ghost-2">
        <span class="hw-ghost-eye"></span>
        <span class="hw-ghost-eye"></span>
        <span class="hw-ghost-mouth"></span>
    </div>

    <div class="hw-spider hw-spider-a">
        <span class="hw-web"></span>
        <span class="hw-spider-body">
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
        </span>
    </div>
    <div class="hw-spider hw-spider-b">
        <span class="hw-web"></span>
        <span class="hw-spider-body">
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
        </span>
    </div>
    <div class="hw-spider hw-spider-c">
        <span class="hw-web"></span>
        <span class="hw-spider-body">
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
            <span class="hw-leg"></span>
        </span>
    </div>

    <div class="hw-crawlers">
        @foreach (range(1, 7) as $n)
            <svg class="hw-crawler hw-crawler-{{ $n }}" viewBox="0 0 80 64">
                <g class="hw-crawler-legs" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <path class="hw-crawler-leg is-l" d="M32 26C18 16 10 10 3 5"/>
                    <path class="hw-crawler-leg is-l" d="M31 30C16 26 8 24 1 21"/>
                    <path class="hw-crawler-leg is-l" d="M31 35C16 38 8 44 1 50"/>
                    <path class="hw-crawler-leg is-l" d="M33 38C20 48 12 56 5 61"/>
                    <path class="hw-crawler-leg is-r" d="M48 26C62 16 70 10 77 5"/>
                    <path class="hw-crawler-leg is-r" d="M49 30C64 26 72 24 79 21"/>
                    <path class="hw-crawler-leg is-r" d="M49 35C64 38 72 44 79 50"/>
                    <path class="hw-crawler-leg is-r" d="M47 38C60 48 68 56 75 61"/>
                </g>
                <ellipse cx="40" cy="38" rx="10" ry="12" fill="currentColor"/>
                <ellipse cx="40" cy="26" rx="8" ry="7" fill="currentColor"/>
                <circle cx="36.5" cy="24" r="1.6" fill="#f3e6c8"/>
                <circle cx="43.5" cy="24" r="1.6" fill="#f3e6c8"/>
            </svg>
        @endforeach
    </div>

    <div class="hw-leaves">
        @foreach (range(1, 10) as $n)
            <span class="hw-leaf hw-leaf-{{ $n }}"></span>
        @endforeach
    </div>

    <div class="hw-pumpkins">
        <svg class="hw-pumpkin hw-pumpkin-1" viewBox="0 0 72 68">
            <path d="M34 16c1-9 6-14 11-12-7 3-8 9-6 14" fill="none" stroke="#3f7a28" stroke-width="3" stroke-linecap="round"/>
            <ellipse cx="36" cy="40" rx="28" ry="22" fill="#e56b12"/>
            <ellipse cx="36" cy="40" rx="18" ry="22" fill="#f1841f"/>
            <ellipse cx="24" cy="40" rx="10" ry="20" fill="#d45d0d"/>
            <ellipse cx="48" cy="40" rx="10" ry="20" fill="#d45d0d"/>
            <path d="M24 34l6 7 6-7" fill="#2a1608"/>
            <path d="M36 34l6 7 6-7" fill="#2a1608"/>
            <path d="M24 50c8 8 16 8 24 0 0 0-12 5-24 0z" fill="#2a1608"/>
        </svg>
        <svg class="hw-pumpkin hw-pumpkin-2" viewBox="0 0 72 68">
            <path d="M34 16c1-9 6-14 11-12-7 3-8 9-6 14" fill="none" stroke="#3f7a28" stroke-width="3" stroke-linecap="round"/>
            <ellipse cx="36" cy="40" rx="28" ry="22" fill="#f08a10"/>
            <ellipse cx="36" cy="40" rx="18" ry="22" fill="#ff9c2b"/>
            <path d="M26 33l5-6 5 6z" fill="#2a1608"/>
            <path d="M38 33l5-6 5 6z" fill="#2a1608"/>
            <ellipse cx="36" cy="50" rx="8" ry="4" fill="#2a1608"/>
        </svg>
        <svg class="hw-pumpkin hw-pumpkin-3" viewBox="0 0 72 68">
            <path d="M34 16c1-9 6-14 11-12-7 3-8 9-6 14" fill="none" stroke="#3f7a28" stroke-width="3" stroke-linecap="round"/>
            <ellipse cx="36" cy="40" rx="28" ry="22" fill="#c44d0a"/>
            <ellipse cx="36" cy="40" rx="16" ry="21" fill="#e56b12"/>
            <path d="M24 34l6 5 6-5" fill="#1c1006"/>
            <path d="M36 34l6 5 6-5" fill="#1c1006"/>
            <path d="M26 48c6 7 14 7 20 0-8 3-12 3-20 0z" fill="#1c1006"/>
        </svg>
    </div>

    <div class="hw-fog hw-fog-1"></div>
    <div class="hw-fog hw-fog-2"></div>
</div>
