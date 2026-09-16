/**
 * ============================================================
 * JAVASCRIPT CHO PLUGIN ĐỌC BÀI VIẾT (v2.1)
 * ============================================================
 *
 * Plugin hỗ trợ 2 chế độ đọc:
 *
 * CHẾ ĐỘ 1: Web Speech API (SpeechSynthesis)
 *   - Sử dụng giọng đọc có sẵn trên trình duyệt.
 *   - Ưu tiên nếu trình duyệt có voice tiếng Việt.
 *
 * CHẾ ĐỘ 2: Google Translate TTS (MIỄN PHÍ)
 *   - Sử dụng khi trình duyệt KHÔNG có voice tiếng Việt.
 *   - Không cần API Key.
 *   - JS gửi AJAX request tới WordPress → PHP gọi Google Translate TTS
 *     → trả về audio MP3 → JS phát bằng HTML5 Audio.
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
    var noiDung = dbvData.noiDung;
    var ajaxUrl = dbvData.ajaxUrl;
    var nonce   = dbvData.nonce;

    // Trạng thái.
    var cheDoDoc     = '';        // 'speech' hoặc 'gtts'.
    var cacDoan      = [];       // Mảng các đoạn văn bản.
    var doanHienTai  = 0;        // Chỉ số đoạn đang đọc.
    var dangDoc      = false;
    var dangTamDung  = false;
    var tocDoDoc     = 1.0;
    var voiceViet    = null;     // Voice tiếng Việt (Web Speech API).
    var audioHienTai = null;     // Audio element (Google Translate TTS).
    var xhrHienTai   = null;     // XMLHttpRequest hiện tại.

    // Lấy các phần tử HTML.
    var btnDoc      = document.getElementById('dbv-btn-doc');
    var btnTamDung  = document.getElementById('dbv-btn-tam-dung');
    var btnTiepTuc  = document.getElementById('dbv-btn-tiep-tuc');
    var btnDung     = document.getElementById('dbv-btn-dung');
    var speedRange  = document.getElementById('dbv-speed-range');
    var speedValue  = document.getElementById('dbv-speed-value');
    var statusText  = document.getElementById('dbv-status-text');
    var thongBaoDiv = document.getElementById('dbv-thong-bao');

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
    // PHẦN 4: TÌM VOICE TIẾNG VIỆT (Web Speech API)
    // ============================================================

    function timVoiceViet() {
        if (!synth) {
            return false;
        }

        var voices = synth.getVoices();
        if (voices.length === 0) {
            return false;
        }

        for (var i = 0; i < voices.length; i++) {
            if (voices[i].lang && voices[i].lang.toLowerCase().indexOf('vi') === 0) {
                voiceViet = voices[i];
                return true;
            }
        }

        return false;
    }

    // Thử tìm voice ngay lập tức.
    var daTim = timVoiceViet();

    // Chrome tải voices bất đồng bộ.
    if (!daTim && synth && synth.onvoiceschanged !== undefined) {
        synth.onvoiceschanged = function () {
            timVoiceViet();
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

        if (voiceViet) {
            utterance.voice = voiceViet;
        }
        utterance.lang = 'vi-VN';
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
     * 2. PHP gọi Google Translate TTS endpoint, nhận audio MP3.
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

        var params = 'action=dbv_text_to_speech'
            + '&nonce=' + encodeURIComponent(nonce)
            + '&text=' + encodeURIComponent(cacDoan[doanHienTai]);
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
     * 1. Web Speech API (nếu có voice tiếng Việt trên trình duyệt).
     * 2. Google Translate TTS (miễn phí, luôn khả dụng khi có mạng).
     */
    function batDauDoc() {
        // Thử tìm voice tiếng Việt lần nữa.
        if (!voiceViet) {
            timVoiceViet();
        }

        // Dừng mọi thứ đang chạy.
        dungDocNgay();
        anThongBao();

        // Xác định chế độ đọc.
        if (voiceViet) {
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

        capNhatNut('dang-doc');
        capNhatTrangThai('Đang đọc...');

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

    // ============================================================
    // PHẦN 10: DỌN DẸP KHI RỜI TRANG
    // ============================================================

    window.addEventListener('beforeunload', function () {
        dungDocNgay();
    });

})();
