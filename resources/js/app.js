const MODES = ['light', 'dark'];
const ACCENTS = ['aurora', 'lagoon', 'ember', 'forest'];
const STORAGE_KEYS = {
    mode: 'echoserp:mode',
    accent: 'echoserp:accent',
};

const root = document.documentElement;
const body = document.body;
const themeMeta = document.querySelector('meta[name="theme-color"]');

const storage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (error) {
            //
        }
    },
};

const resolveMode = () => {
    const storedMode = storage.get(STORAGE_KEYS.mode);

    if (MODES.includes(storedMode)) {
        return storedMode;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const resolveAccent = () => {
    const storedAccent = storage.get(STORAGE_KEYS.accent);

    return ACCENTS.includes(storedAccent) ? storedAccent : 'aurora';
};

const syncThemeControls = () => {
    document.querySelectorAll('[data-set-mode]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.setMode === root.dataset.mode));
    });

    document.querySelectorAll('[data-set-accent]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.setAccent === root.dataset.accent));
    });
};

const syncThemeColor = () => {
    if (!themeMeta) {
        return;
    }

    themeMeta.setAttribute('content', root.dataset.mode === 'dark' ? '#0b1324' : '#f4f7fb');
};

const applyTheme = ({ mode = root.dataset.mode, accent = root.dataset.accent, persist = true } = {}) => {
    const nextMode = MODES.includes(mode) ? mode : resolveMode();
    const nextAccent = ACCENTS.includes(accent) ? accent : resolveAccent();

    root.dataset.mode = nextMode;
    root.dataset.accent = nextAccent;

    if (persist) {
        storage.set(STORAGE_KEYS.mode, nextMode);
        storage.set(STORAGE_KEYS.accent, nextAccent);
    }

    syncThemeControls();
    syncThemeColor();
};

const setSidebarState = (isOpen) => {
    root.dataset.sidebar = isOpen ? 'open' : 'closed';
    body.classList.toggle('is-sidebar-open', isOpen);
};

const getOpenModals = () => Array.from(document.querySelectorAll('[data-modal]:not([hidden])'));

const syncModalState = () => {
    body.classList.toggle('is-modal-open', getOpenModals().length > 0);
};

const focusModalTarget = (modal) => {
    const target = modal.querySelector('[data-modal-focus], input, select, textarea, button');

    if (target instanceof HTMLElement) {
        target.focus();
    }
};

const openModal = (id) => {
    const modal = document.getElementById(id);

    if (!modal) {
        return;
    }

    getOpenModals().forEach((activeModal) => {
        if (activeModal !== modal) {
            activeModal.hidden = true;
            activeModal.classList.remove('is-open');
            activeModal.setAttribute('aria-hidden', 'true');
        }
    });

    setSidebarState(false);
    modal.hidden = false;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    syncModalState();
    focusModalTarget(modal);
};

const closeModal = (modalOrId) => {
    const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId;

    if (!modal) {
        return;
    }

    modal.hidden = true;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    syncModalState();
};

const escapeForRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const normalizeSlugPart = (value, separator = '-') => {
    const normalized = value
        .toLowerCase()
        .trim()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, separator);

    const escapedSeparator = escapeForRegex(separator);

    return normalized
        .replace(new RegExp(`${escapedSeparator}{2,}`, 'g'), separator)
        .replace(new RegExp(`^${escapedSeparator}|${escapedSeparator}$`, 'g'), '');
};

applyTheme({
    mode: resolveMode(),
    accent: resolveAccent(),
    persist: false,
});

document.querySelectorAll('[data-set-mode]').forEach((button) => {
    button.addEventListener('click', () => {
        applyTheme({ mode: button.dataset.setMode, accent: root.dataset.accent });
    });
});

document.querySelectorAll('[data-set-accent]').forEach((button) => {
    button.addEventListener('click', () => {
        applyTheme({ mode: root.dataset.mode, accent: button.dataset.setAccent });
    });
});

document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        setSidebarState(root.dataset.sidebar !== 'open');
    });
});

document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
    button.addEventListener('click', () => {
        setSidebarState(false);
    });
});

document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        openModal(button.dataset.modalOpen);
    });
});

document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
        closeModal(button.closest('[data-modal]'));
    });
});

document.addEventListener('keydown', (event) => {
    const activeModal = getOpenModals().at(-1);

    if (event.key === 'Escape') {
        if (activeModal) {
            closeModal(activeModal);
            return;
        }

        setSidebarState(false);
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth > 980) {
        setSidebarState(false);
    }
});

const groups = Array.from(document.querySelectorAll('[data-nav-group]'));

groups.forEach((group) => {
    group.addEventListener('toggle', () => {
        if (!group.open) {
            return;
        }

        groups.forEach((otherGroup) => {
            if (otherGroup !== group) {
                otherGroup.open = false;
            }
        });
    });
});

document.querySelectorAll('.modal-form').forEach((form) => {
    const moduleInput = form.querySelector('[data-permission-module]');
    const actionInput = form.querySelector('[data-permission-action]');
    const preview = form.querySelector('[data-permission-slug-preview]');

    if (!moduleInput || !actionInput || !preview) {
        return;
    }

    const getSelectedOption = (select) => {
        if (!(select instanceof HTMLSelectElement)) {
            return null;
        }

        return select.options[select.selectedIndex] ?? null;
    };

    const syncActionAvailability = () => {
        const selectedModule = getSelectedOption(moduleInput);
        const allowedActions = (selectedModule?.dataset.actions ?? '')
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean);

        let selectedActionStillAvailable = false;

        Array.from(actionInput.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const isAvailable = allowedActions.includes(option.value);

            option.hidden = !isAvailable;
            option.disabled = !isAvailable;

            if (isAvailable && option.selected) {
                selectedActionStillAvailable = true;
            }
        });

        if (allowedActions.length === 0 || !selectedActionStillAvailable) {
            actionInput.value = '';
        }
    };

    const syncPreview = () => {
        const moduleSlug = getSelectedOption(moduleInput)?.dataset.slug ?? '';
        const actionSlug = getSelectedOption(actionInput)?.dataset.slug ?? '';
        const slug = [moduleSlug, actionSlug].filter(Boolean).join('.');

        preview.textContent = slug || 'module.action';
    };

    moduleInput.addEventListener('change', () => {
        syncActionAvailability();
        syncPreview();
    });

    actionInput.addEventListener('change', syncPreview);

    syncActionAvailability();
    syncPreview();
});

const createTemplateFragment = (template, replacements = {}) => {
    if (!(template instanceof HTMLTemplateElement)) {
        return null;
    }

    let markup = template.innerHTML;

    Object.entries(replacements).forEach(([needle, replacement]) => {
        markup = markup.split(needle).join(String(replacement));
    });

    const fragmentTemplate = document.createElement('template');
    fragmentTemplate.innerHTML = markup.trim();

    return fragmentTemplate.content.cloneNode(true);
};

const formatQuotationAmount = (value) => new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format(value);

const initializeQuotationEditors = () => {
    document.querySelectorAll('[data-quotation-editor]').forEach((form) => {
        let catalogItems = [];
        let customers = [];

        try {
            catalogItems = JSON.parse(form.dataset.catalogItems ?? '[]');
        } catch (error) {
            catalogItems = [];
        }

        try {
            customers = JSON.parse(form.dataset.customers ?? '[]');
        } catch (error) {
            customers = [];
        }

        const catalogMap = new Map(
            catalogItems.map((item) => [String(item.lookup_label ?? '').trim(), item]),
        );
        const customerMap = new Map(
            customers.map((customer) => [String(customer.id ?? ''), customer]),
        );

        const customerSelect = form.querySelector('[data-customer-select]');
        const lineItemList = form.querySelector('[data-line-item-list]');
        const workSectionList = form.querySelector('[data-work-section-list]');
        const lineItemTemplate = form.querySelector('[data-line-item-template]');
        const workSectionTemplate = form.querySelector('[data-work-section-template]');
        const workTaskTemplate = form.querySelector('[data-work-task-template]');
        const estimatedHoursInput = form.querySelector('[data-estimated-hours]');
        const estimatedDaysInput = form.querySelector('[data-estimated-days]');
        const hoursPerDayInput = form.querySelector('[data-hours-per-day]');
        const taxRateInput = form.querySelector('[data-tax-rate]');
        const subtotalTarget = form.querySelector('[data-summary-subtotal]');
        const discountTarget = form.querySelector('[data-summary-discount]');
        const taxTarget = form.querySelector('[data-summary-tax]');
        const totalTarget = form.querySelector('[data-summary-total]');

        if (!lineItemList || !workSectionList || !lineItemTemplate || !workSectionTemplate || !workTaskTemplate) {
            return;
        }

        const applyCustomer = (customerId) => {
            const customer = customerMap.get(String(customerId ?? ''));

            if (!customer) {
                return;
            }

            const fields = {
                '[data-customer-company]': customer.company_name ?? '',
                '[data-customer-document-label]': customer.document_label ?? 'RUC',
                '[data-customer-document-number]': customer.document_number ?? '',
                '[data-customer-email]': customer.email ?? '',
                '[data-customer-phone]': customer.phone ?? '',
                '[data-customer-address]': customer.address ?? '',
            };

            Object.entries(fields).forEach(([selector, value]) => {
                const input = form.querySelector(selector);

                if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement || input instanceof HTMLSelectElement) {
                    input.value = value;
                }
            });
        };

        const readDecimal = (input) => {
            const parsed = Number.parseFloat(input?.value ?? '0');

            return Number.isFinite(parsed) ? parsed : 0;
        };

        const readInteger = (input) => {
            const parsed = Number.parseInt(input?.value ?? '0', 10);

            return Number.isFinite(parsed) ? parsed : 0;
        };

        const normalizeWholeNumberInput = (input) => {
            if (!(input instanceof HTMLInputElement) || input.value === '') {
                return;
            }

            const parsed = Number.parseFloat(input.value);

            if (!Number.isFinite(parsed)) {
                input.value = '';
                return;
            }

            const min = input.min === '' ? null : Number.parseInt(input.min, 10);
            const rounded = Math.round(parsed);
            const normalized = Number.isFinite(min) ? Math.max(rounded, min) : rounded;

            input.value = String(Math.max(normalized, 0));
        };

        const bindWholeNumberInput = (input) => {
            if (!(input instanceof HTMLInputElement) || input.dataset.wholeNumberBound) {
                return;
            }

            input.dataset.wholeNumberBound = 'true';
            input.addEventListener('change', () => {
                normalizeWholeNumberInput(input);
            });
            input.addEventListener('blur', () => {
                normalizeWholeNumberInput(input);
            });
        };

        const bindWholeNumberInputs = (target = form) => {
            target.querySelectorAll('[data-whole-number]').forEach(bindWholeNumberInput);
        };

        const syncWorkTime = () => {
            const durationDays = Array.from(workSectionList.querySelectorAll('[data-task-duration]'))
                .reduce((carry, input) => carry + Math.max(readInteger(input), 0), 0);
            const hoursPerDay = Math.max(readInteger(hoursPerDayInput), 0);
            const wasCalculatedFromTasks = estimatedDaysInput?.dataset.calculatedFromTasks === 'true';
            let days = durationDays > 0 ? durationDays : Math.max(readInteger(estimatedDaysInput), 0);

            if (estimatedDaysInput && durationDays > 0) {
                estimatedDaysInput.value = String(durationDays);
                estimatedDaysInput.dataset.calculatedFromTasks = 'true';
                days = durationDays;
            } else if (estimatedDaysInput && wasCalculatedFromTasks) {
                estimatedDaysInput.value = '';
                delete estimatedDaysInput.dataset.calculatedFromTasks;
                days = 0;
            }

            if (!estimatedHoursInput) {
                return;
            }

            if (days > 0 && hoursPerDay > 0) {
                estimatedHoursInput.value = String(days * hoursPerDay);
            } else if (durationDays > 0 || wasCalculatedFromTasks) {
                estimatedHoursInput.value = '';
            }
        };

        const releaseLineItemPreviewUrl = (lineItem) => {
            if (!lineItem.dataset.localPreviewUrl) {
                return;
            }

            URL.revokeObjectURL(lineItem.dataset.localPreviewUrl);
            delete lineItem.dataset.localPreviewUrl;
        };

        const lineImageCaption = (source) => {
            if (source === 'catalog') {
                return 'Imagen heredada del catalogo comercial.';
            }

            if (source === 'uploaded') {
                return 'Imagen cargada manualmente para esta cotizacion.';
            }

            return 'Aun no se ha definido una imagen para este item.';
        };

        const syncLineImagePreview = (lineItem, { url = '', source = '' } = {}) => {
            const preview = lineItem.querySelector('[data-line-image-preview]');
            const previewImage = lineItem.querySelector('[data-line-image-preview-img]');
            const previewCaption = lineItem.querySelector('[data-line-image-caption]');
            const placeholder = lineItem.querySelector('[data-line-image-placeholder]');
            const clearButton = lineItem.querySelector('[data-clear-line-image]');

            if (!preview || !previewImage || !previewCaption || !placeholder || !clearButton) {
                return;
            }

            if (!url) {
                preview.hidden = true;
                previewImage.removeAttribute('src');
                previewCaption.textContent = lineImageCaption('');
                placeholder.hidden = false;
                clearButton.hidden = true;
                return;
            }

            preview.hidden = false;
            previewImage.src = url;
            previewCaption.textContent = lineImageCaption(source);
            placeholder.hidden = true;
            clearButton.hidden = false;
        };

        const syncLineTotal = (lineItem) => {
            const quantityInput = lineItem.querySelector('[data-line-quantity]');
            const unitPriceInput = lineItem.querySelector('[data-line-unit-price]');
            const discountInput = lineItem.querySelector('[data-line-discount]');
            const totalOutput = lineItem.querySelector('[data-line-total]');

            if (!quantityInput || !unitPriceInput || !discountInput || !totalOutput) {
                return;
            }

            const subtotal = readDecimal(quantityInput) * readDecimal(unitPriceInput);
            const total = Math.max(subtotal - readDecimal(discountInput), 0);

            totalOutput.textContent = formatQuotationAmount(total);
        };

        const syncSummary = () => {
            const lineItems = Array.from(lineItemList.querySelectorAll('[data-line-item]'));
            const subtotal = lineItems.reduce((carry, lineItem) => {
                const quantityInput = lineItem.querySelector('[data-line-quantity]');
                const unitPriceInput = lineItem.querySelector('[data-line-unit-price]');

                return carry + (readDecimal(quantityInput) * readDecimal(unitPriceInput));
            }, 0);
            const discount = lineItems.reduce((carry, lineItem) => {
                const discountInput = lineItem.querySelector('[data-line-discount]');

                return carry + readDecimal(discountInput);
            }, 0);
            const base = Math.max(subtotal - discount, 0);
            const taxRate = readDecimal(taxRateInput);
            const tax = base * (taxRate / 100);
            const total = base + tax;

            if (subtotalTarget) {
                subtotalTarget.textContent = formatQuotationAmount(subtotal);
            }

            if (discountTarget) {
                discountTarget.textContent = formatQuotationAmount(discount);
            }

            if (taxTarget) {
                taxTarget.textContent = formatQuotationAmount(tax);
            }

            if (totalTarget) {
                totalTarget.textContent = formatQuotationAmount(total);
            }
        };

        const applyCatalogItem = (lineItem, lookupValue) => {
            const lookup = String(lookupValue ?? '').trim();
            const catalogItem = catalogMap.get(lookup);
            const hiddenCatalogId = lineItem.querySelector('[data-catalog-id]');
            const nameInput = lineItem.querySelector('[data-line-name]');
            const descriptionInput = lineItem.querySelector('[data-line-description]');
            const specificationsInput = lineItem.querySelector('[data-line-specifications]');
            const unitInput = lineItem.querySelector('[data-line-unit]');
            const unitPriceInput = lineItem.querySelector('[data-line-unit-price]');
            const imageInput = lineItem.querySelector('[data-line-image-input]');
            const imagePathInput = lineItem.querySelector('[data-line-image-path]');
            const imageSourceInput = lineItem.querySelector('[data-line-image-source]');
            const imageUrlInput = lineItem.querySelector('[data-line-image-url]');
            const imageRemoveInput = lineItem.querySelector('[data-line-image-remove]');

            if (!catalogItem) {
                if (hiddenCatalogId) {
                    hiddenCatalogId.value = '';
                }

                if (specificationsInput) {
                    specificationsInput.value = '';
                }

                return;
            }

            if (hiddenCatalogId) {
                hiddenCatalogId.value = String(catalogItem.id ?? '');
            }

            if (nameInput) {
                nameInput.value = catalogItem.name ?? '';
            }

            if (descriptionInput) {
                descriptionInput.value = catalogItem.description ?? '';
            }

            if (specificationsInput) {
                specificationsInput.value = Array.isArray(catalogItem.specifications)
                    ? catalogItem.specifications.join('\n')
                    : (catalogItem.specifications_text ?? '');
            }

            if (unitInput) {
                unitInput.value = catalogItem.unit_label ?? '';
            }

            if (unitPriceInput) {
                unitPriceInput.value = catalogItem.price ?? '';
            }

            if (imageInput) {
                imageInput.value = '';
            }

            releaseLineItemPreviewUrl(lineItem);

            if (imagePathInput) {
                imagePathInput.value = catalogItem.image_path ?? '';
            }

            if (imageSourceInput) {
                imageSourceInput.value = catalogItem.image_path ? 'catalog' : '';
            }

            if (imageUrlInput) {
                imageUrlInput.value = catalogItem.image_url ?? '';
            }

            if (imageRemoveInput) {
                imageRemoveInput.value = '0';
            }

            syncLineImagePreview(lineItem, {
                url: catalogItem.image_url ?? '',
                source: catalogItem.image_path ? 'catalog' : '',
            });

            syncLineTotal(lineItem);
            syncSummary();
        };

        const bindLineItem = (lineItem) => {
            const lookupInput = lineItem.querySelector('[data-catalog-lookup]');
            const quantityInput = lineItem.querySelector('[data-line-quantity]');
            const unitPriceInput = lineItem.querySelector('[data-line-unit-price]');
            const discountInput = lineItem.querySelector('[data-line-discount]');
            const removeButton = lineItem.querySelector('[data-remove-line-item]');
            const imageInput = lineItem.querySelector('[data-line-image-input]');
            const imagePathInput = lineItem.querySelector('[data-line-image-path]');
            const imageSourceInput = lineItem.querySelector('[data-line-image-source]');
            const imageUrlInput = lineItem.querySelector('[data-line-image-url]');
            const imageRemoveInput = lineItem.querySelector('[data-line-image-remove]');
            const clearImageButton = lineItem.querySelector('[data-clear-line-image]');

            bindWholeNumberInput(quantityInput);

            if (lookupInput) {
                const onLookupChange = () => {
                    applyCatalogItem(lineItem, lookupInput.value);
                };

                lookupInput.addEventListener('change', onLookupChange);
                lookupInput.addEventListener('blur', onLookupChange);
            }

            [quantityInput, unitPriceInput, discountInput].forEach((input) => {
                input?.addEventListener('input', () => {
                    syncLineTotal(lineItem);
                    syncSummary();
                });
            });

            imageInput?.addEventListener('change', () => {
                const file = imageInput.files?.[0];

                if (!file) {
                    return;
                }

                releaseLineItemPreviewUrl(lineItem);

                const previewUrl = URL.createObjectURL(file);
                lineItem.dataset.localPreviewUrl = previewUrl;

                if (imagePathInput) {
                    imagePathInput.value = '';
                }

                if (imageSourceInput) {
                    imageSourceInput.value = 'uploaded';
                }

                if (imageUrlInput) {
                    imageUrlInput.value = '';
                }

                if (imageRemoveInput) {
                    imageRemoveInput.value = '0';
                }

                syncLineImagePreview(lineItem, {
                    url: previewUrl,
                    source: 'uploaded',
                });
            });

            clearImageButton?.addEventListener('click', () => {
                releaseLineItemPreviewUrl(lineItem);

                if (imageInput) {
                    imageInput.value = '';
                }

                if (imagePathInput) {
                    imagePathInput.value = '';
                }

                if (imageSourceInput) {
                    imageSourceInput.value = '';
                }

                if (imageUrlInput) {
                    imageUrlInput.value = '';
                }

                if (imageRemoveInput) {
                    imageRemoveInput.value = '1';
                }

                syncLineImagePreview(lineItem, {});
            });

            removeButton?.addEventListener('click', () => {
                releaseLineItemPreviewUrl(lineItem);
                lineItem.remove();

                if (!lineItemList.querySelector('[data-line-item]')) {
                    addLineItem();
                }

                syncSummary();
            });

            syncLineImagePreview(lineItem, {
                url: imageUrlInput?.value ?? '',
                source: imageSourceInput?.value ?? '',
            });
            syncLineTotal(lineItem);
        };

        const addLineItem = () => {
            const nextIndex = Number.parseInt(lineItemList.dataset.nextLineIndex ?? '0', 10);
            const fragment = createTemplateFragment(lineItemTemplate, {
                '__INDEX__': nextIndex,
            });

            if (!fragment) {
                return;
            }

            lineItemList.appendChild(fragment);
            lineItemList.dataset.nextLineIndex = String(nextIndex + 1);
            lineItemList.querySelectorAll('[data-line-item]').forEach((lineItem) => {
                if (!lineItem.dataset.bound) {
                    lineItem.dataset.bound = 'true';
                    bindLineItem(lineItem);
                }
            });
            syncSummary();
        };

        const bindWorkTask = (task) => {
            const removeButton = task.querySelector('[data-remove-work-task]');
            const durationInput = task.querySelector('[data-task-duration]');

            bindWholeNumberInput(durationInput);

            durationInput?.addEventListener('input', syncWorkTime);
            durationInput?.addEventListener('change', () => {
                normalizeWholeNumberInput(durationInput);
                syncWorkTime();
            });

            removeButton?.addEventListener('click', () => {
                const section = task.closest('[data-work-section]');
                const list = task.closest('[data-work-task-list]');

                task.remove();

                if (section && list && !list.querySelector('[data-work-task]')) {
                    addWorkTask(section);
                }

                syncWorkTime();
            });
        };

        const addWorkTask = (section) => {
            const taskList = section.querySelector('[data-work-task-list]');

            if (!taskList) {
                return;
            }

            const sectionIndex = section.dataset.sectionIndex ?? '0';
            const nextTaskIndex = Number.parseInt(section.dataset.nextTaskIndex ?? '0', 10);
            const fragment = createTemplateFragment(workTaskTemplate, {
                '__SECTION_INDEX__': sectionIndex,
                '__TASK_INDEX__': nextTaskIndex,
            });

            if (!fragment) {
                return;
            }

            taskList.appendChild(fragment);
            section.dataset.nextTaskIndex = String(nextTaskIndex + 1);
            taskList.querySelectorAll('[data-work-task]').forEach((task) => {
                if (!task.dataset.bound) {
                    task.dataset.bound = 'true';
                    bindWorkTask(task);
                }
            });
            syncWorkTime();
        };

        const bindWorkSection = (section) => {
            const addTaskButton = section.querySelector('[data-add-work-task]');
            const removeSectionButton = section.querySelector('[data-remove-work-section]');

            addTaskButton?.addEventListener('click', () => {
                addWorkTask(section);
            });

            removeSectionButton?.addEventListener('click', () => {
                section.remove();
                syncWorkTime();
            });

            section.querySelectorAll('[data-work-task]').forEach((task) => {
                if (!task.dataset.bound) {
                    task.dataset.bound = 'true';
                    bindWorkTask(task);
                }
            });
        };

        const addWorkSection = () => {
            const nextSectionIndex = Number.parseInt(workSectionList.dataset.nextSectionIndex ?? '0', 10);
            const fragment = createTemplateFragment(workSectionTemplate, {
                '__SECTION_INDEX__': nextSectionIndex,
            });

            if (!fragment) {
                return;
            }

            workSectionList.appendChild(fragment);
            workSectionList.dataset.nextSectionIndex = String(nextSectionIndex + 1);
            workSectionList.querySelectorAll('[data-work-section]').forEach((section) => {
                if (!section.dataset.bound) {
                    section.dataset.bound = 'true';
                    bindWorkSection(section);
                }
            });
            syncWorkTime();
        };

        customerSelect?.addEventListener('change', () => {
            applyCustomer(customerSelect.value);
        });

        form.querySelector('[data-add-line-item]')?.addEventListener('click', addLineItem);
        form.querySelector('[data-add-work-section]')?.addEventListener('click', addWorkSection);
        taxRateInput?.addEventListener('input', syncSummary);
        hoursPerDayInput?.addEventListener('input', syncWorkTime);
        hoursPerDayInput?.addEventListener('change', () => {
            normalizeWholeNumberInput(hoursPerDayInput);
            syncWorkTime();
        });
        form.addEventListener('submit', () => {
            bindWholeNumberInputs(form);
            form.querySelectorAll('[data-whole-number]').forEach(normalizeWholeNumberInput);
            syncWorkTime();
        });

        lineItemList.querySelectorAll('[data-line-item]').forEach((lineItem) => {
            if (!lineItem.dataset.bound) {
                lineItem.dataset.bound = 'true';
                bindLineItem(lineItem);
            }
        });

        workSectionList.querySelectorAll('[data-work-section]').forEach((section) => {
            if (!section.dataset.bound) {
                section.dataset.bound = 'true';
                bindWorkSection(section);
            }
        });

        bindWholeNumberInputs(form);
        syncWorkTime();
        syncSummary();
    });
};

initializeQuotationEditors();

const initialModal = body.dataset.initialModal?.trim();

if (initialModal) {
    openModal(initialModal);
}
