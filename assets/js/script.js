/**
 * Редактирование встречи: заполняет модальное окно данными из data-атрибутов
 * @param {HTMLElement} btn - кнопка "Редактировать"
 */
function editMeeting(btn) {
    const id = btn.dataset.id;
    const date = btn.dataset.date;
    const time = btn.dataset.time;
    const address = btn.dataset.address;
    const description = btn.dataset.description;

    // Устанавливаем значения формы
    document.getElementById('formAction').value = 'edit';
    document.getElementById('meetingId').value = id;
    document.getElementById('modalTitle').textContent = 'Редактировать встречу';

    document.getElementById('meetingDate').value = date;
    document.getElementById('meetingTime').value = time;
    document.getElementById('meetingAddress').value = address;
    document.getElementById('meetingDescription').value = description || '';
}

/**
 * Удаление встречи с подтверждением
 * @param {number} id - ID встречи
 */
function deleteMeeting(id) {
    if (confirm('Вы уверены, что хотите удалить эту встречу?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация календаря для выбора даты (навигация)
    const datePicker = document.getElementById('datePicker');
    if (datePicker) {
        flatpickr(datePicker, {
            locale: "ru",
            dateFormat: "d.m.Y",
            defaultDate: document.querySelector('.calendar-day.active')?.innerText || new Date(),
            onChange: function(selectedDates, dateStr) {
                window.location.href = '?date=' + dateStr;
            }
        });
    }

    // Инициализация календаря в модальном окне
    const meetingDate = document.getElementById('meetingDate');
    if (meetingDate) {
        flatpickr(meetingDate, {
            locale: "ru",
            dateFormat: "d.m.Y",
            defaultDate: new Date()
        });
    }

    // Сброс формы при закрытии модального окна
    const modal = document.getElementById('meetingModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('meetingForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('modalTitle').textContent = 'Новая встреча';
            document.getElementById('meetingId').value = '';
        });
    }
});