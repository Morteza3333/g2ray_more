/**
 * Admin Messenger Pro Core Client Application (Vanilla JavaScript)
 */
document.addEventListener('DOMContentLoaded', function () {
  if (typeof ampVars === 'undefined') return;

  // Global App State
  const state = {
    activeConversationId: null,
    activeTab: 'all',
    searchQuery: '',
    conversations: [],
    messages: [],
    typingTimeout: null,
    pollingTimer: null,
    isTabFocused: true,
    audioCtx: null,
    replyParentId: null,
  };

  // DOM Cache
  const dom = {
    appContainer: document.querySelector('.amp-app-container'),
    mobileBackBtn: document.getElementById('amp-mobile-back-btn'),
    chatsList: document.getElementById('amp-chats-list-container'),
    chatWindow: document.getElementById('amp-chat-window-main'),
    messagesArea: document.getElementById('amp-chat-messages'),
    messageInput: document.getElementById('amp-message-textarea'),
    sendBtn: document.getElementById('amp-send-message-btn'),
    emojiPickerBtn: document.getElementById('amp-emoji-picker-btn'),
    voiceRecordBtn: document.getElementById('amp-voice-record-btn'),
    attachFileBtn: document.getElementById('amp-attach-file-btn'),
    globalSearch: document.getElementById('amp-global-search'),
    myPresence: document.getElementById('amp-my-presence'),
    tabs: document.querySelectorAll('.amp-tab-btn'),
    newPrivateBtn: document.getElementById('amp-new-private-btn'),
    newGroupBtn: document.getElementById('amp-new-group-btn'),

    // Dialogs
    dialogPrivate: document.getElementById('amp-dialog-private'),
    dialogPrivateCancel: document.getElementById('amp-dialog-private-cancel'),
    userSearchInput: document.getElementById('amp-user-search-input'),
    userSearchResults: document.getElementById('amp-user-search-results'),

    dialogGroup: document.getElementById('amp-dialog-group'),
    dialogGroupCancel: document.getElementById('amp-dialog-group-cancel'),
    dialogGroupSubmit: document.getElementById('amp-dialog-group-submit'),
    groupTitleInput: document.getElementById('amp-group-title'),
    groupUserSearch: document.getElementById('amp-group-user-search'),
    groupUserResults: document.getElementById('amp-group-user-results'),
    groupSelectedMembers: document.getElementById('amp-group-selected-members'),

    // Right Details Panel
    rightPanel: document.getElementById('amp-right-panel-main'),
    panelClose: document.getElementById('amp-panel-close'),
    infoToggle: document.getElementById('amp-info-toggle'),
    pinnedMessagesList: document.getElementById('amp-pinned-messages-list'),
    sharedFilesList: document.getElementById('amp-shared-files-list'),
    sharedMediaGrid: document.getElementById('amp-shared-media-grid'),

    // Lightbox
    lightbox: document.getElementById('amp-lightbox'),
    lightboxImg: document.getElementById('amp-lightbox-img'),
    lightboxVideo: document.getElementById('amp-lightbox-video'),

    // Header Actions
    activeAvatar: document.getElementById('amp-active-avatar'),
    activeTitle: document.getElementById('amp-active-title'),
    activePresence: document.getElementById('amp-active-presence'),
    chatHeader: document.querySelector('.amp-chat-header'),
    chatFooter: document.querySelector('.amp-chat-footer'),
    activePin: document.getElementById('amp-active-pin'),
    activeArchive: document.getElementById('amp-active-archive'),
    activeMute: document.getElementById('amp-active-mute'),

    // Reply Banner
    replyBanner: document.getElementById('amp-reply-banner'),
    replyBannerSender: document.getElementById('amp-reply-banner-sender'),
    replyBannerText: document.getElementById('amp-reply-banner-text'),
    replyBannerClose: document.getElementById('amp-reply-banner-close'),
  };

  // Apply Accent & Mode Styling
  document.documentElement.style.setProperty('--amp-primary', ampVars.accentColor);
  // Simple hex to rgb converter for CSS rgb var
  const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '99, 102, 241';
  };
  document.documentElement.style.setProperty('--amp-primary-rgb', hexToRgb(ampVars.accentColor));

  // Handle Light/Dark/Auto-System
  const applyThemeMode = (mode) => {
    if (mode === 'dark') {
      document.body.classList.add('amp-dark-mode');
    } else if (mode === 'light') {
      document.body.classList.remove('amp-dark-mode');
    } else {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (prefersDark) {
        document.body.classList.add('amp-dark-mode');
      } else {
        document.body.classList.remove('amp-dark-mode');
      }
    }
  };
  applyThemeMode(ampVars.themeMode);

  // Focus tracking to conserve server processing (Adaptive Polling)
  window.addEventListener('focus', () => { state.isTabFocused = true; restartPolling(); });
  window.addEventListener('blur', () => { state.isTabFocused = false; restartPolling(); });

  // Web Audio Synth for notifications (No dependencies/assets)
  const playNotificationSound = () => {
    try {
      if (!state.audioCtx) {
        state.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      }
      if (state.audioCtx.state === 'suspended') {
        state.audioCtx.resume();
      }
      const osc = state.audioCtx.createOscillator();
      const gain = state.audioCtx.createGain();

      osc.type = 'sine';
      osc.frequency.setValueAtTime(587.33, state.audioCtx.currentTime); // D5
      osc.frequency.exponentialRampToValueAtTime(880, state.audioCtx.currentTime + 0.15); // A5

      gain.gain.setValueAtTime(0.15, state.audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, state.audioCtx.currentTime + 0.3);

      osc.connect(gain);
      gain.connect(state.audioCtx.destination);

      osc.start();
      osc.stop(state.audioCtx.currentTime + 0.3);
    } catch (e) {
      console.warn("Synth failed or blocked by autoplay browser restrictions.", e);
    }
  };

  // Request browser desktop notification permissions safely
  if (Notification && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  const triggerDesktopNotification = (title, body, avatarUrl) => {
    if (Notification && Notification.permission === 'granted' && !state.isTabFocused) {
      new Notification(title, {
        body: body,
        icon: avatarUrl || ampVars.currentUserAvatar,
      });
    }
  };

  // Markdown formatting engine + basic HTML escaper to protect from XSS injection
  const parseMarkdown = (text) => {
    if (!text) return '';
    let escaped = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    // Handle code block
    escaped = escaped.replace(/```([\s\S]+?)```/g, '<pre><code>$1</code></pre>');
    // Inline code
    escaped = escaped.replace(/`([^`\n]+?)`/g, '<code>$1</code>');
    // Bold
    escaped = escaped.replace(/\*\*([^*]+?)\*\*/g, '<strong>$1</strong>');
    // Italic
    escaped = escaped.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
    // Mention tag formatting: @username
    escaped = escaped.replace(/@([a-zA-Z0-9_\-\.]+)/g, '<span class="amp-mention">@$1</span>');

    return escaped;
  };

  // Format Dates relative (e.g. "Today at 3:14 PM")
  const formatFriendlyDate = (dateStr) => {
    const d = new Date(dateStr.replace(/-/g, '/'));
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();

    const time = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    if (isToday) {
      return `Today at ${time}`;
    }
    return `${d.toLocaleDateString([], { month: 'short', day: 'numeric' })} at ${time}`;
  };

  // Helper API Fetcher with custom WP Nonce headers
  const fetchAPI = async (endpoint, method = 'GET', body = null) => {
    const url = ampVars.restUrl + endpoint;
    const options = {
      method: method,
      headers: {
        'X-WP-Nonce': ampVars.nonce,
      },
    };

    if (body) {
      if (body instanceof FormData) {
        options.body = body;
      } else {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
      }
    }

    try {
      const response = await fetch(url, options);
      if (!response.ok) {
        const errData = await response.json();
        throw new Error(errData.message || 'API request error');
      }
      return await response.json();
    } catch (err) {
      console.error(`Fetch API Error (${endpoint}):`, err);
      throw err;
    }
  };

  // Fetch conversations and render
  const loadConversations = async () => {
    try {
      const conversations = await fetchAPI(`/chats?filter=${state.activeTab}`);
      state.conversations = conversations;
      renderConversations();
    } catch (e) {
      console.error("Could not fetch conversations", e);
    }
  };

  const renderConversations = () => {
    if (!dom.chatsList) return;
    dom.chatsList.innerHTML = '';

    if (state.conversations.length === 0) {
      dom.chatsList.innerHTML = '<p class="amp-empty-text" style="text-align:center; padding:20px;">No chats found.</p>';
      return;
    }

    state.conversations.forEach(conv => {
      const isSelected = state.activeConversationId === parseInt(conv.id);
      const activeClass = isSelected ? 'active' : '';
      const pinIcon = conv.is_pinned === "1" || conv.is_pinned === 1 ? '<span class="amp-pinned-badge dashicons dashicons-admin-links"></span>' : '';
      const muteIcon = conv.is_muted === "1" || conv.is_muted === 1 ? ' <span class="dashicons dashicons-volume-off" style="font-size:12px; color:var(--amp-text-secondary);"></span>' : '';

      let textPreview = 'No messages';
      if (conv.last_message) {
        textPreview = conv.last_message.message_text ? conv.last_message.message_text : '';
      }

      const timeLabel = conv.last_message ? formatFriendlyDate(conv.last_message.created_at) : '';
      const unreadCount = parseInt(conv.unread_count) || 0;
      const unreadBadge = unreadCount > 0 ? `<span class="amp-unread-badge">${unreadCount}</span>` : '';

      const item = document.createElement('div');
      item.className = `amp-chat-item ${activeClass}`;
      item.dataset.id = conv.id;

      item.innerHTML = `
        <img src="${conv.avatar || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'}" class="amp-chat-item-avatar" alt="Avatar">
        <div class="amp-chat-item-details">
          <div class="amp-chat-item-meta">
            <span class="amp-chat-item-title">${conv.title} ${pinIcon} ${muteIcon}</span>
            <span class="amp-chat-item-time">${timeLabel}</span>
          </div>
          <div class="amp-chat-item-preview-row">
            <span class="amp-chat-item-preview">${textPreview}</span>
            ${unreadBadge}
          </div>
        </div>
      `;

      item.addEventListener('click', () => switchConversation(parseInt(conv.id)));
      dom.chatsList.appendChild(item);
    });
  };

  const switchConversation = async (convId) => {
    state.activeConversationId = convId;
    dom.chatWindow.classList.remove('amp-empty');

    if (dom.appContainer) {
      dom.appContainer.classList.add('amp-mobile-active-chat');
    }

    // Show Chat interfaces
    dom.chatHeader.style.display = 'flex';
    dom.messagesArea.style.display = 'flex';
    dom.chatFooter.style.display = 'flex';

    // Find in local array
    const conv = state.conversations.find(c => parseInt(c.id) === convId);
    if (conv) {
      dom.activeTitle.innerText = conv.title;
      dom.activeAvatar.src = conv.avatar || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
      dom.activePresence.innerText = conv.status || 'offline';
      dom.activePresence.className = `amp-active-presence ${conv.status || 'offline'}`;

      // Update button states
      dom.activePin.style.color = (conv.is_pinned === "1" || conv.is_pinned === 1) ? 'var(--amp-primary)' : 'var(--amp-text-secondary)';
      dom.activeArchive.style.color = (conv.is_archived === "1" || conv.is_archived === 1) ? 'var(--amp-primary)' : 'var(--amp-text-secondary)';
      dom.activeMute.style.color = (conv.is_muted === "1" || conv.is_muted === 1) ? 'var(--amp-primary)' : 'var(--amp-text-secondary)';
    }

    renderConversations();
    await loadMessages(convId);
    await markAsRead(convId);
    await loadRightPanelDetails(convId);
  };

  const markAsRead = async (convId) => {
    try {
      await fetchAPI(`/chats/${convId}/read`, 'POST');
      loadConversations();
    } catch (e) {
      console.warn("Could not mark chat read", e);
    }
  };

  const loadMessages = async (convId) => {
    try {
      const messages = await fetchAPI(`/chats/${convId}/messages?limit=50`);
      state.messages = messages;
      renderMessages();
      scrollChatToBottom();
    } catch (e) {
      console.error("Could not fetch messages", e);
    }
  };

  const renderMessages = () => {
    dom.messagesArea.innerHTML = '';
    let lastDate = '';

    state.messages.forEach(msg => {
      // Date Separator grouping
      const msgDate = new Date(msg.created_at.replace(/-/g, '/')).toLocaleDateString();
      if (msgDate !== lastDate) {
        lastDate = msgDate;
        const sep = document.createElement('div');
        sep.className = 'amp-date-separator';
        sep.style.cssText = 'text-align: center; margin: 15px 0; font-size: 11px; font-weight: bold; color: var(--amp-text-secondary); text-transform: uppercase;';
        sep.innerText = new Date(msg.created_at.replace(/-/g, '/')).toLocaleDateString([], { weekday: 'long', month: 'short', day: 'numeric' });
        dom.messagesArea.appendChild(sep);
      }

      const isMe = parseInt(msg.sender_id) === ampVars.currentUserId;
      const rowClass = isMe ? 'me' : 'other';

      const row = document.createElement('div');
      row.className = `amp-message-row ${rowClass}`;
      row.dataset.id = msg.id;

      // Reactions formatting
      let reactionsMarkup = '';
      if (msg.reactions && msg.reactions.length > 0) {
        reactionsMarkup = `<div class="amp-reactions-row">`;
        msg.reactions.forEach(react => {
          const userHasReacted = react.user_ids.includes(ampVars.currentUserId) ? 'me-reacted' : '';
          reactionsMarkup += `
            <span class="amp-reaction-chip ${userHasReacted}" data-emoji="${react.emoji}">
              ${react.emoji} ${react.count}
            </span>
          `;
        });
        reactionsMarkup += `</div>`;
      }

      // Attachment rendering
      let attachmentsMarkup = '';
      if (msg.attachments && msg.attachments.length > 0) {
        attachmentsMarkup = `<div class="amp-msg-attachments">`;
        msg.attachments.forEach(file => {
          if (file.type.indexOf('image') !== -1) {
            attachmentsMarkup += `<img src="${file.url}" class="amp-preview-image" alt="${file.name}">`;
          } else if (file.type.indexOf('video') !== -1) {
            attachmentsMarkup += `<video src="${file.url}" class="amp-preview-video" controls></video>`;
          } else if (file.type.indexOf('audio') !== -1) {
            attachmentsMarkup += `<audio src="${file.url}" class="amp-preview-audio" controls></audio>`;
          } else {
            // General document download button
            attachmentsMarkup += `
              <a href="${file.url}" class="amp-file-attachment" target="_blank">
                <span class="dashicons dashicons-media-document"></span>
                <div>
                  <strong>${file.name}</strong><br>
                  <small>${file.size}</small>
                </div>
              </a>
            `;
          }
        });
        attachmentsMarkup += `</div>`;
      }

      // Thread parent preview
      let replyMarkup = '';
      if (msg.reply_to) {
        replyMarkup = `
          <div class="amp-bubble-reply">
            <strong>${msg.reply_to.sender_name}</strong>
            <span>${msg.reply_to.text}</span>
          </div>
        `;
      }

      const editedMark = parseInt(msg.is_edited) === 1 ? ' <small style="opacity:0.6;">(edited)</small>' : '';

      row.innerHTML = `
        <img src="${msg.sender_avatar}" class="amp-msg-avatar" alt="Avatar">
        <div class="amp-msg-bubble-wrapper">
          <div class="amp-msg-bubble">
            ${replyMarkup}
            <div class="amp-msg-sender">${msg.sender_name}</div>
            <div class="amp-msg-text">${parseMarkdown(msg.message_text)}</div>
            ${attachmentsMarkup}
            <div class="amp-msg-meta">
              <span>${new Date(msg.created_at.replace(/-/g, '/')).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}${editedMark}</span>
              <span class="amp-msg-actions-trigger dashicons dashicons-ellipsis"></span>
            </div>

            <!-- Contextual Menu -->
            <div class="amp-msg-bubble-menu">
              <button class="amp-action-reply"><span class="dashicons dashicons-undo"></span> Reply</button>
              <button class="amp-action-react" data-emoji="👍">👍 React 👍</button>
              <button class="amp-action-react" data-emoji="❤️">❤️ React ❤️</button>
              <button class="amp-action-react" data-emoji="🔥">🔥 React 🔥</button>
              <button class="amp-action-star"><span class="dashicons dashicons-star-filled"></span> Star/Unstar</button>
              ${isMe ? `
                <button class="amp-action-edit"><span class="dashicons dashicons-edit"></span> Edit</button>
                <button class="amp-action-delete" style="color:var(--amp-text-secondary);"><span class="dashicons dashicons-trash"></span> Delete</button>
              ` : ''}
            </div>
          </div>
          ${reactionsMarkup}
        </div>
      `;

      // Handle custom user interaction inside individual bubble menus
      const actionsTrigger = row.querySelector('.amp-msg-actions-trigger');
      const menu = row.querySelector('.amp-msg-bubble-menu');
      if (actionsTrigger && menu) {
        actionsTrigger.addEventListener('click', (e) => {
          e.stopPropagation();
          // Close other open menus
          document.querySelectorAll('.amp-msg-bubble-menu.active').forEach(m => m.classList.remove('active'));
          menu.classList.add('active');
        });
      }

      // Event handlers for bubble action items
      const replyBtn = row.querySelector('.amp-action-reply');
      if (replyBtn) {
        replyBtn.addEventListener('click', () => {
          state.replyParentId = msg.id;
          dom.replyBannerSender.innerText = `Replying to ${msg.sender_name}`;
          dom.replyBannerText.innerText = msg.message_text || '📎 [Attachment]';
          dom.replyBanner.style.display = 'flex';
          menu.classList.remove('active');
          dom.messageInput.focus();
        });
      }

      // Handle Edit Action
      const editBtn = row.querySelector('.amp-action-edit');
      if (editBtn) {
        editBtn.addEventListener('click', () => {
          const newText = prompt("Edit your message:", msg.message_text);
          if (newText && newText.trim() !== "") {
            fetchAPI(`/messages/${msg.id}`, 'PUT', { message_text: newText }).then(() => {
              loadMessages(state.activeConversationId);
            });
          }
          menu.classList.remove('active');
        });
      }

      // Handle Delete Action
      const deleteBtn = row.querySelector('.amp-action-delete');
      if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
          if (confirm("Delete this message?")) {
            fetchAPI(`/messages/${msg.id}?mode=everyone`, 'DELETE').then(() => {
              loadMessages(state.activeConversationId);
            });
          }
          menu.classList.remove('active');
        });
      }

      // Handle Star Action
      const starBtn = row.querySelector('.amp-action-star');
      if (starBtn) {
        starBtn.addEventListener('click', () => {
          fetchAPI(`/messages/${msg.id}/star`, 'POST').then(() => {
            alert("Starred state toggled!");
            loadMessages(state.activeConversationId);
          });
          menu.classList.remove('active');
        });
      }

      // Handle Reactions Click
      row.querySelectorAll('.amp-action-react').forEach(btn => {
        btn.addEventListener('click', () => {
          const emoji = btn.dataset.emoji;
          toggleReaction(msg.id, emoji);
          menu.classList.remove('active');
        });
      });

      row.querySelectorAll('.amp-reaction-chip').forEach(chip => {
        chip.addEventListener('click', () => {
          const emoji = chip.dataset.emoji;
          toggleReaction(msg.id, emoji);
        });
      });

      // Handle Lightbox on images click
      row.querySelectorAll('.amp-preview-image').forEach(img => {
        img.addEventListener('click', () => {
          dom.lightboxImg.src = img.src;
          dom.lightboxImg.style.display = 'block';
          dom.lightboxVideo.style.display = 'none';
          dom.lightbox.style.display = 'flex';
        });
      });

      dom.messagesArea.appendChild(row);
    });
  };

  // Click on lightbox background to close
  dom.lightbox.addEventListener('click', () => {
    dom.lightbox.style.display = 'none';
  });

  const toggleReaction = async (msgId, emoji) => {
    try {
      await fetchAPI(`/messages/${msgId}/react`, 'POST', { emoji: emoji });
      await loadMessages(state.activeConversationId);
    } catch (e) {
      console.warn("Could not react to message", e);
    }
  };

  const scrollChatToBottom = () => {
    dom.messagesArea.scrollTop = dom.messagesArea.scrollHeight;
  };

  // Close context menu clicking outside
  document.addEventListener('click', () => {
    document.querySelectorAll('.amp-msg-bubble-menu.active').forEach(m => m.classList.remove('active'));
  });

  // Handle Send message event
  const sendMessage = async () => {
    const text = dom.messageInput.value.trim();
    if (!text && !attachmentsToUpload.length) return;

    dom.messageInput.value = '';
    dom.messageInput.style.height = '36px';

    const payload = {
      message_text: text,
      parent_id: state.replyParentId,
    };

    if (state.replyParentId) {
      state.replyParentId = null;
      dom.replyBanner.style.display = 'none';
    }

    try {
      await fetchAPI(`/chats/${state.activeConversationId}/messages`, 'POST', payload);
      await loadMessages(state.activeConversationId);
      loadConversations();
    } catch (e) {
      console.error("Could not send message", e);
    }
  };

  dom.sendBtn.addEventListener('click', sendMessage);
  dom.messageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  // Close reply banner
  dom.replyBannerClose.addEventListener('click', () => {
    state.replyParentId = null;
    dom.replyBanner.style.display = 'none';
  });

  // Handle Toggle Header Actions: Pin, Archive, Mute
  dom.activePin.addEventListener('click', async () => {
    try {
      const res = await fetchAPI(`/chats/${state.activeConversationId}/pin`, 'POST');
      dom.activePin.style.color = res.pinned ? 'var(--amp-primary)' : 'var(--amp-text-secondary)';
      loadConversations();
    } catch (e) {
      console.warn("Could not toggle pin", e);
    }
  });

  dom.activeArchive.addEventListener('click', async () => {
    try {
      const res = await fetchAPI(`/chats/${state.activeConversationId}/archive`, 'POST');
      dom.activeArchive.style.color = res.archived ? 'var(--amp-primary)' : 'var(--amp-text-secondary)';
      loadConversations();
      // If archived, we might want to empty active chat window
      if (res.archived && state.activeTab !== 'archived') {
        state.activeConversationId = null;
        dom.chatWindow.classList.add('amp-empty');
        dom.chatHeader.style.display = 'none';
        dom.messagesArea.style.display = 'none';
        dom.chatFooter.style.display = 'none';
      }
    } catch (e) {
      console.warn("Could not toggle archive", e);
    }
  });

  dom.activeMute.addEventListener('click', async () => {
    try {
      const res = await fetchAPI(`/chats/${state.activeConversationId}/mute`, 'POST');
      dom.activeMute.style.color = res.muted ? 'var(--amp-primary)' : 'var(--amp-text-secondary)';
      loadConversations();
    } catch (e) {
      console.warn("Could not toggle mute", e);
    }
  });

  // Presence Status select heartbeat
  const updatePresenceHeartbeat = async () => {
    const status = dom.myPresence.value;
    try {
      await fetchAPI('/presence', 'POST', { status: status });
    } catch (e) {
      console.warn("Presence update failed", e);
    }
  };

  dom.myPresence.addEventListener('change', updatePresenceHeartbeat);
  setInterval(updatePresenceHeartbeat, 30000); // 30s heartbeat
  updatePresenceHeartbeat();

  // Sidebar Filter tabs switcher
  dom.tabs.forEach(btn => {
    btn.addEventListener('click', () => {
      dom.tabs.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      state.activeTab = btn.dataset.tab;
      loadConversations();
    });
  });

  // Drag and Drop files handling
  let attachmentsToUpload = [];

  dom.messagesArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    dom.messagesArea.classList.add('drag-over');
  });

  dom.messagesArea.addEventListener('dragleave', () => {
    dom.messagesArea.classList.remove('drag-over');
  });

  dom.messagesArea.addEventListener('drop', async (e) => {
    e.preventDefault();
    dom.messagesArea.classList.remove('drag-over');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
      for (let i = 0; i < files.length; i++) {
        await handleDirectFileUpload(files[i]);
      }
    }
  });

  // Media Library WP Uploader Integration fallback
  dom.attachFileBtn.addEventListener('click', (e) => {
    e.preventDefault();
    const mediaUploader = wp.media({
      title: 'Attach Media to Chat',
      button: {
        text: 'Attach file'
      },
      multiple: true
    });

    mediaUploader.on('select', function () {
      const selections = mediaUploader.state().get('selection').toJSON();
      const filesFormatted = selections.map(sel => ({
        id: sel.id,
        name: sel.filename,
        url: sel.url,
        type: sel.mime,
        size: sel.filesizeHumanReadable || 'Unknown'
      }));

      // Directly send files inside message
      fetchAPI(`/chats/${state.activeConversationId}/messages`, 'POST', {
        message_text: '',
        attachments: filesFormatted
      }).then(() => {
        loadMessages(state.activeConversationId);
        loadConversations();
      });
    });

    mediaUploader.open();
  });

  const handleDirectFileUpload = async (file) => {
    const formData = new FormData();
    formData.append('file', file);

    try {
      const uploadRes = await fetchAPI('/upload', 'POST', formData);
      // Automatically send message with file attachment
      await fetchAPI(`/chats/${state.activeConversationId}/messages`, 'POST', {
        message_text: '',
        attachments: [ uploadRes ]
      });
      await loadMessages(state.activeConversationId);
      loadConversations();
    } catch (e) {
      alert("Upload failed: " + e.message);
    }
  };

  // Voice Recording Feature
  let mediaRecorder;
  let audioChunks = [];
  let isRecording = false;

  dom.voiceRecordBtn.addEventListener('click', async () => {
    if (!isRecording) {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = (event) => {
          audioChunks.push(event.data);
        };

        mediaRecorder.onstop = async () => {
          const audioBlob = new Blob(audioChunks, { type: 'audio/mp3' });
          const audioFile = new File([audioBlob], `voice-message-${Date.now()}.mp3`, { type: 'audio/mp3' });
          await handleDirectFileUpload(audioFile);
        };

        mediaRecorder.start();
        isRecording = true;
        dom.voiceRecordBtn.style.color = '#ef4444'; // Red recording light
        dom.voiceRecordBtn.classList.add('recording');
      } catch (err) {
        alert("Audio recording access denied or unsupported.");
      }
    } else {
      mediaRecorder.stop();
      isRecording = false;
      dom.voiceRecordBtn.style.color = 'var(--amp-text-secondary)';
      dom.voiceRecordBtn.classList.remove('recording');
    }
  });

  // Collapsible Right details panel
  dom.infoToggle.addEventListener('click', () => {
    dom.rightPanel.classList.toggle('collapsed');
  });
  dom.panelClose.addEventListener('click', () => {
    dom.rightPanel.classList.add('collapsed');
  });

  const loadRightPanelDetails = async (convId) => {
    try {
      const resources = await fetchAPI(`/chats/${convId}/resources`);

      // Render Shared Media
      dom.sharedMediaGrid.innerHTML = '';
      if (resources.media && resources.media.length > 0) {
        resources.media.forEach(media => {
          const img = document.createElement('img');
          img.src = media.url;
          img.addEventListener('click', () => {
            dom.lightboxImg.src = media.url;
            dom.lightboxImg.style.display = 'block';
            dom.lightboxVideo.style.display = 'none';
            dom.lightbox.style.display = 'flex';
          });
          dom.sharedMediaGrid.appendChild(img);
        });
      } else {
        dom.sharedMediaGrid.innerHTML = '<p class="amp-empty-text">No media shared yet.</p>';
      }

      // Render Shared Files
      dom.sharedFilesList.innerHTML = '';
      if (resources.files && resources.files.length > 0) {
        resources.files.forEach(file => {
          const row = document.createElement('a');
          row.className = 'amp-file-attachment';
          row.href = file.url;
          row.target = '_blank';
          row.style.marginBottom = '5px';
          row.innerHTML = `
            <span class="dashicons dashicons-media-document"></span>
            <div>
              <strong>${file.name}</strong><br>
              <small>${file.size} - By ${file.sender_name}</small>
            </div>
          `;
          dom.sharedFilesList.appendChild(row);
        });
      } else {
        dom.sharedFilesList.innerHTML = '<p class="amp-empty-text">No files shared yet.</p>';
      }

      // Render Pinned Messages (simplistic filtered listing)
      dom.pinnedMessagesList.innerHTML = '';
      const pinned = state.messages.filter(m => parseInt(m.is_pinned) === 1);
      if (pinned.length > 0) {
        pinned.forEach(p => {
          const row = document.createElement('div');
          row.style.cssText = 'padding:8px; background:rgba(0,0,0,0.03); border-radius:8px; margin-bottom:5px; font-size:12px;';
          row.innerHTML = `
            <strong>${p.sender_name}</strong><br>
            <span>${p.message_text || '📎 shared attachment'}</span>
          `;
          dom.pinnedMessagesList.appendChild(row);
        });
      } else {
        dom.pinnedMessagesList.innerHTML = '<p class="amp-empty-text">No pinned messages yet.</p>';
      }

    } catch (e) {
      console.warn("Could not load right panel details", e);
    }
  };

  // Dialog handlers: New Private Chat
  dom.newPrivateBtn.addEventListener('click', () => {
    dom.dialogPrivate.style.display = 'flex';
    dom.userSearchInput.value = '';
    dom.userSearchResults.innerHTML = '';
    dom.userSearchInput.focus();
  });

  dom.dialogPrivateCancel.addEventListener('click', () => {
    dom.dialogPrivate.style.display = 'none';
  });

  dom.userSearchInput.addEventListener('input', () => {
    const term = dom.userSearchInput.value.trim();
    if (term.length < 2) return;

    fetchAPI(`/users/search?term=${term}`).then(users => {
      dom.userSearchResults.innerHTML = '';
      if (users.length === 0) {
        dom.userSearchResults.innerHTML = '<p class="amp-empty-text">No users found.</p>';
        return;
      }

      users.forEach(u => {
        const row = document.createElement('div');
        row.className = 'amp-user-result-row';
        row.innerHTML = `
          <img src="${u.avatar}" alt="Avatar">
          <div>
            <div class="amp-user-name">${u.name}</div>
            <div class="amp-user-role">${u.role}</div>
          </div>
        `;
        row.addEventListener('click', () => {
          fetchAPI('/chats', 'POST', { type: 'private', target_user_id: u.id }).then(res => {
            dom.dialogPrivate.style.display = 'none';
            loadConversations().then(() => switchConversation(parseInt(res.id)));
          });
        });
        dom.userSearchResults.appendChild(row);
      });
    });
  });

  // Group creation logic
  let selectedGroupMembers = [];

  dom.newGroupBtn.addEventListener('click', () => {
    dom.dialogGroup.style.display = 'flex';
    dom.groupTitleInput.value = '';
    dom.groupUserSearch.value = '';
    dom.groupUserResults.innerHTML = '';
    dom.groupSelectedMembers.innerHTML = '';
    selectedGroupMembers = [];
  });

  dom.dialogGroupCancel.addEventListener('click', () => {
    dom.dialogGroup.style.display = 'none';
  });

  dom.groupUserSearch.addEventListener('input', () => {
    const term = dom.groupUserSearch.value.trim();
    if (term.length < 2) return;

    fetchAPI(`/users/search?term=${term}`).then(users => {
      dom.groupUserResults.innerHTML = '';
      users.forEach(u => {
        if (selectedGroupMembers.some(m => m.id === u.id)) return;

        const row = document.createElement('div');
        row.className = 'amp-user-result-row';
        row.innerHTML = `
          <img src="${u.avatar}" alt="Avatar">
          <div>
            <div class="amp-user-name">${u.name}</div>
            <div class="amp-user-role">${u.role}</div>
          </div>
        `;
        row.addEventListener('click', () => {
          selectedGroupMembers.push(u);
          renderSelectedGroupMembers();
          dom.groupUserSearch.value = '';
          dom.groupUserResults.innerHTML = '';
        });
        dom.groupUserResults.appendChild(row);
      });
    });
  });

  const renderSelectedGroupMembers = () => {
    dom.groupSelectedMembers.innerHTML = '';
    selectedGroupMembers.forEach(u => {
      const tag = document.createElement('span');
      tag.style.cssText = 'display:inline-flex; align-items:center; gap:5px; background:var(--amp-primary); color:#fff; border-radius:12px; padding:4px 8px; font-size:11px; margin: 2px;';
      tag.innerHTML = `${u.name} <span class="dashicons dashicons-no-alt" style="font-size:12px; cursor:pointer;"></span>`;
      tag.querySelector('.dashicons').addEventListener('click', () => {
        selectedGroupMembers = selectedGroupMembers.filter(m => m.id !== u.id);
        renderSelectedGroupMembers();
      });
      dom.groupSelectedMembers.appendChild(tag);
    });
  };

  dom.dialogGroupSubmit.addEventListener('click', () => {
    const title = dom.groupTitleInput.value.trim();
    if (!title) {
      alert("Group title is required.");
      return;
    }

    const payload = {
      type: 'group',
      title: title,
      members: selectedGroupMembers.map(m => m.id)
    };

    fetchAPI('/chats', 'POST', payload).then(res => {
      dom.dialogGroup.style.display = 'none';
      loadConversations().then(() => switchConversation(parseInt(res.id)));
    });
  });

  // Global Multi-field Search Event
  dom.globalSearch.addEventListener('input', () => {
    const query = dom.globalSearch.value.trim();
    if (query.length < 2) {
      loadConversations();
      return;
    }

    fetchAPI(`/search?query=${query}`).then(res => {
      dom.chatsList.innerHTML = '<h5 style="margin:5px 0; color:var(--amp-text-secondary);">SEARCH RESULTS</h5>';

      if (res.messages.length === 0 && res.files.length === 0) {
        dom.chatsList.innerHTML += '<p class="amp-empty-text" style="text-align:center;">No results matched query.</p>';
        return;
      }

      // Display matched message logs
      res.messages.forEach(msg => {
        const item = document.createElement('div');
        item.className = `amp-chat-item`;
        item.innerHTML = `
          <div class="amp-chat-item-details">
            <div class="amp-chat-item-meta">
              <span class="amp-chat-item-title">${msg.sender_name} (in ${msg.conversation_title || 'Chat'})</span>
              <span class="amp-chat-item-time">${formatFriendlyDate(msg.created_at)}</span>
            </div>
            <span class="amp-chat-item-preview">${msg.message_text}</span>
          </div>
        `;
        item.addEventListener('click', () => switchConversation(parseInt(msg.conversation_id)));
        dom.chatsList.appendChild(item);
      });
    });
  });

  // Adaptive Fallback Polling Loop
  const pollForNewData = async () => {
    if (!state.isTabFocused) return;

    try {
      // Background heartbeat check
      const currentConvs = await fetchAPI(`/chats?filter=${state.activeTab}`);

      // Play sound and trigger notification if new unread count is higher than existing
      let triggerNotification = false;
      let notificationTitle = 'New Admin Message';
      let notificationBody = '';

      currentConvs.forEach(newC => {
        const oldC = state.conversations.find(c => parseInt(c.id) === parseInt(newC.id));
        if (oldC) {
          const oldUnread = parseInt(oldC.unread_count) || 0;
          const newUnread = parseInt(newC.unread_count) || 0;
          if (newUnread > oldUnread && (newC.is_muted !== "1" && newC.is_muted !== 1)) {
            triggerNotification = true;
            notificationTitle = `Message from ${newC.title}`;
            notificationBody = newC.last_message ? newC.last_message.message_text : '📎 Sent attachment';
          }
        }
      });

      state.conversations = currentConvs;
      renderConversations();

      if (triggerNotification) {
        playNotificationSound();
        triggerDesktopNotification(notificationTitle, notificationBody);
      }

      // If active chat has loaded, reload message history stream
      if (state.activeConversationId) {
        const oldMessagesCount = state.messages.length;
        const freshMsgs = await fetchAPI(`/chats/${state.activeConversationId}/messages?limit=50`);
        state.messages = freshMsgs;
        renderMessages();

        // Auto-scroll if new message was appended
        if (freshMsgs.length > oldMessagesCount) {
          scrollChatToBottom();
        }
      }
    } catch (e) {
      console.warn("Polling request failed", e);
    }
  };

  const restartPolling = () => {
    if (state.pollingTimer) clearInterval(state.pollingTimer);
    if (state.isTabFocused) {
      state.pollingTimer = setInterval(pollForNewData, ampVars.pollingInterval);
    } else {
      // 4x slower fallback offline polling to keep server usage minimum
      state.pollingTimer = setInterval(pollForNewData, ampVars.pollingInterval * 4);
    }
  };

  // Mobile Navigation Back Button
  if (dom.mobileBackBtn) {
    dom.mobileBackBtn.addEventListener('click', () => {
      if (dom.appContainer) {
        dom.appContainer.classList.remove('amp-mobile-active-chat');
      }
    });
  }

  // Register PWA Service Worker for standalone Android app support
  if ('serviceWorker' in navigator && ampVars.swUrl) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(ampVars.swUrl)
        .then(reg => console.log('Admin Messenger Pro PWA Service Worker registered successfully:', reg.scope))
        .catch(err => console.warn('PWA Service Worker registration failed:', err));
    });
  }

  // Initial Load Triggering
  loadConversations();
  restartPolling();
});
