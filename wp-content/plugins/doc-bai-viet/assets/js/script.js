/**
 * ============================================================
 * JAVASCRIPT CHO PLUGIN ĐỌC BÀI VIẾT (v3.0)
 * ============================================================
 *
 * Plugin hỗ trợ 2 chế độ đọc và 12 ngôn ngữ:
 *
 * CHẾ ĐỘ 1: Web Speech API (SpeechSynthesis)
 *   - Sử dụng giọng đọc có sẵn trên trình duyệt.
 *   - Ưu tiên nếu trình duyệt có voice của ngôn ngữ đã chọn.
 *
 * CHẾ ĐỘ 2: Google Translate TTS (MIỄN PHÍ)
 *   - Sử dụng khi trình duyệt KHÔNG có voice của ngôn ngữ đã chọn.
 *   - Không cần API Key.
 *   - JS gửi AJAX request tới WordPress → PHP gọi Google Translate TTS
 *     → trả về audio MP3 → JS phát bằng HTML5 Audio.
 *
 * NGÔN NGỮ HỖ TRỢ:
 *   vi (Tiếng Việt), en (English), fr (Français), de (Deutsch),
 *   ja (日本語), ko (한국어), zh (中文), es (Español),
 *   pt (Português), it (Italiano), ru (Русский), th (ไทย)
 */

(function () {
    'use strict';

    // ============================================================
    // PHẦN 1: KIỂM TRA ĐIỀU KIỆN
    // ============================================================

    if (typeof dbvData === 'undefined' || !dbvData.noiDung) {
        hienThongBao('Bài viết không có nội dung để đọc.');
        return;
    }

    // ============================================================
    // PHẦN 2: KHAI BÁO BIẾN
    // ============================================================

    var synth = window.speechSynthesis || null;

    // Dữ liệu từ PHP (wp_localize_script).
    var noiDung     = dbvData.noiDung;
    var ajaxUrl     = dbvData.ajaxUrl;
    var nonce       = dbvData.nonce;
    var defaultLang = dbvData.defaultLang || 'vi';

    // Trạng thái.
    var cheDoDoc     = '';        // 'speech' hoặc 'gtts'.
    var cacDoan      = [];       // Mảng các đoạn văn bản.
    var doanHienTai  = 0;        // Chỉ số đoạn đang đọc.
    var dangDoc      = false;
    var dangTamDung  = false;
    var tocDoDoc     = 1.0;
    var ngonNguChon  = defaultLang; // Ngôn ngữ hiện tại được chọn.
    var voiceChon    = null;     // Voice của ngôn ngữ đã chọn (Web Speech API).
    var audioHienTai = null;     // Audio element (Google Translate TTS).
    var xhrHienTai   = null;     // XMLHttpRequest hiện tại.

    /**
     * Bảng ánh xạ mã ngôn ngữ → mã BCP-47 cho Web Speech API.
     * Web Speech API sử dụng mã BCP-47 (ví dụ: vi-VN, en-US).
     * Bảng này giúp tìm voice phù hợp khi người dùng chọn ngôn ngữ.
     */
    var langMap = {
        'vi': ['vi-VN', 'vi'],
        'en': ['en-US', 'en-GB', 'en-AU', 'en-IN', 'en'],
        'fr': ['fr-FR', 'fr-CA', 'fr'],
        'de': ['de-DE', 'de-AT', 'de'],
        'ja': ['ja-JP', 'ja'],
        'ko': ['ko-KR', 'ko'],
        'zh': ['zh-CN', 'zh-TW', 'zh-HK', 'zh'],
        'es': ['es-ES', 'es-MX', 'es-US', 'es'],
        'pt': ['pt-BR', 'pt-PT', 'pt'],
        'it': ['it-IT', 'it'],
        'ru': ['ru-RU', 'ru'],
        'th': ['th-TH', 'th']
    };

    // Lấy các phần tử HTML.
    var btnDoc      = document.getElementById('dbv-btn-doc');
    var btnTamDung  = document.getElementById('dbv-btn-tam-dung');
    var btnTiepTuc  = document.getElementById('dbv-btn-tiep-tuc');
    var btnDung     = document.getElementById('dbv-btn-dung');
    var speedRange  = document.getElementById('dbv-speed-range');
    var speedValue  = document.getElementById('dbv-speed-value');
    var statusText  = document.getElementById('dbv-status-text');
    var thongBaoDiv = document.getElementById('dbv-thong-bao');
    var langSelect  = document.getElementById('dbv-lang');

    if (!btnDoc) {
        return;
    }

    // ============================================================
    // PHẦN 3: CHIA NỘI DUNG THÀNH CÁC ĐOẠN
    // ============================================================

    /**
     * chiaDoan():
     * Chia nội dung dài thành các đoạn nhỏ.
     *
     * - Web Speech API: gioiHan = 200 ký tự (giới hạn trình duyệt).
     * - Google Translate TTS: gioiHan = 180 ký tự (giới hạn endpoint ~200).
     */
    function chiaDoan(vanBan, gioiHan) {
        var GH = gioiHan || 180;
        var ketQua = [];

        // Tách theo dấu câu và xuống dòng.
        var cacCau = vanBan.split(/(?<=[.?!;])\s+|\n+/);

        for (var i = 0; i < cacCau.length; i++) {
            var cau = cacCau[i].trim();
            if (cau.length === 0) {
                continue;
            }

            if (cau.length <= GH) {
                ketQua.push(cau);
            } else {
                var cacPhan = cau.split(/,\s*/);
                var doanTam = '';

                for (var j = 0; j < cacPhan.length; j++) {
                    var phan = cacPhan[j].trim();

                    if ((doanTam + ', ' + phan).length <= GH) {
                        doanTam = doanTam ? doanTam + ', ' + phan : phan;
                    } else {
                        if (doanTam) {
                            ketQua.push(doanTam);
                        }
                        while (phan.length > GH) {
                            ketQua.push(phan.substring(0, GH));
                            phan = phan.substring(GH);
                        }
                        doanTam = phan;
                    }
                }

                if (doanTam) {
                    ketQua.push(doanTam);
                }
            }
        }

        return ketQua;
    }

    // ============================================================
    // PHẦN 4: TÌM VOICE THEO NGÔN NGỮ (Web Speech API)
    // ============================================================

    /**
     * timVoiceTheoNgonNgu():
     * Tìm voice phù hợp với mã ngôn ngữ đã chọn.
     *
     * @param {string} langCode - Mã ngôn ngữ (ví dụ: 'vi', 'en', 'fr').
     * @return {boolean} - true nếu tìm thấy voice.
     */
    function timVoiceTheoNgonNgu(langCode) {
        if (!synth) {
            return false;
        }

        var voices = synth.getVoices();
        if (voices.length === 0) {
            return false;
        }

        // Lấy danh sách mã BCP-47 cho ngôn ngữ này.
        var maBCP47 = langMap[langCode] || [langCode];

        // Duyệt qua danh sách mã BCP-47, ưu tiên theo thứ tự.
        for (var m = 0; m < maBCP47.length; m++) {
            var maTimKiem = maBCP47[m].toLowerCase();

            for (var i = 0; i < voices.length; i++) {
                if (voices[i].lang) {
                    var voiceLang = voices[i].lang.toLowerCase();
                    // So khớp chính xác hoặc bắt đầu bằng mã ngôn ngữ.
                    if (voiceLang === maTimKiem || voiceLang.indexOf(maTimKiem + '-') === 0) {
                        voiceChon = voices[i];
                        return true;
                    }
                }
            }
        }

        // Fallback: tìm voice có lang bắt đầu bằng mã ngôn ngữ chính.
        var maChinhLower = langCode.toLowerCase();
        for (var k = 0; k < voices.length; k++) {
            if (voices[k].lang && voices[k].lang.toLowerCase().indexOf(maChinhLower) === 0) {
                voiceChon = voices[k];
                return true;
            }
        }

        voiceChon = null;
        return false;
    }

    // Chrome tải voices bất đồng bộ, đăng ký sự kiện để cập nhật.
    if (synth && synth.onvoiceschanged !== undefined) {
        synth.onvoiceschanged = function () {
            // Voices đã sẵn sàng, không cần làm gì ở đây.
            // Sẽ tìm voice khi nhấn nút Đọc.
        };
    }

    // ============================================================
    // PHẦN 5: ĐỌC BẰNG WEB SPEECH API
    // ============================================================

    function docDoanSpeech() {
        if (doanHienTai >= cacDoan.length) {
            dungDoc();
            capNhatTrangThai('Đã đọc xong');
            return;
        }

        var utterance = new SpeechSynthesisUtterance(cacDoan[doanHienTai]);

        // Gán voice và ngôn ngữ đã chọn.
        if (voiceChon) {
            utterance.voice = voiceChon;
        }
        // Sử dụng mã BCP-47 đầu tiên của ngôn ngữ đã chọn.
        var maBCP47 = langMap[ngonNguChon] || [ngonNguChon];
        utterance.lang = maBCP47[0];
        utterance.rate = tocDoDoc;

        utterance.onend = function () {
            doanHienTai++;
            if (dangDoc && !dangTamDung) {
                docDoanSpeech();
            }
        };

        utterance.onerror = function (event) {
            if (event.error === 'interrupted' || event.error === 'canceled') {
                return;
            }
            capNhatTrangThai('Lỗi: ' + event.error);
        };

        synth.speak(utterance);
    }

    // ============================================================
    // PHẦN 6: ĐỌC BẰNG GOOGLE TRANSLATE TTS (MIỄN PHÍ)
    // ============================================================

    /**
     * docDoanGtts():
     * Đọc đoạn văn bản bằng Google Translate TTS.
     *
     * Luồng:
     * 1. Gửi AJAX POST tới admin-ajax.php (action = dbv_text_to_speech).
     * 2. PHP gọi Google Translate TTS endpoint với mã ngôn ngữ, nhận audio MP3.
     * 3. PHP encode audio thành base64, trả về cho JS.
     * 4. JS tạo Audio element từ base64 data URI và phát.
     * 5. Khi audio kết thúc (onended), đọc đoạn tiếp theo.
     */
    function docDoanGtts() {
        if (doanHienTai >= cacDoan.length) {
            dungDoc();
            capNhatTrangThai('Đã đọc xong');
            return;
        }

        capNhatTrangThai('Đang tải âm thanh (' + (doanHienTai + 1) + '/' + cacDoan.length + ')...');

        var xhr = new XMLHttpRequest();
        xhrHienTai = xhr;
        xhr.open('POST', ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
            xhrHienTai = null;

            if (!dangDoc) {
                return; // Đã bị dừng trong khi đang tải.
            }

            if (xhr.status !== 200) {
                hienThongBao('Lỗi kết nối server (HTTP ' + xhr.status + ').');
                dungDoc();
                return;
            }

            try {
                var response = JSON.parse(xhr.responseText);
            } catch (e) {
                hienThongBao('Lỗi xử lý phản hồi từ server.');
                dungDoc();
                return;
            }

            if (response.success && response.data && response.data.audio) {
                phatAudio(response.data.audio);
            } else {
                var msg = (response.data && response.data.message)
                    ? response.data.message
                    : 'Lỗi không xác định.';
                hienThongBao(msg);
                dungDoc();
            }
        };

        xhr.onerror = function () {
            xhrHienTai = null;
            hienThongBao('Lỗi kết nối mạng. Vui lòng kiểm tra kết nối internet.');
            dungDoc();
        };

        // Gửi kèm mã ngôn ngữ (lang) cho PHP proxy.
        var params = 'action=dbv_text_to_speech'
            + '&nonce=' + encodeURIComponent(nonce)
            + '&text=' + encodeURIComponent(cacDoan[doanHienTai])
            + '&lang=' + encodeURIComponent(ngonNguChon);
        xhr.send(params);
    }

    /**
     * phatAudio():
     * Tạo và phát audio từ dữ liệu base64.
     *
     * @param {string} base64Audio - Audio MP3 dạng base64.
     */
    function phatAudio(base64Audio) {
        audioHienTai = new Audio('data:audio/mp3;base64,' + base64Audio);
        audioHienTai.playbackRate = tocDoDoc;

        audioHienTai.onended = function () {
            doanHienTai++;
            if (dangDoc && !dangTamDung) {
                docDoanGtts(); // Đọc đoạn tiếp.
            }
        };

        audioHienTai.onerror = function () {
            hienThongBao('Lỗi phát audio.');
            dungDoc();
        };

        audioHienTai.play().then(function () {
            capNhatTrangThai('Đang đọc (' + (doanHienTai + 1) + '/' + cacDoan.length + ')...');
        }).catch(function (err) {
            hienThongBao('Không thể phát audio: ' + err.message);
            dungDoc();
        });
    }

    // ============================================================
    // PHẦN 7: CÁC HÀM ĐIỀU KHIỂN CHÍNH
    // ============================================================

    /**
     * batDauDoc():
     * Xác định chế độ đọc và bắt đầu từ đầu.
     *
     * Ưu tiên:
     * 1. Web Speech API (nếu có voice của ngôn ngữ đã chọn trên trình duyệt).
     * 2. Google Translate TTS (miễn phí, luôn khả dụng khi có mạng).
     */
    function batDauDoc() {
        // Lấy ngôn ngữ đã chọn từ dropdown.
        if (langSelect) {
            ngonNguChon = langSelect.value;
        }

        // Tìm voice cho ngôn ngữ đã chọn.
        var coVoice = timVoiceTheoNgonNgu(ngonNguChon);

        // Dừng mọi thứ đang chạy.
        dungDocNgay();
        anThongBao();

        // Xác định chế độ đọc.
        if (coVoice) {
            // Chế độ 1: Web Speech API.
            cheDoDoc = 'speech';
            cacDoan = chiaDoan(noiDung, 200);
        } else {
            // Chế độ 2: Google Translate TTS (luôn khả dụng).
            cheDoDoc = 'gtts';
            cacDoan = chiaDoan(noiDung, 180);
        }

        if (cacDoan.length === 0) {
            hienThongBao('Bài viết không có nội dung để đọc.');
            return;
        }

        // Bắt đầu đọc.
        doanHienTai = 0;
        dangDoc = true;
        dangTamDung = false;

        // Lấy tên ngôn ngữ để hiển thị trạng thái.
        var tenNgonNgu = langSelect ? langSelect.options[langSelect.selectedIndex].text : ngonNguChon;
        var cheDoHienThi = (cheDoDoc === 'speech') ? 'Web Speech API' : 'Google Translate TTS';

        capNhatNut('dang-doc');
        capNhatTrangThai('Đang đọc [' + tenNgonNgu + ' — ' + cheDoHienThi + ']...');

        if (cheDoDoc === 'speech') {
            docDoanSpeech();
        } else {
            docDoanGtts();
        }
    }

    /**
     * tamDung():
     * Tạm dừng đọc.
     */
    function tamDung() {
        if (cheDoDoc === 'speech') {
            if (synth && synth.speaking && !synth.paused) {
                synth.pause();
            }
        } else if (cheDoDoc === 'gtts') {
            if (audioHienTai && !audioHienTai.paused) {
                audioHienTai.pause();
            }
        }

        dangTamDung = true;
        capNhatNut('tam-dung');
        capNhatTrangThai('Đã tạm dừng');
    }

    /**
     * tiepTuc():
     * Tiếp tục đọc sau khi tạm dừng.
     */
    function tiepTuc() {
        if (cheDoDoc === 'speech') {
            if (synth && synth.paused) {
                synth.resume();
            }
        } else if (cheDoDoc === 'gtts') {
            if (audioHienTai && audioHienTai.paused) {
                audioHienTai.play();
            }
        }

        dangTamDung = false;
        capNhatNut('dang-doc');
        capNhatTrangThai('Đang đọc...');
    }

    /**
     * dungDoc():
     * Dừng hoàn toàn, reset về trạng thái ban đầu.
     */
    function dungDoc() {
        dungDocNgay();
        capNhatNut('cho');
        capNhatTrangThai('Đã dừng');
    }

    /**
     * dungDocNgay():
     * Dừng tất cả playback mà không cập nhật UI.
     */
    function dungDocNgay() {
        // Dừng Web Speech API.
        if (synth) {
            synth.cancel();
        }

        // Dừng Audio element.
        if (audioHienTai) {
            audioHienTai.pause();
            audioHienTai.onended = null;
            audioHienTai.onerror = null;
            audioHienTai = null;
        }

        // Hủy AJAX request.
        if (xhrHienTai) {
            xhrHienTai.abort();
            xhrHienTai = null;
        }

        dangDoc = false;
        dangTamDung = false;
        doanHienTai = 0;
    }

    // ============================================================
    // PHẦN 8: CẬP NHẬT GIAO DIỆN
    // ============================================================

    function capNhatNut(trangThai) {
        switch (trangThai) {
            case 'cho':
                btnDoc.disabled = false;
                btnTamDung.disabled = true;
                btnTiepTuc.disabled = true;
                btnDung.disabled = true;
                break;

            case 'dang-doc':
                btnDoc.disabled = false;
                btnTamDung.disabled = false;
                btnTiepTuc.disabled = true;
                btnDung.disabled = false;
                break;

            case 'tam-dung':
                btnDoc.disabled = false;
                btnTamDung.disabled = true;
                btnTiepTuc.disabled = false;
                btnDung.disabled = false;
                break;
        }
    }

    function capNhatTrangThai(text) {
        if (statusText) {
            statusText.textContent = text;
        }
    }

    function hienThongBao(noiDungTB) {
        if (thongBaoDiv) {
            thongBaoDiv.textContent = noiDungTB;
            thongBaoDiv.style.display = 'block';
        }
    }

    function anThongBao() {
        if (thongBaoDiv) {
            thongBaoDiv.style.display = 'none';
        }
    }

    // ============================================================
    // PHẦN 9: GẮN SỰ KIỆN
    // ============================================================

    btnDoc.addEventListener('click', function () {
        batDauDoc();
    });

    btnTamDung.addEventListener('click', function () {
        tamDung();
    });

    btnTiepTuc.addEventListener('click', function () {
        tiepTuc();
    });

    btnDung.addEventListener('click', function () {
        dungDoc();
    });

    if (speedRange) {
        speedRange.addEventListener('input', function () {
            tocDoDoc = parseFloat(this.value);
            if (speedValue) {
                speedValue.textContent = tocDoDoc.toFixed(1) + 'x';
            }
            // Cập nhật tốc độ cho audio đang phát (Google Translate TTS mode).
            if (cheDoDoc === 'gtts' && audioHienTai) {
                audioHienTai.playbackRate = tocDoDoc;
            }
        });
    }

    /**
     * Khi người dùng đổi ngôn ngữ trong lúc đang đọc:
     * Dừng đọc hiện tại để tránh nhầm lẫn ngôn ngữ.
     */
    if (langSelect) {
        langSelect.addEventListener('change', function () {
            if (dangDoc) {
                dungDoc();
                capNhatTrangThai('Đã đổi ngôn ngữ. Nhấn "Đọc" để bắt đầu lại.');
            }
        });
    }

    // ============================================================
    // PHẦN 10: DỌN DẸP KHI RỜI TRANG
    // ============================================================

    window.addEventListener('beforeunload', function () {
        dungDocNgay();
    });

})();
