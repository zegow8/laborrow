/**
 * Laborrow Live Chat Real-time Engine
 * Vanilla JavaScript Fetch API
 */

document.addEventListener('DOMContentLoaded', function () {
    const chatBox = document.getElementById('chatMessagesBox');
    const formChat = document.getElementById('formChat');
    const inputPesan = document.getElementById('inputPesan');
    const btnRefresh = document.getElementById('btnManualRefresh');

    if (!chatBox || typeof chatPartnerId === 'undefined') return;

    let isFirstLoad = true;
    let lastMessageCount = 0;

    // Fungsi Render Obrolan
    function loadChatMessages() {
        const url = `../ajax/get_chat.php?partner_id=${chatPartnerId}&_t=${new Date().getTime()}`;

        fetch(url)
            .then(response => response.json())
            .then(res => {
                if (res.status === 'success') {
                    renderMessages(res.data);
                } else {
                    console.error('Chat error:', res.message);
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
            });
    }

    function renderMessages(messages) {
        if (!messages || messages.length === 0) {
            chatBox.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-comments fa-3x mb-3 text-muted opacity-25"></i>
                    <p class="mb-0 fw-semibold">Belum ada riwayat pesan</p>
                    <small class="text-muted">Ketik pesan di bawah untuk memulai percakapan.</small>
                </div>
            `;
            lastMessageCount = 0;
            return;
        }

        // Jika jumlah pesan sama dan bukan first load, jangan render ulang agar tidak flicker
        if (!isFirstLoad && messages.length === lastMessageCount) {
            return;
        }

        let html = '';
        messages.forEach(msg => {
            const bubbleClass = msg.is_me ? 'message-outgoing' : 'message-incoming';
            html += `
                <div class="message-bubble ${bubbleClass}">
                    <div>${msg.isi_pesan}</div>
                    <span class="message-time">
                        ${msg.time_formatted}
                    </span>
                </div>
            `;
        });

        chatBox.innerHTML = html;
        lastMessageCount = messages.length;

        // Auto-scroll ke pesan terbawah
        chatBox.scrollTop = chatBox.scrollHeight;
        isFirstLoad = false;
    }

    // Load pertama kali
    loadChatMessages();

    // Auto-refresh setiap 3 detik (3000ms)
    const refreshInterval = setInterval(loadChatMessages, 3000);

    // Refresh manual lewat tombol
    if (btnRefresh) {
        btnRefresh.addEventListener('click', function () {
            btnRefresh.classList.add('fa-spin');
            loadChatMessages();
            setTimeout(() => btnRefresh.classList.remove('fa-spin'), 600);
        });
    }

    // Submit form kirim pesan
    if (formChat) {
        formChat.addEventListener('submit', function (e) {
            e.preventDefault();
            const text = inputPesan.value.trim();
            if (!text) return;

            const formData = new FormData();
            formData.append('receiver_id', chatPartnerId);
            formData.append('isi_pesan', text);

            // Bersihkan input langsung untuk UX responsif
            inputPesan.value = '';

            fetch('../ajax/send_chat.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    loadChatMessages();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengirim',
                        text: data.message || 'Pesan tidak dapat terkirim.',
                        confirmButtonColor: '#800020'
                    });
                }
            })
            .catch(err => {
                console.error('Error send chat:', err);
            });
        });
    }
});
