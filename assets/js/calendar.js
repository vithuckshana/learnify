document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('calendar');
    if (!el) return;

    let events = [];
    try {
        events = JSON.parse(el.dataset.events || '[]');
    } catch (e) {
        events = [];
    }

    const today = new Date();
    let viewYear = today.getFullYear();
    let viewMonth = today.getMonth();

    const monthLabel = document.createElement('div');
    monthLabel.className = 'cal-header';
    const grid = document.createElement('div');
    grid.className = 'cal-grid';
    const nav = document.createElement('div');
    nav.className = 'cal-nav';

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'btn btn-sm btn-ghost';
    prevBtn.textContent = '←';
    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'btn btn-sm btn-ghost';
    nextBtn.textContent = '→';

    nav.append(prevBtn, monthLabel, nextBtn);
    el.append(nav, grid);

    const render = () => {
        monthLabel.textContent = new Date(viewYear, viewMonth).toLocaleString('default', { month: 'long', year: 'numeric' });
        grid.innerHTML = '';
        ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach((d) => {
            const h = document.createElement('div');
            h.className = 'cal-dow';
            h.textContent = d;
            grid.appendChild(h);
        });

        const first = new Date(viewYear, viewMonth, 1);
        const startDay = first.getDay();
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        for (let i = 0; i < startDay; i++) {
            grid.appendChild(document.createElement('div'));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'cal-day';
            const dateStr = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            cell.dataset.date = dateStr;

            const num = document.createElement('span');
            num.className = 'cal-num';
            num.textContent = day;
            cell.appendChild(num);

            const dayEvents = events.filter((e) => e.date === dateStr);
            if (dayEvents.length) {
                cell.classList.add('has-events');
                const dots = document.createElement('span');
                dots.className = 'cal-dots';
                dots.textContent = dayEvents.map((e) => e.time).join(' · ');
                cell.appendChild(dots);
                cell.title = dayEvents.map((e) => `${e.time} ${e.title}`).join('\n');
            }

            if (dateStr === today.toISOString().slice(0, 10)) {
                cell.classList.add('is-today');
            }

            cell.addEventListener('click', () => {
                grid.querySelectorAll('.cal-day').forEach((c) => c.classList.remove('is-selected'));
                cell.classList.add('is-selected');
                if (dayEvents.length) {
                    alert(dayEvents.map((e) => `${e.time} — ${e.title}`).join('\n'));
                }
            });

            grid.appendChild(cell);
        }
    };

    prevBtn.addEventListener('click', () => {
        viewMonth--;
        if (viewMonth < 0) { viewMonth = 11; viewYear--; }
        render();
    });
    nextBtn.addEventListener('click', () => {
        viewMonth++;
        if (viewMonth > 11) { viewMonth = 0; viewYear++; }
        render();
    });

    render();
});
