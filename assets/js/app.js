function initNavToggle() {
    const toggle = document.querySelector(".nav-toggle-label");
    const nav = document.querySelector("header nav");

    if (!toggle || !nav) {
        return;
    }

    toggle.addEventListener("click", function () {
        const isOpen = nav.classList.toggle("nav-open");
        toggle.setAttribute("aria-expanded", String(isOpen));
    });

    nav.addEventListener("click", function (event) {
        if (event.target.closest("a") && window.matchMedia("(max-width: 899px)").matches) {
            nav.classList.remove("nav-open");
            toggle.setAttribute("aria-expanded", "false");
        }
    });

    const dropdownItems = Array.from(nav.querySelectorAll(".nav-dropdown"));
    const closeTimers = new WeakMap();

    dropdownItems.forEach(function (dropdownItem) {
        const dropdown = dropdownItem.querySelector(":scope > details");
        dropdown.addEventListener("toggle", function () {
            if (!dropdown.open) {
                return;
            }

            dropdownItems.forEach(function (otherItem) {
                const otherDropdown = otherItem.querySelector(":scope > details");
                if (otherDropdown !== dropdown) {
                    otherDropdown.open = false;
                }
            });
        });

        dropdownItem.addEventListener("pointerenter", function () {
            window.clearTimeout(closeTimers.get(dropdownItem));
        });

        dropdownItem.addEventListener("pointerleave", function () {
            if (!window.matchMedia("(min-width: 900px)").matches) {
                return;
            }

            closeTimers.set(dropdownItem, window.setTimeout(function () {
                dropdown.open = false;
            }, 300));
        });
    });

    document.addEventListener("pointerdown", function (event) {
        if (!nav.contains(event.target)) {
            dropdownItems.forEach(function (dropdownItem) {
                window.clearTimeout(closeTimers.get(dropdownItem));
                dropdownItem.querySelector(":scope > details").open = false;
            });
        }
    });

    document.addEventListener("focusin", function (event) {
        if (!nav.contains(event.target)) {
            dropdownItems.forEach(function (dropdownItem) {
                window.clearTimeout(closeTimers.get(dropdownItem));
                dropdownItem.querySelector(":scope > details").open = false;
            });
        }
    });
}

function updateTableCount(section) {
    const count = section.querySelector(".table-count");
    const rows = section.querySelectorAll("tbody tr");

    if (!count) {
        return;
    }

    const visibleRows = Array.from(rows).filter(function (row) {
        return !row.hidden;
    }).length;

    count.textContent = `Menampilkan ${visibleRows} dari ${rows.length} ${count.dataset.itemLabel}.`;
}

function initHapusConfirm() {
    document.addEventListener("submit", function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains("form-hapus")) {
            return;
        }

        const row = form.closest("tr");
        const itemName = row?.cells[0]?.textContent.trim() || "data ini";

        if (!window.confirm(`Yakin ingin menghapus "${itemName}"?`)) {
            event.preventDefault();
        }
    });
}

async function muatDataTabel(url, kunciKolom, opsi = {}) {
    const tbodySelector = opsi.tbodySelector || ".table-responsive table tbody";
    const loadingId = opsi.loadingId || "loading-indicator";
    const delay = opsi.delay !== undefined ? opsi.delay : 3000;

    const tbody = document.querySelector(tbodySelector);
    const loading = document.getElementById(loadingId);
    if (!tbody || !loading) return;

    loading.style.display = "block";
    tbody.innerHTML = "";

    try {
        await new Promise((resolve) => setTimeout(resolve, delay));

        const res = await fetch(url);
        if (!res.ok) {
            throw new Error("Gagal mengambil data (status " + res.status + ")");
        }

        const dataList = await res.json();
        dataList.forEach(function (item) {
            const tr = document.createElement("tr");
            let cellHtml = "";
            kunciKolom.forEach(function (kunci) {
                const val = item[kunci] !== undefined && item[kunci] !== null ? item[kunci] : "";
                cellHtml += "<td>" + val + "</td>";
            });
            cellHtml +=
                "<td>" +
                "<button type=\"button\">Edit</button> " +
                "<button type=\"button\" class=\"btn-hapus\">Hapus</button>" +
                "</td>";
            tr.innerHTML = cellHtml;
            tbody.appendChild(tr);
        });

        const section = tbody.closest("section");
        if (section) {
            const filterInput = section.querySelector(".table-filter");
            if (filterInput && filterInput.value.trim() !== "") {
                filterInput.dispatchEvent(new Event("input"));
            } else {
                updateTableCount(section);
            }
        }
    } catch (err) {
        const messageRow = document.createElement("tr");
        const messageCell = document.createElement("td");
        const table = tbody.closest("table");
        const thCount = table ? table.querySelectorAll("thead th").length : (kunciKolom.length + 1);
        messageCell.colSpan = thCount;
        messageCell.textContent = "Gagal memuat data: " + err.message;
        messageRow.appendChild(messageCell);
        tbody.appendChild(messageRow);
    } finally {
        loading.style.display = "none";
    }
}

function initTableFilter() {
    document.querySelectorAll(".table-filter").forEach(function (filter) {
        const section = filter.closest("section");
        const table = section?.querySelector("tbody");

        if (!table) {
            return;
        }

        updateTableCount(section);

        const applyFilter = function () {
            const query = filter.value.trim().toLocaleLowerCase("id");
            const columnIndexes = (filter.dataset.filterColumns || filter.dataset.filterColumn || "")
                .split(",")
                .filter(function (value) {
                    return value.trim() !== "";
                })
                .map(function (value) {
                    return Number(value.trim());
                })
                .filter(function (value) {
                    return Number.isInteger(value) && value >= 0;
                });
            let visibleRows = 0;

            table.querySelectorAll("tr").forEach(function (row) {
                const searchableText = columnIndexes.length > 0
                    ? columnIndexes.map(function (index) {
                        return row.cells[index]?.textContent || "";
                    }).join(" ")
                    : row.textContent;
                const matches = searchableText.toLocaleLowerCase("id").includes(query);
                row.hidden = !matches;
                if (matches) {
                    visibleRows += 1;
                }
            });

            updateTableCount(section);
            const status = section.querySelector("#member-search-status");
            if (status) {
                status.textContent = query && visibleRows === 0
                    ? "Tidak ada anggota yang cocok dengan pencarian."
                    : "";
            }

            if (filter.form?.id === "member-search-form") {
                const url = new URL(window.location.href);
                if (filter.value.trim()) {
                    url.searchParams.set("q", filter.value.trim());
                } else {
                    url.searchParams.delete("q");
                }
                window.history.replaceState({}, "", url);
            }
        };

        filter.addEventListener("input", applyFilter);

        if (filter.form?.id === "member-search-form") {
            filter.form.addEventListener("submit", function (event) {
                event.preventDefault();
                applyFilter();
            });
            applyFilter();
        }
    });
}

function initValidasiForm() {
    const forms = Array.from(document.querySelectorAll("form")).filter(function (form) {
        return form.querySelector("#judul, #no_anggota");
    });

    forms.forEach(function (form) {
        form.noValidate = true;
        const fields = Array.from(form.querySelectorAll("input:not([type='hidden']), select, textarea"));

        function getFieldError(field) {
            const value = field.value.trim();

            if (field.required && value === "") {
                return "Field ini wajib diisi.";
            }

            if (field.id === "tahun" && value === "") {
                return "Tahun terbit wajib diisi.";
            }

            if (value === "") {
                return "";
            }

            if (field.id === "nama" && value.length < 3) {
                return "Nama lengkap minimal 3 karakter.";
            }
            if (field.id === "nama" && !/^[a-zA-Z\s.',-]+$/.test(value)) {
                return "Nama hanya boleh berisi huruf, spasi, dan tanda baca nama.";
            }
            if (field.id === "no_anggota" && !/^[A-Za-z0-9-]{3,15}$/.test(value)) {
                return "Nomor anggota harus alfanumerik 3-15 karakter.";
            }
            if (field.id === "no_hp" && !/^(\+62|62|08)[0-9]{8,13}$/.test(value)) {
                return "Nomor HP harus diawali 08, 62, atau +62 dan memiliki 10-15 digit.";
            }
            if (field.id === "alamat" && value.length < 5) {
                return "Alamat minimal 5 karakter.";
            }
            if (field.id === "judul" && value.length < 2) {
                return "Judul buku minimal 2 karakter.";
            }
            if (field.id === "pengarang" && value.length < 2) {
                return "Nama pengarang minimal 2 karakter.";
            }
            if (field.id === "isbn" && !/^[0-9-]+$/.test(value)) {
                return "ISBN hanya boleh berisi angka dan tanda hubung.";
            }
            if (field.id === "isbn") {
                if (!/^[0-9]+(?:-[0-9]+)*$/.test(value)) {
                    return "Format ISBN tidak valid.";
                }
                const digitCount = value.replace(/-/g, "").length;
                if (digitCount !== 10 && digitCount !== 13) {
                    return "ISBN harus terdiri dari 10 atau 13 digit.";
                }
            }
            if (field.id === "tanggal_lahir") {
                const today = new Date();
                const localToday = [
                    today.getFullYear(),
                    String(today.getMonth() + 1).padStart(2, "0"),
                    String(today.getDate()).padStart(2, "0")
                ].join("-");
                if (value > localToday) {
                    return "Tanggal lahir tidak boleh melebihi hari ini.";
                }
            }
            if (field.validity.rangeUnderflow) {
                return `Nilai minimal adalah ${field.min}.`;
            }
            if (field.validity.rangeOverflow) {
                return `Nilai maksimal adalah ${field.max}.`;
            }
            if (field.validity.stepMismatch) {
                return "Masukkan bilangan bulat yang valid.";
            }
            if (field.validity.typeMismatch) {
                return "Masukkan format yang valid.";
            }

            return "";
        }

        function validateField(field) {
            const errorId = `${field.id || field.name}-validation-error`;
            const message = getFieldError(field);
            let error = Array.from(field.parentElement.querySelectorAll(".form-error")).find(function (item) {
                return item.dataset.validationError === errorId;
            });

            if (message) {
                if (!error) {
                    error = document.createElement("span");
                    error.className = "form-error";
                    error.dataset.validationError = errorId;
                    error.setAttribute("role", "status");
                    error.setAttribute("aria-live", "polite");
                    field.insertAdjacentElement("afterend", error);
                }
                error.textContent = message;
                field.setAttribute("aria-invalid", "true");
                return false;
            }

            if (error) {
                error.remove();
            }
            field.removeAttribute("aria-invalid");
            return true;
        }

        fields.forEach(function (field) {
            field.addEventListener("input", function () {
                validateField(field);
            });
            field.addEventListener("change", function () {
                validateField(field);
            });
            validateField(field);
        });

        form.addEventListener("submit", function (event) {
            let firstInvalidField = null;

            fields.forEach(function (field) {
                if (!validateField(field)) {
                    firstInvalidField ||= field;
                }
            });

            if (firstInvalidField) {
                event.preventDefault();
                firstInvalidField.focus();
            } else if (form.dataset.confirmSubmit && !window.confirm(form.dataset.confirmSubmit)) {
                event.preventDefault();
            }
        });
    });
}

document.addEventListener("DOMContentLoaded", function () {
    initNavToggle();
    initHapusConfirm();
    initTableFilter();
    initValidasiForm();
});
