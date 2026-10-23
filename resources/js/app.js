import './bootstrap';
import axios from 'axios';
import Chart from 'chart.js/auto';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Chart = Chart;

const customSelectRoots = () => [...document.querySelectorAll('[data-custom-select]')];

const closeCustomSelects = (except = null) => {
    customSelectRoots().forEach((root) => {
        if (root !== except) {
            root._closeCustomSelect?.();
        }
    });
};

const initializeCustomSelects = (scope = document) => {
    const roots = [];

    if (scope instanceof Element && scope.matches('[data-custom-select]')) {
        roots.push(scope);
    }

    if (scope.querySelectorAll) {
        roots.push(...scope.querySelectorAll('[data-custom-select]'));
    }

    roots.forEach((root) => {
        if (root.dataset.customSelectInitialized === 'true') {
            return;
        }

        const nativeSelect = root.querySelector('select');
        const trigger = root.querySelector('[data-select-trigger]');
        const label = root.querySelector('[data-select-label]');
        const chevron = root.querySelector('[data-select-chevron]');
        const menu = root.querySelector('[data-select-menu]');

        if (!nativeSelect || !trigger || !label || !menu) {
            return;
        }

        let activeIndex = nativeSelect.selectedIndex > -1 ? nativeSelect.selectedIndex : 0;
        let isOpen = false;
        let closeTimeout;

        const optionElements = () => [...menu.querySelectorAll('[role="option"]')];

        const CHECK_SVG = '<svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true"><path d="m4.5 10.5 3.5 3.5 7.5-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>';

        const paintOption = (option, { selected, active }) => {
            option.classList.toggle('bg-bnn-50', selected || active);
            option.classList.toggle('font-semibold', selected);
            option.classList.toggle('font-medium', !selected && active);
            option.classList.toggle('text-bnn-800', selected || active);
            option.classList.toggle('text-slate-700', !selected && !active);
            const check = option.querySelector('[data-check]');
            check?.classList.toggle('hidden', !selected);
            check?.classList.toggle('inline-flex', selected);
        };

        const updateActiveOption = () => {
            optionElements().forEach((option, index) => {
                paintOption(option, {
                    selected: option.getAttribute('aria-selected') === 'true',
                    active: index === activeIndex,
                });
            });

            const activeOption = optionElements()[activeIndex];
            trigger.setAttribute('aria-activedescendant', activeOption?.id ?? '');
        };

        const updateSelectedOption = () => {
            const selected = nativeSelect.options[nativeSelect.selectedIndex];
            const selectedValue = nativeSelect.value;

            label.textContent = selected?.textContent ?? '';
            label.classList.toggle('text-slate-400', selectedValue === '');

            optionElements().forEach((option, index) => {
                const selectedOption = option.dataset.value === selectedValue;
                option.setAttribute('aria-selected', selectedOption ? 'true' : 'false');
                paintOption(option, { selected: selectedOption, active: index === activeIndex });
            });
        };

        const renderOptions = () => {
            const rootId = nativeSelect.id || `${nativeSelect.name.replace(/[^a-z0-9_-]+/gi, '-')}-select`;
            menu.innerHTML = '';

            [...nativeSelect.options].forEach((nativeOption, index) => {
                const option = document.createElement('div');
                option.id = `${rootId}-option-${index}`;
                option.dataset.value = nativeOption.value;
                option.setAttribute('role', 'option');
                option.setAttribute('tabindex', '-1');
                option.setAttribute('aria-selected', nativeOption.value === nativeSelect.value ? 'true' : 'false');
                option.className = 'flex cursor-pointer items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm outline-none transition hover:bg-bnn-50 hover:text-bnn-800 focus:bg-bnn-50 focus:text-bnn-800';
                const text = document.createElement('span');
                text.className = 'truncate';
                text.textContent = nativeOption.textContent;
                const check = document.createElement('span');
                check.setAttribute('data-check', '');
                const isSelected = nativeOption.value === nativeSelect.value;
                check.className = `${isSelected ? 'inline-flex' : 'hidden'} shrink-0 text-bnn-600`;
                check.innerHTML = CHECK_SVG;
                option.append(text, check);
                option.addEventListener('click', () => selectValue(nativeOption.value));
                menu.appendChild(option);
            });

            activeIndex = Math.max(0, nativeSelect.selectedIndex);
            updateSelectedOption();
            updateActiveOption();
        };

        const close = (restoreFocus = false) => {
            isOpen = false;
            clearTimeout(closeTimeout);
            trigger.setAttribute('aria-expanded', 'false');
            menu.classList.remove('visible', 'opacity-100', 'translate-y-0', 'pointer-events-auto');
            menu.classList.add('opacity-0', '-translate-y-1', 'pointer-events-none');
            chevron?.classList.remove('rotate-180');
            closeTimeout = setTimeout(() => menu.classList.add('invisible'), 200);

            if (restoreFocus) {
                trigger.focus();
            }
        };

        const open = () => {
            if (nativeSelect.disabled) {
                return;
            }

            closeCustomSelects(root);
            isOpen = true;
            clearTimeout(closeTimeout);
            trigger.setAttribute('aria-expanded', 'true');
            menu.classList.remove('invisible', 'opacity-0', '-translate-y-1', 'pointer-events-none');
            menu.classList.add('visible', 'opacity-100', 'translate-y-0', 'pointer-events-auto');
            chevron?.classList.add('rotate-180');
            updateActiveOption();
        };

        const selectValue = (value) => {
            nativeSelect.value = value;
            activeIndex = Math.max(0, nativeSelect.selectedIndex);
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            close(true);
        };

        const moveActive = (step) => {
            const length = optionElements().length;

            if (!length) {
                return;
            }

            activeIndex = (activeIndex + step + length) % length;
            updateActiveOption();
        };

        trigger.addEventListener('click', () => (isOpen ? close() : open()));
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();

                if (isOpen) {
                    const activeOption = optionElements()[activeIndex];
                    if (activeOption) {
                        selectValue(activeOption.dataset.value ?? '');
                    }
                } else {
                    open();
                }
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (!isOpen) {
                    open();
                }
                moveActive(1);
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (!isOpen) {
                    open();
                }
                moveActive(-1);
            }

            if (event.key === 'Home' && isOpen) {
                event.preventDefault();
                activeIndex = 0;
                updateActiveOption();
            }

            if (event.key === 'End' && isOpen) {
                event.preventDefault();
                activeIndex = Math.max(0, optionElements().length - 1);
                updateActiveOption();
            }

            if (event.key === 'Escape' && isOpen) {
                event.preventDefault();
                close(true);
            }
        });

        nativeSelect.addEventListener('change', () => {
            renderOptions();
        });

        root._closeCustomSelect = close;
        root.dataset.customSelectInitialized = 'true';
        renderOptions();
    });
};

window.initializeCustomSelects = initializeCustomSelects;

const initializeBnnkCreation = () => {
    const form = document.querySelector('[data-bnnk-create]');
    const panel = document.querySelector('[data-bnnk-create-panel]');
    const toggle = document.querySelector('[data-bnnk-create-toggle]');
    const input = form?.querySelector('[data-bnnk-name]');
    const submit = form?.querySelector('[data-bnnk-submit]');
    const cancel = form?.querySelector('[data-bnnk-cancel]');
    const error = form?.querySelector('[data-bnnk-error]');
    const selectRoot = document.querySelector('[data-bnnk-select]');

    if (!form || !panel || !toggle || !input || !submit || !selectRoot) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isHidden = panel.classList.toggle('hidden');
        toggle.setAttribute('aria-expanded', String(!isHidden));

        if (!isHidden) {
            input.focus();
        }
    });

    cancel?.addEventListener('click', () => {
        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
        input.value = '';
        error.textContent = '';
        error.classList.add('hidden');
    });

    form.addEventListener('click', async (event) => {
        if (!event.target.matches('[data-bnnk-submit]')) {
            return;
        }

        event.preventDefault();
        error.textContent = '';
        error.classList.add('hidden');
        submit.disabled = true;

        const body = new FormData();
        body.append('name', input.value);

        try {
            const response = await fetch(form.dataset.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await response.json();

            if (!response.ok) {
                const messages = Object.values(data.errors ?? {}).flat();
                throw new Error(messages[0] ?? data.message ?? 'BNNK belum dapat ditambahkan.');
            }

            const nativeSelect = selectRoot.querySelector('select');
            const option = new Option(data.bnnk.name, data.bnnk.id, true, true);
            nativeSelect.add(option);
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            input.value = '';
            panel.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        } catch (requestError) {
            error.textContent = requestError.message;
            error.classList.remove('hidden');
        } finally {
            submit.disabled = false;
        }
    });
};

const initializeDashboardCharts = () => {
    const canvases = [...document.querySelectorAll('[data-dashboard-chart] canvas[data-chart-payload]')];

    if (!canvases.length) {
        return;
    }

    if (typeof Chart === 'undefined') {
        canvases.forEach((canvas) => {
            canvas.closest('[data-dashboard-chart]')?.querySelector('.relative')?.insertAdjacentHTML('afterend', '<p class="mt-3 text-sm text-slate-500">Grafik tidak dapat dimuat karena pustaka chart tidak tersedia. Periksa koneksi lalu muat ulang halaman.</p>');
        });
        return;
    }

    const ink = '#0f172a';
    const muted = '#64748b';
    const grid = '#e2e8f0';

    Chart.defaults.font.family = 'inherit';
    Chart.defaults.color = muted;

    const tooltipStyle = () => ({
        backgroundColor: '#0f172a',
        titleColor: '#ffffff',
        bodyColor: '#e2e8f0',
        padding: 12,
        cornerRadius: 10,
        displayColors: true,
        boxPadding: 4,
    });

    canvases.forEach((canvas) => {
        if (canvas.dataset.chartInitialized === 'true') {
            return;
        }
        canvas.dataset.chartInitialized = 'true';

        const payload = JSON.parse(canvas.dataset.chartPayload || '{}');
        const host = canvas.closest('[data-dashboard-chart]');
        const legendHost = host ? host.querySelector('[data-chart-legend]') : null;
        const compactNumbers = payload.type !== 'doughnut';

        const chart = new Chart(canvas, {
            type: payload.type === 'doughnut' ? 'doughnut' : 'bar',
            data: {
                labels: payload.labels || [],
                datasets: (payload.datasets || []).map((dataset) => Object.assign({}, dataset, {
                    borderRadius: payload.type === 'doughnut' ? 0 : 6,
                    borderSkipped: payload.type === 'doughnut' ? false : 'start',
                    borderWidth: payload.type === 'doughnut' ? 2 : 0,
                    borderColor: payload.type === 'doughnut' ? '#ffffff' : dataset.borderColor,
                    maxBarThickness: 26,
                    categoryPercentage: 0.62,
                    barPercentage: 0.9,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign(tooltipStyle(), {
                        callbacks: {
                            label: (item) => {
                                const value = Number(item.raw || 0).toLocaleString('id-ID');
                                return compactNumbers && item.dataset.label
                                    ? ` ${item.dataset.label}: ${value}`
                                    : ` ${item.label || ''}: ${value}`;
                            },
                        },
                    }),
                },
                scales: payload.type === 'doughnut' ? {} : {
                    x: { grid: { display: false }, ticks: { color: muted, maxRotation: 30 } },
                    y: { beginAtZero: true, grid: { color: grid }, ticks: { color: muted, precision: 0 } },
                },
                cutout: payload.type === 'doughnut' ? '62%' : undefined,
            },
        });

        if (legendHost) {
            legendHost.innerHTML = '';
            const legendEntries = [];
            if (payload.type === 'doughnut') {
                const doughnutColors = chart.data.datasets[0] ? chart.data.datasets[0].backgroundColor : [];
                (chart.data.labels || []).forEach((label, labelIndex) => {
                    legendEntries.push({
                        text: label,
                        color: (Array.isArray(doughnutColors) ? doughnutColors[labelIndex] : doughnutColors) || '#0984E2',
                        isVisible: () => chart.getDataVisibility(labelIndex),
                        toggle: () => chart.toggleDataVisibility(labelIndex),
                    });
                });
            } else {
                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    legendEntries.push({
                        text: dataset.label || (`Seri ${datasetIndex + 1}`),
                        color: Array.isArray(dataset.backgroundColor) ? dataset.backgroundColor[0] : dataset.backgroundColor,
                        isVisible: () => chart.isDatasetVisible(datasetIndex),
                        toggle: () => chart.setDatasetVisibility(datasetIndex, !chart.isDatasetVisible(datasetIndex)),
                    });
                });
            }

            legendEntries.forEach((entry) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300';
                item.setAttribute('aria-pressed', 'true');

                const swatch = document.createElement('span');
                swatch.style.cssText = `width:12px;height:12px;border-radius:9999px;background:${entry.color || '#0984E2'};flex:none;`;
                const caption = document.createElement('span');
                caption.textContent = entry.text;
                item.append(swatch, caption);

                item.addEventListener('click', () => {
                    const visible = entry.isVisible();
                    entry.toggle();
                    chart.update();
                    item.setAttribute('aria-pressed', String(!visible));
                    item.classList.toggle('opacity-50', visible);
                });

                legendHost.append(item);
            });
        }
    });
};

// Placeholder strip (—, -, –) tanda data kosong harus rata tengah di semua tabel,
// walau alignment bawaan kolomnya rata kiri/kanan. Dicek per sel: hanya sel yang
// seluruh isinya strip yang di-center, sel berisi teks lain tidak tersentuh.
const centerEmptyPlaceholders = (scope = document) => {
    if (!scope.querySelectorAll) {
        return;
    }

    scope.querySelectorAll('table td').forEach((cell) => {
        const text = cell.textContent.trim();
        if ((text === '—' || text === '-' || text === '–') && !cell.classList.contains('text-center')) {
            cell.classList.add('text-center');
        }
    });
};

const initializeApplication = () => {
    centerEmptyPlaceholders();
    initializeCustomSelects();
    initializeBnnkCreation();
    initializeDashboardCharts();

    const sidebar = document.querySelector('#app-sidebar');
    const backdrop = document.querySelector('#sidebar-backdrop');
    const openSidebar = () => { sidebar?.classList.remove('-translate-x-full'); backdrop?.classList.remove('hidden'); };
    const closeSidebar = () => { sidebar?.classList.add('-translate-x-full'); backdrop?.classList.add('hidden'); };
    document.querySelectorAll('[data-sidebar-open]').forEach((button) => button.addEventListener('click', openSidebar));
    document.querySelectorAll('[data-sidebar-close]').forEach((button) => button.addEventListener('click', closeSidebar));

    const accountToggle = document.querySelector('[data-account-toggle]');
    const accountMenu = document.querySelector('[data-account-menu]');
    const closeAccount = () => { accountMenu?.classList.add('hidden'); accountToggle?.setAttribute('aria-expanded', 'false'); };
    accountToggle?.addEventListener('click', (event) => { event.stopPropagation(); const isHidden = accountMenu?.classList.toggle('hidden'); accountToggle.setAttribute('aria-expanded', String(isHidden === false)); });

    document.addEventListener('click', (event) => {
        if (accountMenu && !accountMenu.contains(event.target) && !accountToggle?.contains(event.target)) {
            closeAccount();
        }

        if (!event.target.closest('[data-custom-select]')) {
            closeCustomSelects();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
            closeAccount();
            closeCustomSelects();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApplication);
} else {
    initializeApplication();
}
