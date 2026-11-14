(function () {
    'use strict';

    var hero = document.querySelector('.room-hero');
    var shadowFields = Array.from(document.querySelectorAll('.leaf-shadow-field'));
    var manifestoCopy = document.querySelector('[data-manifesto-copy]');
    var manifestoPhrases = manifestoCopy ? Array.from(manifestoCopy.querySelectorAll('.manifesto-phrase')) : [];
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var finePointer = window.matchMedia('(pointer: fine)');
    var framePending = false;

    var segmentGraphemes = function (text) {
        if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
            var segmenter = new Intl.Segmenter('vi', { granularity: 'grapheme' });
            return Array.from(segmenter.segment(text), function (part) {
                return part.segment;
            });
        }
        return Array.from(text);
    };

    var HEAVY_VI_RE = /[ÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼẾỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴỶỸàáâãèéêìíòóôõùúýăđĩũơưạảấầẩẫậắằẳẵặẹẻẽếềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵỷỹ]/;

    var organicRange = function (min, max) {
        return Math.round(min + Math.random() * (max - min));
    };

    var buildManifestoPlan = function (phrases) {
        var chunks = phrases.map(function (phrase) {
            return phrase.textContent.replace(/\s+/g, ' ').trim();
        }).filter(Boolean);

        var graphemes = [];
        var atMs = [];
        var pauseAfter = [];
        var cursorMs = 70;

        chunks.forEach(function (chunk, chunkIndex) {
            if (chunkIndex > 0) {
                graphemes.push(' ');
                atMs.push(cursorMs);
                pauseAfter.push(0);
                cursorMs += organicRange(12, 22);
            }
            var chars = segmentGraphemes(chunk);
            var wordCharPos = 0;

            chars.forEach(function (ch, idx) {
                var isLastInChunk = idx === chars.length - 1;
                var isLastOverall = isLastInChunk && chunkIndex === chunks.length - 1;
                var nextCh = !isLastInChunk ? chars[idx + 1] : '';

                var delay;
                if (ch === ' ') {
                    wordCharPos = 0;
                    delay = organicRange(12, 22);
                } else {
                    wordCharPos += 1;
                    if (wordCharPos === 1) {
                        delay = organicRange(12, 20);
                    } else if (wordCharPos === 2) {
                        delay = organicRange(7, 13);
                    } else {
                        delay = organicRange(6, 11);
                    }
                    if (HEAVY_VI_RE.test(ch) || (ch >= 'A' && ch <= 'Z')) {
                        delay += organicRange(2, 6);
                    }
                    if (nextCh && /[.—]/.test(nextCh)) {
                        delay += organicRange(10, 18);
                    }
                }

                var pause = 0;
                if (!isLastInChunk) {
                    if (ch === ',') pause = organicRange(80, 110);
                } else if (!isLastOverall) {
                    if (/[.!?]$/.test(chunk)) pause = organicRange(440, 520);
                    else if (/[—–:]$/.test(chunk)) pause = organicRange(360, 440);
                }

                graphemes.push(ch);
                atMs.push(cursorMs);
                pauseAfter.push(pause);

                if (!isLastOverall) {
                    cursorMs += (pause > 0 ? pause : delay);
                }
            });
        });

        var wordRuns = new Array(graphemes.length);
        var i = 0;
        while (i < graphemes.length) {
            if (graphemes[i] === ' ') {
                i += 1;
                continue;
            }
            var start = i;
            while (i < graphemes.length && graphemes[i] !== ' ') {
                i += 1;
            }
            var run = [start, i];
            for (var j = start; j < i; j += 1) {
                wordRuns[j] = run;
            }
        }

        return {
            graphemes: graphemes,
            atMs: atMs,
            pauseAfter: pauseAfter,
            wordRuns: wordRuns,
            totalCount: graphemes.length,
        };
    };

    var manifestoState = {
        plan: null,
        stepIndex: 0,
        startTime: 0,
        hasStarted: false,
        isFinished: false,
        rafId: 0,
        completeTimerId: 0,
        stageNode: null,
        beforeNode: null,
        activeTypedNode: null,
        activeUntypedNode: null,
        afterNode: null,
        caretNode: null,
        observer: null,
    };

    var clearManifestoTimers = function () {
        if (manifestoState.rafId) {
            window.cancelAnimationFrame(manifestoState.rafId);
            manifestoState.rafId = 0;
        }
        if (manifestoState.completeTimerId) {
            window.clearTimeout(manifestoState.completeTimerId);
            manifestoState.completeTimerId = 0;
        }
    };

    var renderManifestoSplit = function (count) {
        var plan = manifestoState.plan;
        if (!plan || !manifestoState.stageNode) return;
        var k = Math.max(0, Math.min(plan.totalCount, count));
        var run = k < plan.totalCount && plan.wordRuns[k]
            ? plan.wordRuns[k]
            : (k > 0 ? plan.wordRuns[k - 1] : null);

        if (run) {
            var wStart = run[0];
            var wEnd = run[1];
            manifestoState.beforeNode.nodeValue = plan.graphemes.slice(0, wStart).join('');
            manifestoState.activeTypedNode.nodeValue = plan.graphemes.slice(wStart, k).join('');
            manifestoState.activeUntypedNode.nodeValue = plan.graphemes.slice(k, wEnd).join('');
            manifestoState.afterNode.nodeValue = plan.graphemes.slice(wEnd).join('');
        } else {
            manifestoState.beforeNode.nodeValue = plan.graphemes.slice(0, k).join('');
            manifestoState.activeTypedNode.nodeValue = '';
            manifestoState.activeUntypedNode.nodeValue = '';
            manifestoState.afterNode.nodeValue = plan.graphemes.slice(k).join('');
        }
    };

    var showStaticManifesto = function () {
        if (!manifestoCopy) return;
        clearManifestoTimers();
        document.body.classList.remove('js-manifesto-ready');
        manifestoCopy.classList.remove('is-manifesto-staged', 'is-typed-complete');
        if (manifestoState.stageNode) {
            manifestoState.stageNode.hidden = true;
        }
    };

    var finishManifestoTyping = function () {
        if (!manifestoCopy || manifestoState.isFinished) return;
        manifestoState.isFinished = true;
        clearManifestoTimers();
        renderManifestoSplit(manifestoState.plan.totalCount);
        if (manifestoState.caretNode) {
            manifestoState.caretNode.classList.add('is-paused');
        }
        manifestoState.completeTimerId = window.setTimeout(function () {
            if (manifestoState.caretNode) {
                manifestoState.caretNode.classList.remove('is-paused');
                manifestoState.caretNode.classList.add('is-done');
            }
            manifestoState.completeTimerId = window.setTimeout(function () {
                if (reducedMotion.matches) return;
                manifestoCopy.classList.remove('is-manifesto-staged');
                manifestoCopy.classList.add('is-typed-complete');
                if (manifestoState.stageNode) {
                    manifestoState.stageNode.hidden = true;
                }
            }, 260);
        }, 380);
    };

    var tickManifestoTyping = function (now) {
        if (reducedMotion.matches) {
            showStaticManifesto();
            return;
        }
        var plan = manifestoState.plan;
        var elapsed = now - manifestoState.startTime;
        var advanced = false;
        while (manifestoState.stepIndex < plan.totalCount && plan.atMs[manifestoState.stepIndex] <= elapsed) {
            manifestoState.stepIndex += 1;
            advanced = true;
        }
        if (advanced) {
            renderManifestoSplit(manifestoState.stepIndex);
        }
        if (manifestoState.stepIndex >= plan.totalCount) {
            finishManifestoTyping();
            return;
        }
        if (manifestoState.caretNode) {
            var remainingWait = plan.atMs[manifestoState.stepIndex] - elapsed;
            var prevPause = manifestoState.stepIndex > 0 ? plan.pauseAfter[manifestoState.stepIndex - 1] : 0;
            var isPausing = manifestoState.stepIndex === 0 || (prevPause >= 200 && remainingWait > 40);
            manifestoState.caretNode.classList.toggle('is-paused', isPausing);
        }
        manifestoState.rafId = window.requestAnimationFrame(tickManifestoTyping);
    };

    var startManifestoTyping = function () {
        if (!manifestoCopy || manifestoState.hasStarted || manifestoState.isFinished || reducedMotion.matches) {
            return;
        }
        manifestoState.hasStarted = true;
        if (manifestoState.observer) {
            manifestoState.observer.disconnect();
            manifestoState.observer = null;
        }
        if (manifestoState.stageNode) {
            manifestoState.stageNode.hidden = false;
        }
        if (manifestoState.caretNode) {
            manifestoState.caretNode.classList.add('is-paused');
        }
        manifestoState.startTime = window.performance ? window.performance.now() : Date.now();
        manifestoState.rafId = window.requestAnimationFrame(tickManifestoTyping);
    };

    var initManifestoTypewriter = function () {
        if (!manifestoCopy || manifestoPhrases.length === 0) return;
        if (reducedMotion.matches) {
            showStaticManifesto();
            return;
        }
        if (manifestoState.isFinished) {
            document.body.classList.add('js-manifesto-ready');
            manifestoCopy.classList.remove('is-manifesto-staged');
            manifestoCopy.classList.add('is-typed-complete');
            return;
        }
        if (!manifestoState.stageNode) {
            manifestoState.plan = buildManifestoPlan(manifestoPhrases);
            var stageNode = document.createElement('span');
            stageNode.className = 'manifesto-stage';
            stageNode.setAttribute('aria-hidden', 'true');

            var typedSpan = document.createElement('span');
            typedSpan.className = 'manifesto-typed';
            var beforeNode = document.createTextNode('');
            typedSpan.appendChild(beforeNode);

            var activeWord = document.createElement('span');
            activeWord.className = 'manifesto-word-active';
            var activeTypedSpan = document.createElement('span');
            activeTypedSpan.className = 'manifesto-typed';
            var activeTypedNode = document.createTextNode('');
            activeTypedSpan.appendChild(activeTypedNode);
            var caretNode = document.createElement('span');
            caretNode.className = 'manifesto-caret is-paused';
            var activeUntypedSpan = document.createElement('span');
            activeUntypedSpan.className = 'manifesto-untyped';
            var activeUntypedNode = document.createTextNode('');
            activeUntypedSpan.appendChild(activeUntypedNode);
            activeWord.appendChild(activeTypedSpan);
            activeWord.appendChild(caretNode);
            activeWord.appendChild(activeUntypedSpan);

            var untypedSpan = document.createElement('span');
            untypedSpan.className = 'manifesto-untyped';
            var afterNode = document.createTextNode('');
            untypedSpan.appendChild(afterNode);

            stageNode.appendChild(typedSpan);
            stageNode.appendChild(activeWord);
            stageNode.appendChild(untypedSpan);

            var inset = manifestoCopy.querySelector('.manifesto-inset');
            if (inset) {
                inset.insertAdjacentElement('afterend', stageNode);
            } else {
                manifestoCopy.insertBefore(stageNode, manifestoCopy.firstChild);
            }

            manifestoState.stageNode = stageNode;
            manifestoState.beforeNode = beforeNode;
            manifestoState.activeTypedNode = activeTypedNode;
            manifestoState.activeUntypedNode = activeUntypedNode;
            manifestoState.afterNode = afterNode;
            manifestoState.caretNode = caretNode;
            renderManifestoSplit(0);
        }
        document.body.classList.add('js-manifesto-ready');
        manifestoCopy.classList.add('is-manifesto-staged');
        manifestoCopy.classList.remove('is-typed-complete');
        manifestoState.stageNode.hidden = false;

        if (manifestoState.hasStarted) return;
        var rect = manifestoCopy.getBoundingClientRect();
        var vh = window.innerHeight || document.documentElement.clientHeight || 800;
        if (rect.top < vh * 0.92 && rect.bottom > 0) {
            startManifestoTyping();
            return;
        }
        if ('IntersectionObserver' in window) {
            manifestoState.observer = new IntersectionObserver(function (entries) {
                if (entries.some(function (entry) { return entry.isIntersecting; })) {
                    startManifestoTyping();
                }
            }, {
                rootMargin: '0px 0px -8% 0px',
                threshold: 0.12,
            });
            manifestoState.observer.observe(manifestoCopy);
        } else {
            startManifestoTyping();
        }
    };

    var updateScrollEffects = function () {
        framePending = false;
        if (hero) {
            var distance = reducedMotion.matches
                ? 0
                : Math.min(hero.offsetHeight, Math.max(0, -hero.getBoundingClientRect().top));
            hero.style.setProperty('--hero-travel', (distance * 0.25) + 'px');
            if (reducedMotion.matches) {
                hero.style.setProperty('--hero-mx', '0px');
                hero.style.setProperty('--hero-my', '0px');
            }
        }
        if (shadowFields.length > 0) {
            var vh = window.innerHeight || 800;
            shadowFields.forEach(function (field) {
                if (reducedMotion.matches) {
                    field.style.setProperty('--shadow-y', '0px');
                    return;
                }
                var parent = field.parentElement;
                if (!parent) return;
                var rect = parent.getBoundingClientRect();
                var centerDelta = (rect.top + rect.height * 0.5) - vh * 0.5;
                var drift = Math.max(-42, Math.min(42, centerDelta * -0.06));
                field.style.setProperty('--shadow-y', drift.toFixed(1) + 'px');
            });
        }
        if (manifestoState.stageNode && !manifestoState.hasStarted && !manifestoState.isFinished && !reducedMotion.matches) {
            var mRect = manifestoCopy.getBoundingClientRect();
            if (mRect.top < (window.innerHeight || 800) * 0.92 && mRect.bottom > 0) {
                startManifestoTyping();
            }
        }
    };

    var scheduleEffects = function () {
        if (framePending) return;
        framePending = true;
        window.requestAnimationFrame(updateScrollEffects);
    };

    window.addEventListener('scroll', scheduleEffects, { passive: true });
    window.addEventListener('resize', scheduleEffects);
    reducedMotion.addEventListener('change', function () {
        updateScrollEffects();
        initManifestoTypewriter();
    });
    updateScrollEffects();
    initManifestoTypewriter();

    if (hero) {
        hero.addEventListener('pointermove', function (event) {
            if (reducedMotion.matches || !finePointer.matches) return;
            var rect = hero.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            var nx = ((event.clientX - rect.left) / rect.width) - 0.5;
            var ny = ((event.clientY - rect.top) / rect.height) - 0.5;
            hero.style.setProperty('--hero-mx', (nx * -24).toFixed(1) + 'px');
            hero.style.setProperty('--hero-my', (ny * -18).toFixed(1) + 'px');
        }, { passive: true });
    }

    var scene = document.querySelector('.room-scene');
    var spots = Array.from(document.querySelectorAll('.room-spot'));
    if (scene) {
        spots.forEach(function (spot) {
            spot.addEventListener('toggle', function () {
                if (!spot.open) return;
                spots.forEach(function (other) {
                    if (other !== spot) other.open = false;
                });
            });
        });
        scene.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            var openSpot = spots.find(function (spot) { return spot.open; });
            if (openSpot) {
                openSpot.open = false;
                openSpot.querySelector('summary').focus({ preventScroll: true });
            }
        });
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.room-spot')) {
                spots.forEach(function (spot) { spot.open = false; });
            }
        });
    }

    var featureTrack = document.querySelector('[data-feature-track]');
    if (featureTrack) {
        var prevBtn = document.querySelector('[data-feature-prev]');
        var nextBtn = document.querySelector('[data-feature-next]');
        var syncFeatureControls = function () {
            var maxScroll = Math.max(0, featureTrack.scrollWidth - featureTrack.clientWidth - 2);
            if (prevBtn) prevBtn.disabled = featureTrack.scrollLeft <= 2;
            if (nextBtn) nextBtn.disabled = featureTrack.scrollLeft >= maxScroll;
        };
        var scrollFeaturesBy = function (direction) {
            var card = featureTrack.querySelector('.feature-card');
            var step = card ? card.getBoundingClientRect().width + 20 : 320;
            featureTrack.scrollBy({
                left: direction * step,
                behavior: reducedMotion.matches ? 'auto' : 'smooth',
            });
        };
        if (prevBtn) prevBtn.addEventListener('click', function () { scrollFeaturesBy(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { scrollFeaturesBy(1); });
        featureTrack.addEventListener('scroll', syncFeatureControls, { passive: true });
        window.addEventListener('resize', syncFeatureControls);
        syncFeatureControls();
    }
})();
