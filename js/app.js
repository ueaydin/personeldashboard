/**
 * Kişisel Dashboard - Ana JavaScript
 * Modern Heritage Tasarımı için İnteraktif Özellikler
 */

// ===== Global Değişkenler =====
let currentCalendarDate = new Date();
const MONTHS_TR = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
const DAYS_TR = ['Pz', 'Pt', 'Sa', 'Ça', 'Pe', 'Cu', 'Ct'];

// ===== Sayfa Yüklendiğinde =====
document.addEventListener('DOMContentLoaded', () => {
    initializeDashboard();
});

async function initializeDashboard() {
    updateCurrentDate();
    renderMiniCalendar();
    await Promise.all([
        loadWeather(),
        loadPayments(),
        loadEvents(),
        loadTodos(),
        loadNotes(),
        loadStats()
    ]);

    // Event listeners
    setupEventListeners();
}

// ===== Tarih Güncelleme =====
function updateCurrentDate() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('current-date').textContent = now.toLocaleDateString('tr-TR', options);
}

// ===== Mini Takvim =====
function renderMiniCalendar() {
    const container = document.getElementById('calendar-mini');
    const year = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startingDay = firstDay.getDay();
    const totalDays = lastDay.getDate();

    const today = new Date();

    let html = `
        <div class="calendar-header">
            <span class="calendar-month">${MONTHS_TR[month]} ${year}</span>
            <div class="calendar-nav">
                <button onclick="changeMonth(-1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </button>
                <button onclick="changeMonth(1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="calendar-grid">
    `;

    // Gün isimleri
    for (let day of DAYS_TR) {
        html += `<div class="calendar-day-name">${day}</div>`;
    }

    // Önceki ayın günleri
    const prevMonthDays = new Date(year, month, 0).getDate();
    for (let i = startingDay - 1; i >= 0; i--) {
        html += `<div class="calendar-day other-month">${prevMonthDays - i}</div>`;
    }

    // Bu ayın günleri
    for (let day = 1; day <= totalDays; day++) {
        const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        html += `<div class="calendar-day${isToday ? ' today' : ''}" data-date="${dateStr}" onclick="selectDate('${dateStr}')">${day}</div>`;
    }

    // Sonraki ayın günleri
    const remainingDays = 42 - (startingDay + totalDays);
    for (let i = 1; i <= remainingDays; i++) {
        html += `<div class="calendar-day other-month">${i}</div>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

function changeMonth(delta) {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
    renderMiniCalendar();
}

function selectDate(dateStr) {
    openModal('calendar');
    document.getElementById('event-date').value = dateStr;
}

// ===== Hava Durumu =====
async function loadWeather() {
    const widget = document.getElementById('weather-widget');

    try {
        const response = await fetch(`api/weather.php?city=${WEATHER_CITY}&country=${WEATHER_COUNTRY}`);
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;
            const iconUrl = result.demo
                ? getWeatherEmoji(data.icon)
                : `https://openweathermap.org/img/wn/${data.icon}@2x.png`;

            widget.innerHTML = `
                ${result.demo
                    ? `<div class="weather-icon" style="font-size: 2.5rem;">${iconUrl}</div>`
                    : `<img src="${iconUrl}" alt="${data.description}" class="weather-icon">`
                }
                <div class="weather-info">
                    <span class="weather-temp">${data.temp}°C</span>
                    <span class="weather-desc">${data.description}</span>
                    <span class="weather-city">${data.city}, ${data.country}</span>
                </div>
            `;
        } else {
            widget.innerHTML = `<span class="weather-desc">Hava durumu yüklenemedi</span>`;
        }
    } catch (error) {
        console.error('Weather error:', error);
        widget.innerHTML = `<span class="weather-desc">Hava durumu yüklenemedi</span>`;
    }
}

function getWeatherEmoji(icon) {
    const emojis = {
        '01d': '☀️', '01n': '🌙',
        '02d': '⛅', '02n': '☁️',
        '03d': '☁️', '03n': '☁️',
        '04d': '☁️', '04n': '☁️',
        '09d': '🌧️', '09n': '🌧️',
        '10d': '🌦️', '10n': '🌧️',
        '11d': '⛈️', '11n': '⛈️',
        '13d': '❄️', '13n': '❄️',
        '50d': '🌫️', '50n': '🌫️'
    };
    return emojis[icon] || '🌤️';
}

// ===== Ödemeler =====
async function loadPayments() {
    const container = document.getElementById('payments-list');

    try {
        const response = await fetch('api/payments.php');
        const result = await response.json();

        if (result.payments && result.payments.length > 0) {
            container.innerHTML = result.payments.slice(0, 5).map(payment => `
                <div class="payment-item ${payment.status}" data-id="${payment.id}">
                    <div class="payment-icon">${getCategoryIcon(payment.category)}</div>
                    <div class="payment-info">
                        <div class="payment-title">${escapeHtml(payment.title)}</div>
                        <div class="payment-meta">
                            <span>${formatDate(payment.due_date)}</span>
                            ${payment.is_recurring ? '<span>🔄 Tekrarlayan</span>' : ''}
                        </div>
                    </div>
                    <div class="payment-amount">
                        <div class="payment-amount-value">${formatMoney(payment.amount, payment.currency)}</div>
                        <span class="payment-status ${payment.status}">${getStatusLabel(payment.status)}</span>
                    </div>
                    <div class="payment-actions">
                        ${payment.status !== 'paid' ? `
                            <button class="btn-action" onclick="markPaymentPaid(${payment.id})" title="Ödendi olarak işaretle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </button>
                        ` : ''}
                        <button class="btn-action" onclick="editPayment(${payment.id})" title="Düzenle">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button class="btn-action delete" onclick="deletePayment(${payment.id})" title="Sil">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                    <p>Henüz ödeme eklenmemiş</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Payments error:', error);
        container.innerHTML = '<div class="empty-state"><p>Ödemeler yüklenemedi</p></div>';
    }
}

function getCategoryIcon(category) {
    const icons = {
        'fatura': '📄',
        'kira': '🏠',
        'kredi': '💳',
        'abonelik': '📺',
        'diger': '💰'
    };
    return icons[category] || '💰';
}

function getStatusLabel(status) {
    const labels = {
        'pending': 'Bekliyor',
        'paid': 'Ödendi',
        'overdue': 'Gecikmiş'
    };
    return labels[status] || status;
}

async function markPaymentPaid(id) {
    try {
        const response = await fetch('api/payments.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify({ id, status: 'paid' })
        });

        if (response.ok) {
            showToast('Ödeme tamamlandı olarak işaretlendi', 'success');
            loadPayments();
            loadStats();
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

async function editPayment(id) {
    try {
        const response = await fetch(`api/payments.php?id=${id}`);
        const payment = await response.json();

        document.getElementById('payment-id').value = payment.id;
        document.getElementById('payment-title').value = payment.title;
        document.getElementById('payment-amount').value = payment.amount;
        document.getElementById('payment-currency').value = payment.currency;
        document.getElementById('payment-due-date').value = payment.due_date;
        document.getElementById('payment-category').value = payment.category;
        document.getElementById('payment-recurring').checked = payment.is_recurring == 1;
        document.getElementById('payment-recurring-period').value = payment.recurring_period || 'monthly';
        document.getElementById('payment-notes').value = payment.notes || '';

        toggleRecurringOptions();
        openModal('payment');
    } catch (error) {
        showToast('Ödeme bilgileri yüklenemedi', 'error');
    }
}

async function deletePayment(id) {
    if (!confirm('Bu ödemeyi silmek istediğinizden emin misiniz?')) return;

    try {
        const response = await fetch(`api/payments.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-Token': CSRF_TOKEN }
        });

        if (response.ok) {
            showToast('Ödeme silindi', 'success');
            loadPayments();
            loadStats();
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

async function savePayment(e) {
    e.preventDefault();

    const form = document.getElementById('payment-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.is_recurring = document.getElementById('payment-recurring').checked;

    try {
        const isEdit = data.id && data.id !== '';
        const response = await fetch('api/payments.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            showToast(isEdit ? 'Ödeme güncellendi' : 'Ödeme eklendi', 'success');
            closeModal();
            loadPayments();
            loadStats();
            form.reset();
        } else {
            const error = await response.json();
            showToast(error.error || 'Bir hata oluştu', 'error');
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

function toggleRecurringOptions() {
    const checkbox = document.getElementById('payment-recurring');
    const options = document.getElementById('recurring-options');
    options.style.display = checkbox.checked ? 'block' : 'none';
}

// ===== Takvim Etkinlikleri =====
async function loadEvents() {
    const container = document.getElementById('events-list');

    try {
        const response = await fetch('api/calendar.php?upcoming=1');
        const result = await response.json();

        if (result.events && result.events.length > 0) {
            container.innerHTML = result.events.slice(0, 5).map(event => {
                const date = new Date(event.event_date);
                return `
                    <div class="event-item" style="border-left-color: ${event.color}" data-id="${event.id}">
                        <div class="event-date">
                            <span class="event-date-day">${date.getDate()}</span>
                            <span class="event-date-month">${MONTHS_TR[date.getMonth()].substring(0, 3)}</span>
                        </div>
                        <div class="event-info">
                            <div class="event-title">${getEventTypeIcon(event.event_type)} ${escapeHtml(event.title)}</div>
                            <div class="event-time">${event.event_time ? event.event_time.substring(0, 5) : 'Tüm gün'}</div>
                        </div>
                        <div class="event-actions">
                            <button class="btn-action" onclick="editEvent(${event.id})" title="Düzenle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button class="btn-action delete" onclick="deleteEvent(${event.id})" title="Sil">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                    </svg>
                    <p>Yaklaşan etkinlik yok</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Events error:', error);
        container.innerHTML = '<div class="empty-state"><p>Etkinlikler yüklenemedi</p></div>';
    }
}

function getEventTypeIcon(type) {
    const icons = {
        'birthday': '🎂',
        'anniversary': '💍',
        'meeting': '📅',
        'reminder': '⏰',
        'holiday': '🎉',
        'other': '📌'
    };
    return icons[type] || '📌';
}

async function editEvent(id) {
    try {
        const response = await fetch(`api/calendar.php?id=${id}`);
        const event = await response.json();

        document.getElementById('event-id').value = event.id;
        document.getElementById('event-title').value = event.title;
        document.getElementById('event-date').value = event.event_date;
        document.getElementById('event-time').value = event.event_time || '';
        document.getElementById('event-type').value = event.event_type;
        document.getElementById('event-description').value = event.description || '';
        document.getElementById('event-recurring').checked = event.is_recurring == 1;

        // Renk seçimi
        document.querySelectorAll('#calendar-modal .color-option input').forEach(input => {
            input.checked = input.value === event.color;
        });

        openModal('calendar');
    } catch (error) {
        showToast('Etkinlik bilgileri yüklenemedi', 'error');
    }
}

async function deleteEvent(id) {
    if (!confirm('Bu etkinliği silmek istediğinizden emin misiniz?')) return;

    try {
        const response = await fetch(`api/calendar.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-Token': CSRF_TOKEN }
        });

        if (response.ok) {
            showToast('Etkinlik silindi', 'success');
            loadEvents();
            loadStats();
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

async function saveEvent(e) {
    e.preventDefault();

    const form = document.getElementById('calendar-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.is_recurring = document.getElementById('event-recurring').checked;

    try {
        const isEdit = data.id && data.id !== '';
        const response = await fetch('api/calendar.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            showToast(isEdit ? 'Etkinlik güncellendi' : 'Etkinlik eklendi', 'success');
            closeModal();
            loadEvents();
            loadStats();
            form.reset();
        } else {
            const error = await response.json();
            showToast(error.error || 'Bir hata oluştu', 'error');
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

// ===== Yapılacaklar =====
async function loadTodos() {
    const container = document.getElementById('todos-list');

    try {
        const response = await fetch('api/todos.php');
        const result = await response.json();

        if (result.todos && result.todos.length > 0) {
            container.innerHTML = result.todos.filter(t => t.status !== 'completed').slice(0, 6).map(todo => `
                <div class="todo-item ${todo.status}" data-id="${todo.id}">
                    <div class="todo-checkbox ${todo.status === 'completed' ? 'checked' : ''}" onclick="toggleTodo(${todo.id}, '${todo.status}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <div class="todo-info">
                        <div class="todo-title">${escapeHtml(todo.title)}</div>
                        <div class="todo-meta">
                            ${todo.due_date ? `<span>📅 ${formatDate(todo.due_date)}</span>` : ''}
                            ${todo.category ? `<span>🏷️ ${escapeHtml(todo.category)}</span>` : ''}
                        </div>
                    </div>
                    <div class="todo-priority ${todo.priority}" title="${getPriorityLabel(todo.priority)}"></div>
                    <div class="todo-actions">
                        <button class="btn-action" onclick="editTodo(${todo.id})" title="Düzenle">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button class="btn-action delete" onclick="deleteTodo(${todo.id})" title="Sil">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    <p>Henüz görev eklenmemiş</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Todos error:', error);
        container.innerHTML = '<div class="empty-state"><p>Görevler yüklenemedi</p></div>';
    }
}

function getPriorityLabel(priority) {
    const labels = { 'low': 'Düşük', 'medium': 'Orta', 'high': 'Yüksek' };
    return labels[priority] || priority;
}

async function toggleTodo(id, currentStatus) {
    const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';

    try {
        const response = await fetch('api/todos.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify({ id, status: newStatus })
        });

        if (response.ok) {
            loadTodos();
            loadStats();
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

async function editTodo(id) {
    try {
        const response = await fetch(`api/todos.php?id=${id}`);
        const todo = await response.json();

        document.getElementById('todo-id').value = todo.id;
        document.getElementById('todo-title').value = todo.title;
        document.getElementById('todo-priority').value = todo.priority;
        document.getElementById('todo-due-date').value = todo.due_date || '';
        document.getElementById('todo-category').value = todo.category || '';
        document.getElementById('todo-description').value = todo.description || '';

        openModal('todo');
    } catch (error) {
        showToast('Görev bilgileri yüklenemedi', 'error');
    }
}

async function deleteTodo(id) {
    if (!confirm('Bu görevi silmek istediğinizden emin misiniz?')) return;

    try {
        const response = await fetch(`api/todos.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-Token': CSRF_TOKEN }
        });

        if (response.ok) {
            showToast('Görev silindi', 'success');
            loadTodos();
            loadStats();
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

async function saveTodo(e) {
    e.preventDefault();

    const form = document.getElementById('todo-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const isEdit = data.id && data.id !== '';
        const response = await fetch('api/todos.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            showToast(isEdit ? 'Görev güncellendi' : 'Görev eklendi', 'success');
            closeModal();
            loadTodos();
            loadStats();
            form.reset();
        } else {
            const error = await response.json();
            showToast(error.error || 'Bir hata oluştu', 'error');
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

// ===== Notlar =====
async function loadNotes() {
    const container = document.getElementById('notes-list');

    try {
        const response = await fetch('api/notes.php');
        const result = await response.json();

        if (result.notes && result.notes.length > 0) {
            container.innerHTML = result.notes.slice(0, 4).map(note => `
                <div class="note-card ${note.is_pinned == 1 ? 'pinned' : ''}" style="background: ${hexToRgba(note.color, 0.1)}; border-color: ${hexToRgba(note.color, 0.2)};" data-id="${note.id}" onclick="editNote(${note.id})">
                    <div class="note-title">${escapeHtml(note.title)}</div>
                    <div class="note-content">${escapeHtml(note.content || '')}</div>
                    <div class="note-date">${formatDateTime(note.updated_at)}</div>
                    <div class="note-actions" onclick="event.stopPropagation()">
                        <button class="btn-action delete" onclick="deleteNote(${note.id})" title="Sil">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <p>Henüz not eklenmemiş</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Notes error:', error);
        container.innerHTML = '<div class="empty-state" style="grid-column: 1 / -1;"><p>Notlar yüklenemedi</p></div>';
    }
}

async function editNote(id) {
    try {
        const response = await fetch(`api/notes.php?id=${id}`);
        const note = await response.json();

        document.getElementById('note-id').value = note.id;
        document.getElementById('note-title').value = note.title;
        document.getElementById('note-content').value = note.content || '';
        document.getElementById('note-pinned').checked = note.is_pinned == 1;

        // Renk seçimi
        document.querySelectorAll('#note-modal .color-option input').forEach(input => {
            input.checked = input.value === note.color;
        });

        openModal('note');
    } catch (error) {
        showToast('Not bilgileri yüklenemedi', 'error');
    }
}

async function deleteNote(id) {
    if (!confirm('Bu notu silmek istediğinizden emin misiniz?')) return;

    try {
        const response = await fetch(`api/notes.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-Token': CSRF_TOKEN }
        });

        if (response.ok) {
            showToast('Not silindi', 'success');
            loadNotes();
            loadStats();
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

async function saveNote(e) {
    e.preventDefault();

    const form = document.getElementById('note-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    data.is_pinned = document.getElementById('note-pinned').checked;

    try {
        const isEdit = data.id && data.id !== '';
        const response = await fetch('api/notes.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            showToast(isEdit ? 'Not güncellendi' : 'Not eklendi', 'success');
            closeModal();
            loadNotes();
            loadStats();
            form.reset();
        } else {
            const error = await response.json();
            showToast(error.error || 'Bir hata oluştu', 'error');
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

// ===== İstatistikler =====
async function loadStats() {
    try {
        const [paymentsRes, eventsRes, todosRes, notesRes] = await Promise.all([
            fetch('api/payments.php'),
            fetch('api/calendar.php?upcoming=1'),
            fetch('api/todos.php'),
            fetch('api/notes.php')
        ]);

        const payments = await paymentsRes.json();
        const events = await eventsRes.json();
        const todos = await todosRes.json();
        const notes = await notesRes.json();

        document.getElementById('pending-payments').textContent =
            (payments.stats?.pending_count || 0) + (payments.stats?.overdue_count || 0);

        document.getElementById('upcoming-events').textContent =
            events.stats?.this_week || 0;

        document.getElementById('pending-todos').textContent =
            (todos.stats?.pending || 0) + (todos.stats?.in_progress || 0);

        document.getElementById('total-notes').textContent =
            notes.stats?.total || 0;

    } catch (error) {
        console.error('Stats error:', error);
    }
}

// ===== Ayarlar =====
function openSettings() {
    openModal('settings');
}

async function saveSettings(e) {
    e.preventDefault();

    const form = document.getElementById('settings-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('api/settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            showToast('Ayarlar kaydedildi', 'success');
            closeModal();

            // Şifre alanlarını temizle
            document.getElementById('settings-current-password').value = '';
            document.getElementById('settings-new-password').value = '';

            // Hava durumunu yeniden yükle
            loadWeather();
        } else {
            showToast(result.error || 'Bir hata oluştu', 'error');
        }
    } catch (error) {
        showToast('Bir hata oluştu', 'error');
    }
}

// ===== Modal İşlemleri =====
function openModal(type) {
    const modal = document.getElementById(`${type}-modal`);
    const overlay = document.getElementById('modal-overlay');

    // Form sıfırla (edit değilse)
    const form = modal.querySelector('form');
    if (form && !form.querySelector('input[name="id"]').value) {
        form.reset();
    }

    // Varsayılan tarih ayarla
    if (type === 'payment') {
        const dueDateInput = document.getElementById('payment-due-date');
        if (!dueDateInput.value) {
            dueDateInput.value = new Date().toISOString().split('T')[0];
        }
    } else if (type === 'calendar') {
        const eventDateInput = document.getElementById('event-date');
        if (!eventDateInput.value) {
            eventDateInput.value = new Date().toISOString().split('T')[0];
        }
    }

    overlay.classList.add('active');
    modal.classList.add('active');
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            const idInput = form.querySelector('input[name="id"]');
            if (idInput) idInput.value = '';
        }
    });
    document.getElementById('modal-overlay').classList.remove('active');
}

// ===== Sidebar Toggle =====
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// ===== Event Listeners =====
function setupEventListeners() {
    // Recurring checkbox
    document.getElementById('payment-recurring').addEventListener('change', toggleRecurringOptions);

    // Escape tuşu ile modal kapat
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    // Navigasyon
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        });
    });
}

// ===== Yardımcı Fonksiyonlar =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function formatMoney(amount, currency = 'TRY') {
    const symbols = { 'TRY': '₺', 'USD': '$', 'EUR': '€' };
    const symbol = symbols[currency] || currency;
    return symbol + parseFloat(amount).toLocaleString('tr-TR', { minimumFractionDigits: 2 });
}

function hexToRgba(hex, alpha) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

// ===== Toast Bildirimleri =====
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;">
            ${type === 'success'
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'
                : type === 'error'
                ? '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
                : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
            }
        </svg>
        <span>${escapeHtml(message)}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
