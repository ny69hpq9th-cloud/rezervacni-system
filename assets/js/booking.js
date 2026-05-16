/* ============================================================
   BOOKING WIZARD
   ============================================================ */
const Booking = (() => {
  let state = {
    step: 1,
    serviceId: null,
    serviceName: '',
    servicePrice: '',
    serviceDuration: '',
    date: null,
    time: null,
    slug: '',
    calYear: 0,
    calMonth: 0,
  };

  // Init
  function init() {
    state.slug = document.body.dataset.slug || '';
    const now = new Date();
    state.calYear  = now.getFullYear();
    state.calMonth = now.getMonth(); // 0-based

    // Service click
    document.querySelectorAll('.service-item').forEach(el => {
      el.addEventListener('click', () => selectService(el));
    });

    // Calendar nav
    document.getElementById('cal-prev')?.addEventListener('click', () => {
      state.calMonth--;
      if (state.calMonth < 0) { state.calMonth = 11; state.calYear--; }
      renderCalendar();
    });
    document.getElementById('cal-next')?.addEventListener('click', () => {
      state.calMonth++;
      if (state.calMonth > 11) { state.calMonth = 0; state.calYear++; }
      renderCalendar();
    });

    // Buttons
    document.getElementById('btn-next-1')?.addEventListener('click', () => goStep(2));
    document.getElementById('btn-back-2')?.addEventListener('click', () => goStep(1));
    document.getElementById('btn-next-2')?.addEventListener('click', () => goStep(3));
    document.getElementById('btn-back-3')?.addEventListener('click', () => goStep(2));
    document.getElementById('btn-submit')?.addEventListener('click', submitBooking);
  }

  function selectService(el) {
    document.querySelectorAll('.service-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    state.serviceId       = el.dataset.id;
    state.serviceName     = el.dataset.name;
    state.servicePrice    = el.dataset.price;
    state.serviceDuration = el.dataset.duration;
    document.getElementById('btn-next-1').disabled = false;
  }

  function goStep(n) {
    if (n === 2 && !state.serviceId) {
      alert('Vyberte prosím službu.');
      return;
    }
    if (n === 3) {
      if (!state.date || !state.time) {
        alert('Vyberte prosím datum a čas.');
        return;
      }
      fillSummary();
    }

    state.step = n;
    document.querySelectorAll('.booking-panel').forEach((p, i) => {
      p.classList.toggle('active', i + 1 === n);
    });
    document.querySelectorAll('.booking-step').forEach((s, i) => {
      s.classList.remove('active', 'done');
      if (i + 1 < n) s.classList.add('done');
      if (i + 1 === n) s.classList.add('active');
    });

    if (n === 2) renderCalendar();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /* ---- Calendar ---- */
  function renderCalendar() {
    const container = document.getElementById('cal-grid-body');
    const title     = document.getElementById('cal-month-title');
    if (!container || !title) return;

    const months = ['Leden','Únor','Březen','Duben','Květen','Červen',
                    'Červenec','Srpen','Září','Říjen','Listopad','Prosinec'];
    title.textContent = months[state.calMonth] + ' ' + state.calYear;

    const today    = new Date();
    today.setHours(0,0,0,0);
    const firstDay = new Date(state.calYear, state.calMonth, 1);
    const lastDay  = new Date(state.calYear, state.calMonth + 1, 0);
    // Monday-based start: JS getDay() 0=Sun
    let startDow = firstDay.getDay(); // 0=Sun
    startDow = (startDow === 0) ? 6 : startDow - 1; // convert to Mon=0

    container.innerHTML = '';
    let row = document.createElement('tr');

    // Leading empty cells
    for (let i = 0; i < startDow; i++) {
      row.appendChild(emptyCell());
    }

    for (let d = 1; d <= lastDay.getDate(); d++) {
      const date    = new Date(state.calYear, state.calMonth, d);
      const dateStr = formatDateStr(date);
      const isPast  = date < today;
      const isToday = date.toDateString() === today.toDateString();
      const isSel   = dateStr === state.date;

      const btn = document.createElement('button');
      btn.className = 'mini-cal__day';
      if (isToday) btn.classList.add('today');
      if (isSel)   btn.classList.add('selected');
      if (isPast)  { btn.disabled = true; }
      btn.textContent = d;

      if (!isPast) {
        btn.addEventListener('click', () => selectDate(dateStr, btn));
      }

      const td = document.createElement('td');
      td.appendChild(btn);
      row.appendChild(td);

      const totalCells = startDow + d;
      if (totalCells % 7 === 0 && d < lastDay.getDate()) {
        container.appendChild(row);
        row = document.createElement('tr');
      }
    }

    // Trailing cells
    const totalCells = startDow + lastDay.getDate();
    const rem = totalCells % 7;
    if (rem !== 0) {
      for (let i = rem; i < 7; i++) row.appendChild(emptyCell());
    }
    container.appendChild(row);
  }

  function emptyCell() {
    const td = document.createElement('td');
    td.innerHTML = '<span class="mini-cal__day other-month"></span>';
    return td;
  }

  function selectDate(dateStr, btn) {
    state.date = dateStr;
    state.time = null;
    document.querySelectorAll('.mini-cal__day.selected').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');

    const title = document.getElementById('time-slots-date');
    if (title) {
      const [y,m,d] = dateStr.split('-');
      title.textContent = d + '. ' + m + '. ' + y;
    }

    fetchTimes();
  }

  function fetchTimes() {
    const container = document.getElementById('time-slots-grid');
    if (!container) return;
    container.innerHTML = '<div class="time-slots__loading"><div class="spinner"></div></div>';

    fetch(`/api/get_times.php?slug=${encodeURIComponent(state.slug)}&service_id=${state.serviceId}&date=${state.date}`)
      .then(r => r.json())
      .then(data => {
        if (!data.times || data.times.length === 0) {
          container.innerHTML = '<div class="time-slots__empty">Žádné volné termíny pro tento den.</div>';
          return;
        }
        container.innerHTML = '';
        data.times.forEach(t => {
          const btn = document.createElement('button');
          btn.className = 'time-slot';
          btn.textContent = t;
          btn.addEventListener('click', () => selectTime(t, btn));
          container.appendChild(btn);
        });
      })
      .catch(() => {
        container.innerHTML = '<div class="time-slots__empty">Chyba při načítání termínů.</div>';
      });
  }

  function selectTime(time, btn) {
    state.time = time;
    document.querySelectorAll('.time-slot.selected').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('btn-next-2').disabled = false;
  }

  /* ---- Step 3 summary ---- */
  function fillSummary() {
    const [y,m,d] = state.date.split('-');
    const dateFormatted = d + '. ' + m + '. ' + y;

    setText('sum-service',  state.serviceName);
    setText('sum-date',     dateFormatted);
    setText('sum-time',     state.time);
    setText('sum-price',    state.servicePrice ? state.servicePrice + ' Kč' : 'Dle ceníku');
    setText('sum-duration', state.serviceDuration ? state.serviceDuration + ' min' : '');
  }

  function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }

  /* ---- Submit ---- */
  function submitBooking() {
    const name  = document.getElementById('f-name')?.value.trim();
    const email = document.getElementById('f-email')?.value.trim();
    const phone = document.getElementById('f-phone')?.value.trim();
    const notes = document.getElementById('f-notes')?.value.trim();
    const csrf  = document.getElementById('f-csrf')?.value;

    // Basic validation
    const errors = [];
    if (!name)  errors.push('Vyplňte prosím jméno a příjmení.');
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Zadejte platný email.');
    if (!phone) errors.push('Vyplňte prosím telefon.');

    if (errors.length) {
      alert(errors.join('\n'));
      return;
    }

    const submitBtn = document.getElementById('btn-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Odesílám...';

    fetch('/api/create_booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        slug:           state.slug,
        service_id:     state.serviceId,
        date:           state.date,
        time:           state.time,
        customer_name:  name,
        customer_email: email,
        customer_phone: phone,
        notes:          notes,
        csrf_token:     csrf,
      }),
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showConfirmation(name);
        } else {
          alert(data.error || 'Nastala chyba. Zkuste to prosím znovu.');
          submitBtn.disabled = false;
          submitBtn.textContent = 'Potvrdit rezervaci';
        }
      })
      .catch(() => {
        alert('Nastala chyba. Zkuste to prosím znovu.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Potvrdit rezervaci';
      });
  }

  function showConfirmation(name) {
    state.step = 4;
    document.querySelectorAll('.booking-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-4')?.classList.add('active');
    document.querySelectorAll('.booking-step').forEach(s => s.classList.add('done'));
    setText('conf-name', name);
    const [y,m,d] = state.date.split('-');
    setText('conf-service', state.serviceName);
    setText('conf-date', d + '. ' + m + '. ' + y);
    setText('conf-time', state.time);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function formatDateStr(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  return { init };
})();

document.addEventListener('DOMContentLoaded', Booking.init);
