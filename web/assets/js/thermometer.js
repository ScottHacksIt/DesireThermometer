/* thermometer.js — Desire Thermometer interactive logic */
(function () {
    'use strict';

    const tube       = document.getElementById('thermometer');
    const fill       = document.getElementById('thermoFill');
    const indicator  = document.getElementById('thermoIndicator');
    const levelNum   = document.getElementById('currentLevelNum');
    const levelName  = document.getElementById('currentLevelName');
    const savedMsg   = document.getElementById('savedMsg');
    const scaleLabels= document.querySelectorAll('.scale-label');

    // Partner elements (may not exist if no partner)
    const partnerBtn    = document.getElementById('partnerBtn');
    const partnerReveal = document.getElementById('partnerReveal');
    const closePartnerBtn = document.getElementById('closePartnerBtn');
    const partnerLevelNum  = document.getElementById('partnerLevelNum');
    const partnerLevelName = document.getElementById('partnerLevelName');

    let currentLevel = parseInt(INIT_LEVEL, 10) || 5;
    let saveTimer    = null;
    let isDragging   = false;

    // ── Render helpers ──────────────────────────────────────────

    function levelToPercent(lvl) {
        // level 1 = bottom (0%), level 10 = top (100%)
        return ((lvl - 1) / 9) * 100;
    }

    function render(lvl) {
        const pct = levelToPercent(lvl);
        fill.style.height      = pct + '%';
        indicator.style.bottom = 'calc(' + pct + '% - 3px)';

        levelNum.textContent  = lvl;
        levelName.textContent = SCALE[lvl] || '';

        // Highlight active scale label
        scaleLabels.forEach(function (el) {
            el.classList.toggle('active', parseInt(el.dataset.level, 10) === lvl);
        });
    }

    // ── Save via AJAX ────────────────────────────────────────────

    function scheduleAjaxSave(lvl) {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            const fd = new FormData();
            fd.append('level', lvl);
            fetch('api/api_set_level.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) {
                        savedMsg.style.display = 'block';
                        setTimeout(function () { savedMsg.style.display = 'none'; }, 2000);
                    }
                })
                .catch(function () {});
        }, 400);
    }

    // ── Level from mouse/touch Y position ────────────────────────

    function levelFromY(clientY) {
        const rect = fill.parentElement.getBoundingClientRect(); // thermo-tube
        const relY = clientY - rect.top;
        const ratio = 1 - (relY / rect.height); // 0 at bottom, 1 at top
        const clamped = Math.max(0, Math.min(1, ratio));
        return Math.round(clamped * 9) + 1; // maps to 1–10
    }

    function setLevel(lvl) {
        lvl = Math.max(1, Math.min(10, lvl));
        if (lvl === currentLevel) return;
        currentLevel = lvl;
        render(lvl);
        scheduleAjaxSave(lvl);
    }

    // ── Mouse / touch events on the tube ─────────────────────────

    tube.addEventListener('mousedown', function (e) {
        isDragging = true;
        setLevel(levelFromY(e.clientY));
        e.preventDefault();
    });
    document.addEventListener('mousemove', function (e) {
        if (!isDragging) return;
        setLevel(levelFromY(e.clientY));
    });
    document.addEventListener('mouseup', function () { isDragging = false; });

    tube.addEventListener('touchstart', function (e) {
        isDragging = true;
        setLevel(levelFromY(e.touches[0].clientY));
        e.preventDefault();
    }, { passive: false });
    tube.addEventListener('touchmove', function (e) {
        if (!isDragging) return;
        setLevel(levelFromY(e.touches[0].clientY));
        e.preventDefault();
    }, { passive: false });
    document.addEventListener('touchend', function () { isDragging = false; });

    // ── Click on scale labels ─────────────────────────────────────

    scaleLabels.forEach(function (el) {
        el.addEventListener('click', function () {
            setLevel(parseInt(el.dataset.level, 10));
            scheduleAjaxSave(currentLevel);
        });
    });

    // ── Partner reveal button ─────────────────────────────────────

    if (partnerBtn) {
        partnerBtn.addEventListener('click', function () {
            partnerReveal.style.display = 'block';
            partnerLevelNum.textContent  = '…';
            partnerLevelName.textContent = '';

            fetch('api/api_get_partner.php')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) {
                        partnerLevelNum.textContent  = data.level;
                        partnerLevelName.textContent = data.level_name;
                    } else {
                        partnerLevelNum.textContent  = '?';
                        partnerLevelName.textContent = data.error || 'Not set yet';
                    }
                })
                .catch(function () {
                    partnerLevelNum.textContent  = '?';
                    partnerLevelName.textContent = 'Could not load';
                });
        });
    }

    if (closePartnerBtn) {
        closePartnerBtn.addEventListener('click', function () {
            partnerReveal.style.display = 'none';
        });
    }

    // ── Initial render ────────────────────────────────────────────

    render(currentLevel);

})();
