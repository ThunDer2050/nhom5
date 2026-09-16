/* Voice Commerce AI Engine v1.2.0 - Nhóm 5 */
(function ($) {
    'use strict';
    if (typeof VoiceCommerce === 'undefined') return;

    var VC          = VoiceCommerce;
    var recognition = null;
    var isListening = false;
    var panelOpen   = false;
    var toastTimer       = null;
    var ttsEnabled       = (localStorage.getItem('vc_tts_enabled') !== 'false');
    var a11yActive       = (localStorage.getItem('vc_a11y_mode') === 'true');
    var highContrast     = (localStorage.getItem('vc_high_contrast') === 'true');
    var handsFreeActive  = (localStorage.getItem('vc_handsfree') === 'true');
    var currentZoom      = 100;
    var a11yDebounce     = null;
    var wakeActiveWindow = false;
    var wakeTimer        = null;

    var WAKE_WORDS = ['tro ly oi', 'tro ly', 'hey can tho', 'alo can tho', 'xin chao tro ly', 'xin chao', 'ok shop', 'hey assistant'];

    // Elements
    var $wrapper          = $('#vc-wrapper');
    var $micBtn           = $('#vc-mic-btn');
    var $a11yBtn          = $('#vc-a11y-btn');
    var $a11yMasterToggle = $('#vc-a11y-master-toggle');
    var $a11yStatusTag    = $('#vc-a11y-status-tag');
    var $srLive           = $('#vc-sr-live');
    var $handsFreeToggle  = $('#vc-handsfree-toggle');
    var $handsFreeBadge   = $('#vc-handsfree-badge');
    var $panel            = $('#vc-panel');
    var $status           = $('#vc-status');
    var $transcript       = $('#vc-transcript');
    var $aiReply          = $('#vc-ai-reply');
    var $result           = $('#vc-result');
    var $closeBtn         = $('#vc-close-btn');
    var $toast            = $('#vc-toast');
    var $ttsToggle        = $('#vc-tts-toggle');
    var $tabs             = $('.vc-tab-btn');
    var $tabPanes         = $('.vc-tab-content');

    /* ── Audio Chime & Helpers ────────────────────────────── */
    function playChime() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            var ctx = new AudioCtx();
            var now = ctx.currentTime;
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, now);
            osc.frequency.exponentialRampToValueAtTime(880, now + 0.12);

            gain.gain.setValueAtTime(0.18, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.32);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.35);
        } catch (e) {}
    }

    function removeAccentsJS(str) {
        if (!str) return '';
        return str.normalize('NFD')
                  .replace(/[\u0300-\u036f]/g, '')
                  .replace(/đ/g, 'd')
                  .replace(/Đ/g, 'D')
                  .toLowerCase()
                  .trim();
    }

    /* ── Helper Functions ─────────────────────────────────── */
    function esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function setStatus(msg, type) {
        $status.removeClass('vc-status--listening vc-status--success vc-status--loading').text(msg);
        if (type) $status.addClass('vc-status--' + type);
    }

    function showToast(msg, type) {
        clearTimeout(toastTimer);
        $toast.removeClass('vc-toast--show vc-toast--success vc-toast--error vc-toast--info')
              .text(msg).addClass('vc-toast--' + (type || 'info'));
        requestAnimationFrame(function () {
            $toast.addClass('vc-toast--show');
            toastTimer = setTimeout(function () {
                $toast.removeClass('vc-toast--show');
            }, 3200);
        });
    }

    function openPanel(tab) {
        panelOpen = true;
        $panel.addClass('vc-panel--open');
        if (tab) switchTab(tab);
    }

    function closePanel() {
        panelOpen = false;
        $panel.removeClass('vc-panel--open');
    }

    function switchTab(tabName) {
        $tabs.removeClass('vc-tab-btn--active');
        $tabPanes.removeClass('vc-tab-content--active');
        $('[data-tab="' + tabName + '"]').addClass('vc-tab-btn--active');
        $('#vc-tab-' + tabName).addClass('vc-tab-content--active');
    }

    /* ── Text to Speech (TTS) ────────────────────────────── */
    function speakText(text) {
        if (!ttsEnabled || !window.speechSynthesis || !text) return;
        try {
            window.speechSynthesis.cancel();
            var utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'vi-VN';
            utterance.rate = 1.05;
            utterance.pitch = 1.0;
            window.speechSynthesis.speak(utterance);
        } catch (e) {
            console.warn('TTS error:', e);
        }
    }

    function updateTTSButton() {
        if (ttsEnabled) {
            $ttsToggle.addClass('vc-tts-active');
            $ttsToggle.find('.vc-sound-on').show();
            $ttsToggle.find('.vc-sound-off').hide();
        } else {
            $ttsToggle.removeClass('vc-tts-active');
            $ttsToggle.find('.vc-sound-on').hide();
            $ttsToggle.find('.vc-sound-off').show();
        }
    }

    /* ── Speech Recognition & Wake Word Detection ───────── */
    function buildRecognition() {
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SR) {
            showToast(VC.i18n.notSupported, 'error');
            return null;
        }

        var r = new SR();
        r.lang            = (VC.language === 'auto') ? 'vi-VN' : VC.language;
        r.continuous      = handsFreeActive ? true : false;
        r.interimResults  = true;
        r.maxAlternatives = 2;

        r.onstart = function () {
            isListening = true;
            $micBtn.addClass('vc-listening');
            if (handsFreeActive) {
                setStatus('Đang lắng nghe: Nói "Trợ lý ơi" để ra lệnh...', 'listening');
            } else {
                setStatus(VC.i18n.listening, 'listening');
                $transcript.text('');
                $aiReply.hide().text('');
                $result.html('');
                openPanel('live');
            }
        };

        r.onresult = function (e) {
            var interim = '', final = '';
            for (var i = e.resultIndex; i < e.results.length; i++) {
                if (e.results[i].isFinal) final   += e.results[i][0].transcript;
                else                      interim += e.results[i][0].transcript;
            }

            var spoken = (final || interim).trim();
            var spokenClean = removeAccentsJS(spoken);

            // 1. Hands-free Wake Word detection
            if (handsFreeActive) {
                var detectedWake = null;
                for (var w = 0; w < WAKE_WORDS.length; w++) {
                    var idx = spokenClean.indexOf(WAKE_WORDS[w]);
                    if (idx !== -1) {
                        detectedWake = {
                            word: WAKE_WORDS[w],
                            remainder: spokenClean.substring(idx + WAKE_WORDS[w].length).trim()
                        };
                        break;
                    }
                }

                if (detectedWake) {
                    playChime();
                    openPanel('live');

                    if (detectedWake.remainder.length > 2) {
                        // Spoke wake word + command together: e.g. "Trợ lý ơi tìm bánh tét"
                        setStatus('Đã nhận: ' + detectedWake.remainder, 'listening');
                        $transcript.text(spoken);
                        wakeActiveWindow = false;
                        clearTimeout(wakeTimer);
                        handleTranscript(detectedWake.remainder);
                        return;
                    } else {
                        // Spoke wake word alone: e.g. "Trợ lý ơi"
                        speakText('Dạ, em nghe đây ạ!');
                        setStatus('Dạ em nghe! Bạn hãy nói câu lệnh...', 'listening');
                        $transcript.text('🎤 ' + spoken);
                        wakeActiveWindow = true;
                        clearTimeout(wakeTimer);
                        wakeTimer = setTimeout(function () {
                            wakeActiveWindow = false;
                            setStatus('Đang lắng nghe: Nói "Trợ lý ơi" để ra lệnh...', '');
                        }, 8000);
                        return;
                    }
                }

                // If user called wake word earlier and is now speaking the command
                if (wakeActiveWindow && final) {
                    wakeActiveWindow = false;
                    clearTimeout(wakeTimer);
                    $transcript.text(final);
                    handleTranscript(final.trim());
                    return;
                }
            }

            // 2. Normal mode
            $transcript.text(final || interim);
            if (final && !handsFreeActive) {
                stopListening();
                handleTranscript(final.trim());
            }
        };

        r.onerror = function (e) {
            if (e.error === 'not-allowed') {
                showToast(VC.i18n.micDenied, 'error');
                handsFreeActive = false;
                updateHandsFreeUI();
            } else if (e.error !== 'aborted' && e.error !== 'no-speech') {
                console.warn('Speech recognition error:', e.error);
            }
        };

        r.onend = function () {
            isListening = false;
            $micBtn.removeClass('vc-listening');

            // In Hands-free mode, auto restart listening seamlessly
            if (handsFreeActive) {
                setTimeout(function () {
                    if (handsFreeActive) {
                        try { startListening(); } catch (err) {}
                    }
                }, 350);
            } else if (!$transcript.text()) {
                setStatus('Nhấn micro hoặc Alt+M để nói lệnh...', '');
            }
        };

        return r;
    }

    function startListening() {
        if (recognition) {
            try { recognition.stop(); } catch (e) {}
            recognition = null;
        }
        recognition = buildRecognition();
        if (!recognition) return;
        try { recognition.start(); } catch (e) {}
    }

    function stopListening() {
        if (recognition) {
            try { recognition.stop(); } catch (e) {}
            recognition = null;
        }
        isListening = false;
        $micBtn.removeClass('vc-listening');
    }

    function toggleListening() {
        if (!panelOpen) openPanel('live');
        if (isListening) stopListening();
        else startListening();
    }

    /* ── Transcript Processing with Gemini AI ────────────── */
    function handleTranscript(text) {
        setStatus(VC.i18n.processing, 'loading');
        showToast('Gemini AI đang phân tích...', 'info');

        $.ajax({
            url: VC.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vc_ai_command',
                nonce: VC.nonce,
                transcript: text,
                current_post_id: VC.currentPostId || 0
            },
            success: function (res) {
                if (res.success) {
                    dispatchAction(res.data);
                } else {
                    fallbackLocalCommand(text);
                }
            },
            error: function () {
                fallbackLocalCommand(text);
            }
        });
    }

    function fallbackLocalCommand(text) {
        $.ajax({
            url: VC.ajaxUrl,
            type: 'POST',
            data: { action: 'vc_parse_command', nonce: VC.nonce, transcript: text },
            success: function (res) {
                if (res.success && res.data.action !== 'unknown') {
                    dispatchAction({
                        action: res.data.action,
                        keyword: res.data.keyword,
                        quantity: 1,
                        reply: 'Thực hiện: ' + res.data.matched
                    });
                } else {
                    setStatus(VC.i18n.noCommand, '');
                    showToast(VC.i18n.noCommand, 'error');
                }
            },
            error: function () {
                setStatus(VC.i18n.noCommand, '');
                showToast(VC.i18n.noCommand, 'error');
            }
        });
    }

    /* ── Action Dispatcher ───────────────────────────────── */
    function dispatchAction(data) {
        var action   = data.action;
        var keyword  = data.keyword || '';
        var reply    = data.reply || '';

        // Display AI reply & speak it
        if (reply) {
            $aiReply.text(reply).fadeIn(200);
            speakText(reply);
        }

        switch (action) {
            case 'search':
                setStatus('Đang tìm: "' + keyword + '"', 'success');
                showToast('Tìm kiếm: "' + keyword + '"', 'info');
                if (data.search_results && data.search_results.length) {
                    renderResults(data.search_results, keyword, VC.searchUrl + encodeURIComponent(keyword));
                } else {
                    doLiveSearch(keyword);
                }
                break;

            case 'add_to_cart':
                if (data.product_name) {
                    setStatus('Đã thêm: ' + data.product_name, 'success');
                    showToast('Đã thêm ' + data.product_name + ' vào giỏ!', 'success');
                    $(document.body).trigger('added_to_cart');
                } else if (VC.currentPostId) {
                    addToCart(VC.currentPostId);
                } else {
                    showToast('Vui lòng nói rõ tên sản phẩm bạn muốn mua.', 'info');
                    if (keyword) doLiveSearch(keyword);
                }
                break;

            case 'view_cart':
                setStatus('Đang mở giỏ hàng...', 'success');
                showToast('Chuyển đến giỏ hàng...', 'info');
                setTimeout(function () { window.location.href = VC.cartUrl; }, 600);
                break;

            case 'checkout':
                setStatus('Đang chuyển đến thanh toán...', 'success');
                showToast('Chuyển đến thanh toán...', 'info');
                setTimeout(function () { window.location.href = VC.checkoutUrl; }, 600);
                break;

            case 'clear_cart':
                clearCart();
                break;

            case 'go_home':
                setStatus('Đang về trang chủ...', 'success');
                showToast('Về trang chủ...', 'info');
                setTimeout(function () { window.location.href = VC.homeUrl; }, 600);
                break;

            case 'go_shop':
                setStatus('Đang mở cửa hàng...', 'success');
                showToast('Mở Cửa hàng đặc sản...', 'info');
                setTimeout(function () { window.location.href = VC.shopUrl; }, 600);
                break;

            case 'go_about':
                setStatus('Đang mở trang Giới thiệu...', 'success');
                showToast('Mở trang Giới thiệu...', 'info');
                setTimeout(function () { window.location.href = VC.aboutUrl; }, 600);
                break;

            case 'go_news':
                setStatus('Đang mở trang Tin tức...', 'success');
                showToast('Mở Tin tức & Bài viết...', 'info');
                setTimeout(function () { window.location.href = VC.newsUrl; }, 600);
                break;

            case 'go_contact':
                setStatus('Đang mở trang Liên hệ...', 'success');
                showToast('Mở trang Liên hệ...', 'info');
                setTimeout(function () { window.location.href = VC.contactUrl; }, 600);
                break;

            case 'go_account':
                setStatus('Đang mở Tài khoản...', 'success');
                showToast('Mở trang Tài khoản...', 'info');
                setTimeout(function () { window.location.href = VC.accountUrl; }, 600);
                break;

            case 'history_back':
                setStatus('Đang quay lại...', 'success');
                showToast('Quay lại trang trước...', 'info');
                setTimeout(function () { window.history.back(); }, 400);
                break;

            case 'scroll_top':
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setStatus('Đã cuộn lên đầu trang', 'success');
                showToast('Lên đầu trang', 'info');
                break;

            case 'scroll_bottom':
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                setStatus('Đã cuộn xuống cuối trang', 'success');
                showToast('Xuống cuối trang', 'info');
                break;

            case 'scroll_down':
                window.scrollBy({ top: 600, behavior: 'smooth' });
                setStatus('Đã cuộn xuống', 'success');
                break;

            case 'scroll_up':
                window.scrollBy({ top: -600, behavior: 'smooth' });
                setStatus('Đã cuộn lên', 'success');
                break;

            case 'reload_page':
                setStatus('Đang tải lại trang...', 'success');
                showToast('Đang làm mới trang...', 'info');
                setTimeout(function () { window.location.reload(); }, 500);
                break;

            case 'dark_mode':
                toggleDarkMode();
                break;

            case 'read_page':
                readPageContent();
                break;

            case 'stop_reading':
                stopReadingContent();
                break;

            case 'zoom_in':
                zoomPage(10);
                break;

            case 'zoom_out':
                zoomPage(-10);
                break;

            case 'zoom_reset':
                zoomPage(0);
                break;

            case 'toggle_menu':
                switchTab('menu');
                openPanel();
                break;

            case 'accessibility_mode':
                toggleAccessibility();
                break;

            case 'hands_free_mode':
                toggleHandsFree();
                break;

            case 'cart_summary':
                readCartSummary();
                break;

            case 'read_products':
                readProductsOnPage();
                break;

            case 'high_contrast':
                toggleHighContrast();
                break;

            case 'ai_chat':
                setStatus('Gemini AI phản hồi', 'success');
                break;

            default:
                setStatus(VC.i18n.noCommand, '');
                showToast(VC.i18n.noCommand, 'error');
                break;
        }
    }

    /* ── Live Search ─────────────────────────────────────── */
    function doLiveSearch(kw) {
        if (!kw) return;
        $.ajax({
            url: VC.ajaxUrl,
            type: 'POST',
            data: { action: 'vc_search', nonce: VC.nonce, keyword: kw },
            success: function (res) {
                if (res.success && res.data.results && res.data.results.length) {
                    renderResults(res.data.results, kw, res.data.search_url);
                } else {
                    $result.html('<div style="font-size:12px;color:#94a3b8;padding:8px 0;">Không tìm thấy sản phẩm "' + esc(kw) + '".</div>');
                }
            },
            error: function () {
                window.location.href = VC.searchUrl + encodeURIComponent(kw);
            }
        });
    }

    function renderResults(items, kw, searchUrl) {
        var html = '<p style="font-size:12px;color:#94a3b8;margin:0 0 8px">Kết quả: <strong style="color:#a78bfa">' + esc(kw) + '</strong></p>';
        items.slice(0, 4).forEach(function (item) {
            html += '<a class="vc-result-item" href="' + esc(item.url) + '">';
            if (item.image) html += '<img src="' + esc(item.image) + '" alt="">';
            html += '<div class="vc-result-item-text"><div class="vc-result-item-title">' + esc(item.title) + '</div>';
            if (item.price) html += '<div class="vc-result-item-price">' + item.price + '</div>';
            html += '</div>';
            if (item.type === 'product') {
                html += '<button class="vc-result-add-btn" onclick="event.preventDefault();vcATC(' + item.id + ')">+ Giỏ</button>';
            }
            html += '</a>';
        });
        if (items.length > 4) {
            html += '<a href="' + esc(searchUrl) + '" style="display:block;text-align:center;color:#a78bfa;font-size:12px;margin-top:6px">Xem tất cả ' + items.length + ' kết quả &rarr;</a>';
        }
        $result.html(html);
        openPanel('live');
    }

    /* ── Cart Operations ─────────────────────────────────── */
    function addToCart(id) {
        setStatus('Đang thêm vào giỏ hàng...', 'loading');
        $.ajax({
            url: VC.ajaxUrl,
            type: 'POST',
            data: { action: 'vc_add_to_cart', nonce: VC.nonce, product_id: id, quantity: 1 },
            success: function (res) {
                if (res.success) {
                    setStatus('Đã thêm vào giỏ (' + res.data.cart_count + ' món)', 'success');
                    showToast('Đã thêm ' + (res.data.product || 'sản phẩm') + ' vào giỏ hàng!', 'success');
                    speakText('Đã thêm vào giỏ hàng.');
                    $(document.body).trigger('added_to_cart', [res.data]);
                } else {
                    showToast('Không thể thêm: ' + (res.data.message || ''), 'error');
                }
            },
            error: function () {
                showToast('Lỗi kết nối máy chủ', 'error');
            }
        });
    }
    window.vcATC = addToCart;

    function clearCart() {
        setStatus('Đang xóa giỏ hàng...', 'loading');
        $.ajax({
            url: VC.ajaxUrl,
            type: 'POST',
            data: { action: 'vc_clear_cart', nonce: VC.nonce },
            success: function (res) {
                if (res.success) {
                    setStatus('Đã xóa sạch giỏ hàng', 'success');
                    showToast('Giỏ hàng đã được làm trống!', 'success');
                    speakText('Giỏ hàng đã được làm trống.');
                    $(document.body).trigger('wc_fragment_refresh');
                } else {
                    showToast(res.data.message || 'Lỗi khi xóa giỏ hàng', 'error');
                }
            }
        });
    }

    /* ── Dark Mode & Accessibility ───────────────────────── */
    function toggleDarkMode() {
        var isDark = $('html').toggleClass('vc-dark-theme').hasClass('vc-dark-theme');
        localStorage.setItem('vc_dark_mode', isDark ? 'true' : 'false');
        var msg = isDark ? 'Đã bật chế độ tối.' : 'Đã tắt chế độ tối.';
        setStatus(msg, 'success');
        showToast(msg, 'info');
        speakText(msg);
    }

    // Init saved dark mode state
    if (localStorage.getItem('vc_dark_mode') === 'true') {
        $('html').addClass('vc-dark-theme');
    }

    function zoomPage(delta) {
        if (delta === 0) currentZoom = 100;
        else currentZoom = Math.max(80, Math.min(150, currentZoom + delta));
        $('html').css('font-size', currentZoom + '%');
        var msg = (delta === 0) ? 'Đã đặt lại cỡ chữ 100%.' : ('Cỡ chữ hiện tại: ' + currentZoom + '%');
        setStatus(msg, 'success');
        showToast(msg, 'info');
    }

    function readPageContent() {
        var title = $('h1').first().text().trim() || $('title').text().trim();
        var paragraphs = [];
        $('article p, .entry-content p, main p').each(function () {
            var t = $(this).text().trim();
            if (t.length > 20) paragraphs.push(t);
        });

        var textToRead = title + '. ' + paragraphs.slice(0, 3).join('. ');
        if (!textToRead.trim()) {
            textToRead = 'Trang chủ Cửa hàng đặc sản Cần Thơ Nhóm 5. Bạn có thể nói Tìm kiếm bánh tét, Xem giỏ hàng, hoặc Mở cửa hàng.';
        }

        setStatus('Đang đọc nội dung trang...', 'success');
        showToast('Đang đọc bài viết...', 'info');
        speakText(textToRead);
        announceScreenReader(textToRead);
    }

    function stopReadingContent() {
        if (window.speechSynthesis) window.speechSynthesis.cancel();
        $('.vc-reading-highlight').removeClass('vc-reading-highlight');
        setStatus('Đã dừng đọc giọng nói.', '');
        showToast('Đã dừng đọc', 'info');
    }

    /* ── Accessibility & Screen Reader (WCAG 2.1) ────────── */
    function announceScreenReader(msg) {
        if ($srLive.length && msg) {
            $srLive.text(msg);
        }
    }

    function toggleAccessibility(force) {
        if (typeof force === 'boolean') {
            a11yActive = force;
        } else {
            a11yActive = !a11yActive;
        }
        localStorage.setItem('vc_a11y_mode', a11yActive ? 'true' : 'false');
        updateA11yUI();

        var msg = a11yActive
            ? 'Đã bật chế độ hỗ trợ người khiếm thị. Bạn có thể nhấn phím Tab hoặc di chuột để nghe đọc từng mục, hoặc nói vào micro để mua sắm.'
            : 'Đã tắt chế độ hỗ trợ người khiếm thị.';

        setStatus(a11yActive ? 'Chế độ khiếm thị: ĐANG BẬT' : 'Chế độ khiếm thị: ĐÃ TẮT', 'success');
        showToast(a11yActive ? 'Đã bật Chế độ Trợ năng (WCAG)' : 'Đã tắt Chế độ Trợ năng', 'info');
        speakText(msg);
        announceScreenReader(msg);
    }

    function updateA11yUI() {
        if (a11yActive) {
            $('html').addClass('vc-accessibility-mode');
            $a11yBtn.addClass('vc-a11y-active');
            $a11yMasterToggle.addClass('vc-active');
            $a11yStatusTag.removeClass('vc-a11y-tag-off').addClass('vc-a11y-tag-on').text('Đang Bật');
        } else {
            if (!highContrast) $('html').removeClass('vc-accessibility-mode');
            $a11yBtn.removeClass('vc-a11y-active');
            $a11yMasterToggle.removeClass('vc-active');
            $a11yStatusTag.removeClass('vc-a11y-tag-on').addClass('vc-a11y-tag-off').text('Đang Tắt');
        }
    }

    /* ── Hands-Free Wake Word Mode ("Trợ lý ơi") ─────────── */
    function toggleHandsFree(force) {
        if (typeof force === 'boolean') {
            handsFreeActive = force;
        } else {
            handsFreeActive = !handsFreeActive;
        }
        localStorage.setItem('vc_handsfree', handsFreeActive ? 'true' : 'false');
        updateHandsFreeUI();

        if (handsFreeActive) {
            playChime();
            startListening();
            var msg = 'Chế độ rảnh tay đã bật. Bạn có thể nói "Trợ lý ơi" bất cứ lúc nào để ra lệnh.';
            setStatus('Đang lắng nghe: Nói "Trợ lý ơi" để ra lệnh...', 'listening');
            showToast('Rảnh tay: Đang lắng nghe "Trợ lý ơi"', 'success');
            speakText(msg);
            announceScreenReader(msg);
        } else {
            wakeActiveWindow = false;
            clearTimeout(wakeTimer);
            stopListening();
            var msg = 'Đã tắt chế độ rảnh tay.';
            setStatus('Nhấn micro hoặc Alt+M để nói lệnh...', '');
            showToast('Đã tắt chế độ rảnh tay', 'info');
            speakText(msg);
            announceScreenReader(msg);
        }
    }

    function updateHandsFreeUI() {
        if (handsFreeActive) {
            $handsFreeToggle.addClass('vc-hf-active').attr('title', 'Chế độ rảnh tay: ĐANG BẬT (Nói "Trợ lý ơi")');
            $handsFreeBadge.show();
        } else {
            $handsFreeToggle.removeClass('vc-hf-active').attr('title', 'Bật chế độ rảnh tay: Nói "Trợ lý ơi" không cần bấm mic');
            $handsFreeBadge.hide();
        }
    }

    function toggleHighContrast(force) {
        if (typeof force === 'boolean') {
            highContrast = force;
        } else {
            highContrast = !highContrast;
        }
        localStorage.setItem('vc_high_contrast', highContrast ? 'true' : 'false');
        if (highContrast) {
            $('html').addClass('vc-accessibility-mode');
            speakText('Đã bật chế độ tương phản cao vàng đen chuẩn WCAG AAA.');
            showToast('Tương phản cao: Đang bật', 'info');
        } else {
            if (!a11yActive) $('html').removeClass('vc-accessibility-mode');
            speakText('Đã tắt chế độ tương phản cao.');
            showToast('Tương phản cao: Đã tắt', 'info');
        }
    }

    function readCartSummary() {
        setStatus('Đang kiểm tra giỏ hàng...', 'loading');
        $.ajax({
            url: VC.ajaxUrl,
            type: 'POST',
            data: { action: 'vc_cart_summary', nonce: VC.nonce },
            success: function (res) {
                if (res.success && res.data) {
                    var sp = res.data.speech;
                    setStatus('Giỏ hàng: ' + res.data.count + ' món (' + res.data.total + ')', 'success');
                    showToast('Giỏ hàng: ' + res.data.count + ' sản phẩm', 'info');
                    speakText(sp);
                    announceScreenReader(sp);
                }
            },
            error: function () {
                speakText('Không thể kết nối để kiểm tra giỏ hàng.');
            }
        });
    }

    function readProductsOnPage() {
        setStatus('Đang đọc danh sách sản phẩm...', 'loading');
        $.ajax({
            url: VC.ajaxUrl,
            type: 'POST',
            data: { action: 'vc_get_current_products', nonce: VC.nonce },
            success: function (res) {
                if (res.success && res.data) {
                    setStatus('Đang đọc danh sách sản phẩm...', 'success');
                    showToast('Đang đọc đặc sản...', 'info');
                    speakText(res.data.speech);
                    announceScreenReader(res.data.speech);
                }
            },
            error: function () {
                readPageContent();
            }
        });
    }

    // Auto audio guidance on Tab Focus & Hover for Visually Impaired users
    $(document).on('focusin mouseenter', 'a, button, input, select, textarea, .woocommerce-loop-product__title, .price, li.product, .entry-title', function (e) {
        if (!a11yActive) return;
        var $el = $(this);
        clearTimeout(a11yDebounce);
        a11yDebounce = setTimeout(function () {
            var text = getAccessibleText($el);
            if (text) {
                $('.vc-reading-highlight').removeClass('vc-reading-highlight');
                $el.addClass('vc-reading-highlight');
                speakText(text);
                announceScreenReader(text);
            }
        }, 180);
    });

    function getAccessibleText($el) {
        if ($el.closest('#vc-panel').length && !$el.is('#vc-close-btn')) return null;

        // Product item
        if ($el.hasClass('product') || $el.closest('li.product').length) {
            var $p = $el.hasClass('product') ? $el : $el.closest('li.product');
            var pTitle = $p.find('.woocommerce-loop-product__title').text().trim() || $p.find('h2, h3').text().trim();
            var pPrice = $p.find('.price').text().trim();
            if (pTitle) {
                return 'Sản phẩm: ' + pTitle + (pPrice ? (', Giá ' + pPrice) : '') + '. Nhấn Enter để xem hoặc nói Thêm vào giỏ.';
            }
        }
        if ($el.is('button')) {
            var btnText = $el.attr('aria-label') || $el.text().trim() || $el.attr('title') || 'Nút bấm';
            return 'Nút: ' + btnText;
        }
        if ($el.is('a')) {
            var linkText = $el.attr('aria-label') || $el.text().trim() || $el.attr('title');
            if (linkText) return 'Liên kết: ' + linkText + '. Nhấn Enter để mở.';
        }
        if ($el.is('input, textarea')) {
            var ph = $el.attr('placeholder') || $el.attr('aria-label') || 'Ô nhập văn bản';
            return 'Ô nhập liệu: ' + ph;
        }
        return null;
    }

    // Initialize saved accessibility state
    updateA11yUI();
    if (highContrast) {
        $('html').addClass('vc-accessibility-mode');
    }

    /* ── Event Handlers ──────────────────────────────────── */
    $micBtn.on('click', toggleListening);
    $closeBtn.on('click', closePanel);

    // Floating Accessibility Button
    $a11yBtn.on('click', function () {
        openPanel('a11y');
    });

    // Master toggle in a11y panel
    $a11yMasterToggle.on('click', function () {
        toggleAccessibility();
    });

    // Accessibility action buttons in panel
    $(document).on('click', '.vc-a11y-action-btn', function () {
        var act = $(this).data('action');
        if (act === 'read_page') readPageContent();
        else if (act === 'cart_summary') readCartSummary();
        else if (act === 'read_products') readProductsOnPage();
        else if (act === 'high_contrast') toggleHighContrast();
        else if (act === 'zoom_in') zoomPage(10);
        else if (act === 'stop_reading') stopReadingContent();
    });

    // Tab switcher
    $tabs.on('click', function () {
        switchTab($(this).data('tab'));
    });

    // TTS mute toggle
    $ttsToggle.on('click', function () {
        ttsEnabled = !ttsEnabled;
        localStorage.setItem('vc_tts_enabled', ttsEnabled ? 'true' : 'false');
        updateTTSButton();
        if (ttsEnabled) speakText('Đã bật âm thanh trợ lý.');
        else if (window.speechSynthesis) window.speechSynthesis.cancel();
    });
    updateTTSButton();

    // Hands-free Wake Word Toggle
    $handsFreeToggle.on('click', function () {
        toggleHandsFree();
    });
    updateHandsFreeUI();

    // Auto-start hands-free listening if saved as active
    if (handsFreeActive) {
        setTimeout(function () {
            try { startListening(); } catch (e) {}
        }, 800);
    }

    // Direct clicks on Menu items
    $(document).on('click', '.vc-menu-item', function () {
        var action = $(this).data('action');
        if (action === 'search_prompt') {
            switchTab('live');
            setStatus('Hãy nói: "Tìm kiếm [tên đặc sản]"', 'listening');
            startListening();
        } else {
            dispatchAction({ action: action });
        }
    });

    // Direct clicks on Quick chips
    $(document).on('click', '.vc-chip', function () {
        var cmd = $(this).data('cmd');
        $transcript.text(cmd);
        handleTranscript(cmd);
    });

    // Keyboard Shortcuts (WCAG Accessible)
    $(document).on('keydown', function (e) {
        // Alt + M: Voice Command
        if (e.altKey && (e.key === 'm' || e.key === 'M' || e.keyCode === 77)) {
            e.preventDefault();
            toggleListening();
        }
        // Alt + H: Toggle Hands-free Wake Word Mode
        else if (e.altKey && (e.key === 'h' || e.key === 'H' || e.keyCode === 72)) {
            e.preventDefault();
            toggleHandsFree();
        }
        // Alt + A: Toggle Accessibility Mode
        else if (e.altKey && (e.key === 'a' || e.key === 'A' || e.keyCode === 65)) {
            e.preventDefault();
            toggleAccessibility();
        }
        // Alt + R: Read Page Content
        else if (e.altKey && (e.key === 'r' || e.key === 'R' || e.keyCode === 82)) {
            e.preventDefault();
            readPageContent();
        }
        // Alt + C: Check Cart Summary
        else if (e.altKey && (e.key === 'c' || e.key === 'C' || e.keyCode === 67)) {
            e.preventDefault();
            readCartSummary();
        }
        // Escape: Stop reading & close panel
        else if (e.key === 'Escape' || e.keyCode === 27) {
            stopReadingContent();
            if (panelOpen) closePanel();
        }
    });

    // Close panel on outside click
    $(document).on('click', function (e) {
        if (panelOpen && !$(e.target).closest('#vc-wrapper').length) {
            closePanel();
        }
    });

    console.info('[Voice Commerce AI v1.2] Loaded with Wake Word & WCAG 2.1 Accessibility Mode.');

}(jQuery));
