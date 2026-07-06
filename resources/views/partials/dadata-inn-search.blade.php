@push('css')
    <style>
        .dadata-field__btn {
            min-width: 150px;
            position: relative;
        }
        .dadata-field__btn[disabled] { cursor: wait; opacity: .75; }
        .dadata-field__spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid #fff6;
            border-top-color: #fff;
            border-radius: 50%;
            animation: dadata-spin .6s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        .dadata-field__btn[disabled] .dadata-field__spinner { display: inline-block; }
        @keyframes dadata-spin { to { transform: rotate(360deg); } }

        .dadata-suggestions {
            position: absolute;
            z-index: 1050;
            background: #fff;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
            max-height: 280px;
            overflow-y: auto;
            min-width: 320px;
            display: none;
        }
        .dadata-suggestions__item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            font-size: 14px;
        }
        .dadata-suggestions__item:last-child { border-bottom: 0; }
        .dadata-suggestions__item:hover,
        .dadata-suggestions__item.is-active { background: #f8f9fa; }
        .dadata-suggestions__name { font-weight: 600; }
        .dadata-suggestions__meta { color: #6c757d; font-size: 12px; margin-top: 2px; }
        .dadata-suggestions__empty { color: #6c757d; cursor: default; }
        .dadata-suggestions__empty:hover { background: #fff; }
    </style>
@endpush

@push('js')
    <script>
        (function () {
            const innInput = document.getElementById('inn');
            if (!innInput) return;

            // --- Кнопка "Найти по ИНН" под полем ИНН ---
            const formGroup = innInput.closest('.form-group');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary btn-sm dadata-field__btn mt-2';
            btn.innerHTML = '<span class="dadata-field__spinner"></span>Найти по ИНН';
            formGroup?.appendChild(btn);

            // --- Выпадающий список вариантов ---
            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative';
            formGroup?.appendChild(wrapper);

            const dropdown = document.createElement('div');
            dropdown.className = 'dadata-suggestions';
            wrapper.appendChild(dropdown);

            const endpoint = @json(route('contractors.dadata.party'));
            let lastItems = [];

            /**
             * Показать выпадающий список вариантов компаний.
             */
            function renderSuggestions(items) {
                if (!items.length) {
                    dropdown.innerHTML = '<div class="dadata-suggestions__item dadata-suggestions__empty">По этому ИНН ничего не найдено</div>';
                } else {
                    dropdown.innerHTML = items.map((item, idx) => `
                        <div class="dadata-suggestions__item" data-idx="${idx}">
                            <div class="dadata-suggestions__name">${escapeHtml(item.name)}</div>
                            <div class="dadata-suggestions__meta">
                                ИНН ${escapeHtml(item.inn)}
                                ${item.address ? ' · ' + escapeHtml(item.address) : ''}
                            </div>
                        </div>
                    `).join('');
                }
                dropdown.style.display = 'block';
            }

            /**
             * Заполнить поля формы выбранной компанией.
             */
            function applySuggestion(item) {
                const nameEl = document.getElementById('name');
                const addressEl = document.getElementById('legal_address');
                const websiteEl = document.getElementById('website');

                if (nameEl) nameEl.value = item.name || '';
                if (addressEl && item.address) addressEl.value = item.address;
                if (websiteEl && item.website) websiteEl.value = item.website;

                hideDropdown();

                if (typeof toastr !== 'undefined') {
                    toastr.success('Данные компании заполнены');
                }
            }

            function hideDropdown() { dropdown.style.display = 'none'; }

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = String(value ?? '');
                return div.innerHTML;
            }

            /**
             * Показать дружелюбное сообщение об ошибке.
             */
            function showError(message) {
                hideDropdown();
                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                } else {
                    alert(message);
                }
            }

            /**
             * Запросить варианты у бэкенда по ИНН через fetch.
             */
            async function search(inn) {
                btn.disabled = true;
                try {
                    const response = await fetch(endpoint + '?inn=' + encodeURIComponent(inn), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        showError('Сервер вернул ошибку (' + response.status + '). Попробуйте обновить страницу.');
                        return;
                    }

                    const data = await response.json();

                    if (data.error) {
                        showError(data.error);
                        return;
                    }

                    lastItems = data.suggestions || [];
                    renderSuggestions(lastItems);

                    if (!lastItems.length && typeof toastr !== 'undefined') {
                        toastr.info('По ИНН ' + inn + ' ничего не найдено');
                    }
                } catch (e) {
                    showError('Сбой сети при поиске компании. Проверьте подключение к интернету.');
                } finally {
                    btn.disabled = false;
                }
            }

            btn.addEventListener('click', function () {
                const inn = innInput.value.replace(/\D/g, '');
                if (inn.length !== 10 && inn.length !== 12) {
                    showError('Введите корректный ИНН: 10 цифр для юрлица или 12 для ИП.');
                    innInput.focus();
                    return;
                }
                search(inn);
            });

            // Enter в поле ИНН тоже запускает поиск (форма не сабмитится, т.к. type=button у основной кнопки)
            innInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btn.click();
                }
            });

            innInput.addEventListener('blur', function () {
                setTimeout(hideDropdown, 200);
            });

            dropdown.addEventListener('mousedown', function (e) {
                const item = e.target.closest('.dadata-suggestions__item');
                if (!item || item.classList.contains('dadata-suggestions__empty')) return;
                const idx = Number(item.dataset.idx);
                if (lastItems[idx]) applySuggestion(lastItems[idx]);
            });
        })();
    </script>
@endpush
