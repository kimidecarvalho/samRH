let currentDate = new Date(2025, 1);

function generateCalendar(date) {
    const calendarDays = document.getElementById('calendar-days');
    const currentMonth = document.getElementById('currentMonth');
    calendarDays.innerHTML = '';

    const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    currentMonth.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;

    let emptyDaysAtStart = date.getMonth() === 1 && date.getFullYear() === 2025 ? 5 :
                            new Date(date.getFullYear(), date.getMonth(), 1).getDay();

    for (let i = 0; i < emptyDaysAtStart; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day empty';
        calendarDays.appendChild(emptyDay);
    }

    const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

    for (let i = 1; i <= lastDay; i++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';

        const currentDayOfWeek = (emptyDaysAtStart + i - 1) % 7;

        if (currentDayOfWeek === 5 || currentDayOfWeek === 6) {
            dayElement.className += ' weekend';
        } else {
            dayElement.className += ' active';
        }

        dayElement.textContent = i;
        calendarDays.appendChild(dayElement);
    }
}

// Month navigation event listeners
document.getElementById('prevMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    generateCalendar(currentDate);
});

document.getElementById('nextMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    generateCalendar(currentDate);
});

// Initial calendar generation
generateCalendar(currentDate);