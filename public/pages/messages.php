<?php
// public/pages/messages.php (NİHAİ VE TAM VERSİYON - TASARIM GÜNCELLENMİŞ)
include_once __DIR__.'/../../includes/logic/messages.logic.php';
include_once __DIR__.'/../../includes/header.php';
?>
<style>
    /* Bu sayfaya özel stiller (güncellendi) */
    .messages-main-container { height: calc(100vh - 120px); }
    .chat-list { height: 100%; }
    /* chat-panel sabit yüksekliğe uyumlu olacak şekilde düzenlendi */
    .chat-panel { height: 100%; display: flex; flex-direction: column; }
    /* chat-box artık esnek ve içeriği scroll olan bir alan */
    .chat-box { flex: 1 1 auto; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; }
    /* Mesaj listesini ayırdık */
    #messagesList { display: flex; flex-direction: column; gap: 0.75rem; }

    .message-bubble { max-width: 75%; width: fit-content; word-wrap: break-word; padding: 0.6rem 1.1rem; border-radius: 1.25rem; display: inline-block; }
    .message-bubble.sender { background-color: var(--primary-color); color: white; border-bottom-right-radius: 0.5rem; align-self: flex-end; }
    .message-bubble.receiver { background-color: var(--bg-input); color: var(--text-primary); border-bottom-left-radius: 0.5rem; align-self: flex-start; }
    .message-time { font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8; display: block; }
    .message-bubble.sender .message-time { color: rgba(255, 255, 255, 0.85); }

    .conversation-item.unread h6 { font-weight: bold !important; }

    /* Post embed kart yenilendi: artık tüm kart bir link olarak kullanılacak */
    .post-embed-card {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        max-width: 320px;
        background-color: var(--bg-card);
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .post-embed-img { width: 100%; height: 150px; object-fit: cover; display:block; }
    .post-embed-body { padding: 10px; }
    .post-embed-user { font-weight: bold; color: var(--text-primary); display:block; margin-bottom:6px; }
    .post-embed-caption { font-size: 0.9em; margin: 5px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; color: var(--text-secondary); }
    /* Link stili (gönderen taraf beyaz görünmeli) */
    .post-embed-card a.post-embed-link { display:block; padding:0; color: inherit; text-decoration: none; }
    .message-bubble.sender .post-embed-body a { color: rgba(255,255,255,0.95); }
    /* Mobilde fazla genişleme olmasın */
    @media (max-width: 576px) {
        .post-embed-card { max-width: 100%; }
        .message-bubble { max-width: 90%; }
    }
</style>

<div class="container-fluid my-4 flex-grow-1">
    <div class="row h-100 messages-main-container">
        <div class="col-md-4 h-100">
            <div class="card shadow-sm h-100 d-flex flex-column rounded-3 chat-list">
                <div class="card-header p-3"><h4 class="mb-0">Mesajlar</h4></div>
                <div class="card-body p-0 flex-grow-1 overflow-auto">
                    <div class="list-group list-group-flush" id="conversationList">
                        <?php if (empty($conversations)) { ?>
                            <div class="p-4 text-center text-muted">Henüz bir konuşmanız yok.</div>
                        <?php } else { ?>
                            <?php foreach ($conversations as $conv) { ?>
                                <a href="?conversation_id=<?php echo $conv['id']; ?>" class="list-group-item list-group-item-action conversation-item <?php echo (isset($_GET['conversation_id']) && $_GET['conversation_id'] == $conv['id']) ? 'active' : ''; ?> <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>" data-conversation-id="<?php echo $conv['id']; ?>">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo BASE_URL.'uploads/profile_pictures/'.($conv['profile_picture_url'] ?? 'default_profile.png'); ?>" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                                        <div class="flex-grow-1" style="min-width: 0;">
                                            <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($conv['username']); ?></h6>
                                            <small class="text-muted text-truncate d-block"><?php echo htmlspecialchars($conv['last_message_text'] ?? '...'); ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 h-100">
            <div class="card shadow-sm h-100 d-flex flex-column rounded-3 chat-panel">
                <div class="card-header d-flex align-items-center p-3">
                    <h4 id="chatPartnerName" class="mb-0 text-muted">Konuşma Seç</h4>
                </div>

                <!-- mesaj alanı (scroll burada) -->
                <div id="messageContainer" class="card-body p-0 chat-box">
                    <!-- messagesList mesajların yönlendirileceği sabit scroll alanı -->
                    <div id="messagesList" class="w-100 d-flex flex-column align-items-stretch px-3 py-3">
                        <p class="text-center text-muted m-auto" id="noConversationMessage">Bir konuşma başlatmak için soldan bir kullanıcı seçin.</p>
                    </div>
                </div>

                <div class="card-footer p-2 border-top d-none" id="messageInputArea">
                    <form id="messageForm" class="d-flex gap-2">
                        <input type="hidden" name="conversation_id" id="formConversationId">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <textarea id="messageInput" name="message_text" class="form-control border-0 bg-light" rows="1" placeholder="Mesaj yaz..." required style="resize:none;"></textarea>
                        <button class="btn btn-primary rounded-circle flex-shrink-0" type="submit" style="width: 40px; height: 40px;"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatPartnerName = document.getElementById('chatPartnerName');
    const messageContainer = document.getElementById('messageContainer');
    const messagesList = document.getElementById('messagesList');
    const messageInputArea = document.getElementById('messageInputArea');
    const noConversationMessage = document.getElementById('noConversationMessage');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const formConversationId = document.getElementById('formConversationId');

    let currentConversationId = null;
    let messagePollingInterval = null;

    function renderMessages(data) {
        if (!data.partner_info) {
            console.error("Partner bilgisi gelmedi!");
            return;
        }

        chatPartnerName.textContent = data.partner_info.username;
        messageInputArea.classList.remove('d-none');
        noConversationMessage.style.display = 'none';
        formConversationId.value = currentConversationId;

        // temizle
        messagesList.innerHTML = '';

        if (!data.messages || data.messages.length === 0) {
            const p = document.createElement('p');
            p.className = 'text-center text-muted m-auto';
            p.textContent = 'İlk mesajı siz gönderin!';
            messagesList.appendChild(p);
            // scroll yok çünkü tek mesaj
            return;
        }

        data.messages.forEach(msg => {
            const isSender = String(msg.sender_id) === String(data.current_user_id);
            let wrapper = document.createElement('div');

            // post preview varsa kartı <a> ile sarıp veriyoruz (buton kaldırıldı)
            if (msg.post_preview) {
                const preview = msg.post_preview;
                const link = document.createElement('a');
                link.href = `${BASE_URL}public/pages/post.php?id=${preview.id}`;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.className = 'post-embed-card post-embed-link';
                link.innerHTML = `
                    <img src="${BASE_URL}uploads/posts/${preview.image_url}" class="post-embed-img" alt="post image">
                    <div class="post-embed-body">
                        <span class="post-embed-user">${escapeHtml(preview.username)}</span>
                        <p class="post-embed-caption">${escapeHtml(preview.caption)}</p>
                    </div>
                `;
                wrapper.appendChild(link);
            } else {
                // normal metin mesaj
                const span = document.createElement('span');
                span.innerHTML = escapeHtml(msg.message_text).replace(/\n/g, '<br>');
                wrapper.appendChild(span);

                const time = document.createElement('div');
                time.className = 'message-time text-end mt-1';
                time.textContent = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                wrapper.appendChild(time);
            }

            const bubble = document.createElement('div');
            bubble.className = 'message-bubble ' + (isSender ? 'sender' : 'receiver');
            bubble.appendChild(wrapper);

            messagesList.appendChild(bubble);
        });

        // en alta scroll
        messagesList.scrollTop = messagesList.scrollHeight;
    }

    function fetchMessages(conversationId) {
        if (!conversationId) return;
        fetch(`${BASE_URL}public/ajax/get_messages.php?conversation_id=${conversationId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data);
                }
            })
            .catch(error => console.error("Mesajlar çekilirken hata:", error));
    }

    // URL'den gelen conversation_id'yi alıp ilgili konuşmayı aç
    const urlParams = new URLSearchParams(window.location.search);
    currentConversationId = urlParams.get('conversation_id');
    if (currentConversationId) {
        fetchMessages(currentConversationId);
        messagePollingInterval = setInterval(() => fetchMessages(currentConversationId), 5000);
    }

    // Mesaj gönderme formu
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageText = messageInput.value.trim();
        if (messageText === '' || !currentConversationId) return;

        const formData = new FormData(messageForm);
        messageInput.value = '';

        fetch(`${BASE_URL}public/ajax/send_message.php`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fetchMessages(currentConversationId); // Yeni mesajları hemen çek
                } else {
                    alert(data.message);
                    messageInput.value = messageText; // Hata durumunda yazıyı geri koy
                }
            })
            .catch(err => {
                console.error('Gönderme hatası:', err);
                messageInput.value = messageText;
            });
    });

    // küçük yardımcı: XSS önlemi için escape
    function escapeHtml(unsafe) {
        if (!unsafe && unsafe !== 0) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>

<?php
include_once __DIR__.'/../../includes/footer.php';
?>
