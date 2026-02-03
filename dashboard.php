<?php
/**
 * Kişisel Dashboard - Ana Sayfa
 */

require_once 'includes/auth.php';

startSecureSession();
requireLogin();

$user = getCurrentUser();
$settings = getUserSettings();
$csrfToken = generateCSRFToken();

// Varsayılan değerler
$weatherCity = $settings['weather_city'] ?? 'Istanbul';
$weatherCountry = $settings['weather_country'] ?? 'TR';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18"/>
                    <path d="M9 21V9"/>
                </svg>
            </div>
            <h1 class="app-title">Dashboard</h1>
        </div>

        <nav class="sidebar-nav">
            <a href="#" class="nav-item active" data-section="overview">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Genel Bakış</span>
            </a>
            <a href="#" class="nav-item" data-section="payments">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                    <line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
                <span>Ödemeler</span>
            </a>
            <a href="#" class="nav-item" data-section="calendar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <span>Takvim</span>
            </a>
            <a href="#" class="nav-item" data-section="notes">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                <span>Notlar</span>
            </a>
            <a href="#" class="nav-item" data-section="todos">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span>Yapılacaklar</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
            </div>
            <button class="btn-settings" onclick="openSettings()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </button>
            <a href="logout.php" class="btn-logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <button class="btn-menu-toggle" onclick="toggleSidebar()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <div class="greeting">
                    <h2 class="greeting-title">Merhaba, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!</h2>
                    <p class="greeting-subtitle" id="current-date"></p>
                </div>
            </div>
            <div class="header-right">
                <div class="weather-widget" id="weather-widget">
                    <div class="weather-loading">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Quick Stats -->
            <section class="stats-row">
                <div class="stat-card" id="stat-payments">
                    <div class="stat-icon payments">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value" id="pending-payments">-</span>
                        <span class="stat-label">Bekleyen Ödeme</span>
                    </div>
                </div>
                <div class="stat-card" id="stat-events">
                    <div class="stat-icon events">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value" id="upcoming-events">-</span>
                        <span class="stat-label">Yaklaşan Etkinlik</span>
                    </div>
                </div>
                <div class="stat-card" id="stat-todos">
                    <div class="stat-icon todos">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value" id="pending-todos">-</span>
                        <span class="stat-label">Yapılacak İş</span>
                    </div>
                </div>
                <div class="stat-card" id="stat-notes">
                    <div class="stat-icon notes">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value" id="total-notes">-</span>
                        <span class="stat-label">Not</span>
                    </div>
                </div>
            </section>

            <!-- Main Widgets -->
            <div class="widgets-grid">
                <!-- Calendar Widget -->
                <section class="widget widget-calendar">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            Takvim
                        </h3>
                        <button class="btn-add" onclick="openModal('calendar')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="calendar-mini" id="calendar-mini">
                        <!-- Mini takvim JS ile doldurulacak -->
                    </div>
                    <div class="widget-content">
                        <div class="events-list" id="events-list">
                            <div class="loading-placeholder">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Payments Widget -->
                <section class="widget widget-payments">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2"/>
                                <line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                            Ödeme Takibi
                        </h3>
                        <button class="btn-add" onclick="openModal('payment')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="payments-list" id="payments-list">
                            <div class="loading-placeholder">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Todos Widget -->
                <section class="widget widget-todos">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"/>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                            Yapılacaklar
                        </h3>
                        <button class="btn-add" onclick="openModal('todo')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="todos-list" id="todos-list">
                            <div class="loading-placeholder">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Notes Widget -->
                <section class="widget widget-notes">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            Notlar
                        </h3>
                        <button class="btn-add" onclick="openModal('note')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="widget-content">
                        <div class="notes-grid" id="notes-list">
                            <div class="loading-placeholder">
                                <div class="spinner"></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div class="modal-overlay" id="modal-overlay" onclick="closeModal()"></div>

    <!-- Payment Modal -->
    <div class="modal" id="payment-modal">
        <div class="modal-header">
            <h3 class="modal-title">Yeni Ödeme</h3>
            <button class="btn-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="payment-form" onsubmit="savePayment(event)">
            <input type="hidden" name="id" id="payment-id">
            <div class="form-group">
                <label class="form-label">Başlık</label>
                <input type="text" name="title" id="payment-title" class="form-input" required placeholder="Ör: Elektrik Faturası">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tutar</label>
                    <input type="number" name="amount" id="payment-amount" class="form-input" required step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Para Birimi</label>
                    <select name="currency" id="payment-currency" class="form-input">
                        <option value="TRY">₺ TRY</option>
                        <option value="USD">$ USD</option>
                        <option value="EUR">€ EUR</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Vade Tarihi</label>
                    <input type="date" name="due_date" id="payment-due-date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category" id="payment-category" class="form-input">
                        <option value="fatura">Fatura</option>
                        <option value="kira">Kira</option>
                        <option value="kredi">Kredi</option>
                        <option value="abonelik">Abonelik</option>
                        <option value="diger">Diğer</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_recurring" id="payment-recurring">
                    Tekrarlayan Ödeme
                </label>
            </div>
            <div class="form-group recurring-options" id="recurring-options" style="display:none;">
                <label class="form-label">Tekrar Periyodu</label>
                <select name="recurring_period" id="payment-recurring-period" class="form-input">
                    <option value="monthly">Aylık</option>
                    <option value="weekly">Haftalık</option>
                    <option value="yearly">Yıllık</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notlar</label>
                <textarea name="notes" id="payment-notes" class="form-input" rows="2" placeholder="İsteğe bağlı notlar..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>

    <!-- Calendar Event Modal -->
    <div class="modal" id="calendar-modal">
        <div class="modal-header">
            <h3 class="modal-title">Yeni Etkinlik</h3>
            <button class="btn-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="calendar-form" onsubmit="saveEvent(event)">
            <input type="hidden" name="id" id="event-id">
            <div class="form-group">
                <label class="form-label">Başlık</label>
                <input type="text" name="title" id="event-title" class="form-input" required placeholder="Ör: Doğum Günü">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tarih</label>
                    <input type="date" name="event_date" id="event-date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Saat (İsteğe bağlı)</label>
                    <input type="time" name="event_time" id="event-time" class="form-input">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Etkinlik Türü</label>
                <select name="event_type" id="event-type" class="form-input">
                    <option value="birthday">🎂 Doğum Günü</option>
                    <option value="anniversary">💍 Yıldönümü</option>
                    <option value="meeting">📅 Toplantı</option>
                    <option value="reminder">⏰ Hatırlatıcı</option>
                    <option value="holiday">🎉 Tatil</option>
                    <option value="other">📌 Diğer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Renk</label>
                <div class="color-picker">
                    <label class="color-option"><input type="radio" name="color" value="#6366f1" checked><span style="background:#6366f1"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#10b981"><span style="background:#10b981"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#f59e0b"><span style="background:#f59e0b"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#ef4444"><span style="background:#ef4444"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#8b5cf6"><span style="background:#8b5cf6"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#ec4899"><span style="background:#ec4899"></span></label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_recurring" id="event-recurring">
                    Her yıl tekrarla
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <textarea name="description" id="event-description" class="form-input" rows="2" placeholder="İsteğe bağlı açıklama..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>

    <!-- Todo Modal -->
    <div class="modal" id="todo-modal">
        <div class="modal-header">
            <h3 class="modal-title">Yeni Görev</h3>
            <button class="btn-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="todo-form" onsubmit="saveTodo(event)">
            <input type="hidden" name="id" id="todo-id">
            <div class="form-group">
                <label class="form-label">Görev</label>
                <input type="text" name="title" id="todo-title" class="form-input" required placeholder="Ne yapılacak?">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Öncelik</label>
                    <select name="priority" id="todo-priority" class="form-input">
                        <option value="low">Düşük</option>
                        <option value="medium" selected>Orta</option>
                        <option value="high">Yüksek</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Bitiş Tarihi</label>
                    <input type="date" name="due_date" id="todo-due-date" class="form-input">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori</label>
                <input type="text" name="category" id="todo-category" class="form-input" placeholder="Ör: İş, Kişisel, Alışveriş">
            </div>
            <div class="form-group">
                <label class="form-label">Açıklama</label>
                <textarea name="description" id="todo-description" class="form-input" rows="2" placeholder="Detaylar..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>

    <!-- Note Modal -->
    <div class="modal" id="note-modal">
        <div class="modal-header">
            <h3 class="modal-title">Yeni Not</h3>
            <button class="btn-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="note-form" onsubmit="saveNote(event)">
            <input type="hidden" name="id" id="note-id">
            <div class="form-group">
                <label class="form-label">Başlık</label>
                <input type="text" name="title" id="note-title" class="form-input" required placeholder="Not başlığı">
            </div>
            <div class="form-group">
                <label class="form-label">Renk</label>
                <div class="color-picker">
                    <label class="color-option"><input type="radio" name="color" value="#fbbf24" checked><span style="background:#fbbf24"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#34d399"><span style="background:#34d399"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#60a5fa"><span style="background:#60a5fa"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#f472b6"><span style="background:#f472b6"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#a78bfa"><span style="background:#a78bfa"></span></label>
                    <label class="color-option"><input type="radio" name="color" value="#fb7185"><span style="background:#fb7185"></span></label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">İçerik</label>
                <textarea name="content" id="note-content" class="form-input" rows="6" placeholder="Notunuzu yazın..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="is_pinned" id="note-pinned">
                    Sabitle
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>

    <!-- Settings Modal -->
    <div class="modal" id="settings-modal">
        <div class="modal-header">
            <h3 class="modal-title">Ayarlar</h3>
            <button class="btn-close" onclick="closeModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form id="settings-form" onsubmit="saveSettings(event)">
            <div class="settings-section">
                <h4 class="settings-section-title">Hava Durumu</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Şehir</label>
                        <input type="text" name="weather_city" id="settings-city" class="form-input" value="<?php echo htmlspecialchars($weatherCity); ?>" placeholder="Ör: Istanbul">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ülke Kodu</label>
                        <input type="text" name="weather_country" id="settings-country" class="form-input" value="<?php echo htmlspecialchars($weatherCountry); ?>" maxlength="2" placeholder="TR">
                    </div>
                </div>
            </div>
            <div class="settings-section">
                <h4 class="settings-section-title">Hesap</h4>
                <div class="form-group">
                    <label class="form-label">Mevcut Şifre</label>
                    <input type="password" name="current_password" id="settings-current-password" class="form-input" placeholder="Şifre değiştirmek için doldurun">
                </div>
                <div class="form-group">
                    <label class="form-label">Yeni Şifre</label>
                    <input type="password" name="new_password" id="settings-new-password" class="form-input" placeholder="Yeni şifre (en az 6 karakter)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">İptal</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>

    <script>
        // Global değişkenler
        const CSRF_TOKEN = '<?php echo $csrfToken; ?>';
        const WEATHER_CITY = '<?php echo htmlspecialchars($weatherCity); ?>';
        const WEATHER_COUNTRY = '<?php echo htmlspecialchars($weatherCountry); ?>';
    </script>
    <script src="js/app.js"></script>
</body>
</html>
