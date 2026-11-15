function initBookSearch() {
    const form = document.getElementById("book-search-form");
    const input = document.getElementById("search-input");
    const tbody = document.getElementById("book-results-body");
    const summary = document.getElementById("book-search-summary");
    const term = document.getElementById("book-search-term");
    const count = document.getElementById("book-search-count");
    const status = document.getElementById("book-search-status");
    const pagination = document.getElementById("book-pagination");
    const canDelete = form && form.dataset.canDelete === "true";

    if (!form || !input || !tbody || !summary || !term || !count || !status || !pagination) {
        return;
    }

    let debounceTimer;
    let activeRequest;
    let requestNumber = 0;

    function updateSearchUrl(query, page = 1) {
        const url = new URL(window.location.href);
        url.search = "";
        if (query) {
            url.searchParams.set("q", query);
        }
        if (page > 1) {
            url.searchParams.set("page", String(page));
        }
        window.history.replaceState({}, "", url);
    }

    function addCell(value, className) {
        const cell = document.createElement("td");
        const text = value === null || value === undefined || value === "" ? "-" : String(value);

        if (className === "title") {
            const title = document.createElement("strong");
            title.textContent = text;
            cell.appendChild(title);
            return cell;
        }

        if (className === "isbn") {
            const code = document.createElement("code");
            code.textContent = text;
            cell.appendChild(code);
            return cell;
        }

        if (className === "date") {
            const small = document.createElement("small");
            small.style.color = "#666";
            small.textContent = text;
            cell.appendChild(small);
            return cell;
        }

        cell.textContent = text;
        return cell;
    }

    function renderBooks(books) {
        tbody.replaceChildren();

        if (books.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = canDelete ? 7 : 5;
            cell.textContent = "Tidak ada buku yang cocok dengan pencarian.";
            row.appendChild(cell);
            tbody.appendChild(row);
            return;
        }

        books.forEach(function (book) {
            const row = document.createElement("tr");
            const titleCell = addCell(book.judul, "title");

            if (book.kategori) {
                const category = document.createElement("span");
                category.style.display = "block";
                category.style.fontSize = "0.8rem";
                category.style.color = "#666";
                category.textContent = book.kategori;
                titleCell.appendChild(category);
            }

            const cells = [
                titleCell,
                addCell(book.pengarang),
                addCell(book.isbn, "isbn"),
                addCell(book.tahun),
                addCell(book.stok)
            ];

            if (canDelete) {
                cells.push(addCell(book.tanggal_ditambahkan, "date"));
            }

            row.append(...cells);

            if (!canDelete) {
                tbody.appendChild(row);
                return;
            }

            const actionCell = document.createElement("td");
            const actions = document.createElement("div");
            actions.className = "book-actions";
            actions.style.display = "flex";
            actions.style.gap = "0.5rem";
            actions.style.alignItems = "center";
            actions.style.flexWrap = "nowrap";

            const edit = document.createElement("a");
            edit.href = "ubah.php?id=" + encodeURIComponent(book.id);
            edit.className = "btn btn-secondary";
            edit.style.padding = "0.45rem 0.75rem";
            edit.textContent = "Edit";
            actions.appendChild(edit);

            const deleteForm = document.createElement("form");
            deleteForm.className = "form-hapus";
            deleteForm.action = "hapus.php";
            deleteForm.method = "post";
            deleteForm.style.display = "inline-flex";
            deleteForm.style.margin = "0";

            const id = document.createElement("input");
            id.type = "hidden";
            id.name = "id";
            id.value = book.id;
            deleteForm.appendChild(id);

            const deleteButton = document.createElement("button");
            deleteButton.type = "submit";
            deleteButton.className = "btn-hapus";
            deleteButton.style.width = "auto";
            deleteButton.textContent = "Hapus";
            deleteForm.appendChild(deleteButton);
            actions.appendChild(deleteForm);

            actionCell.appendChild(actions);
            row.appendChild(actionCell);
            tbody.appendChild(row);
        });
    }

    function renderPagination(totalPages, currentPage, query) {
        pagination.replaceChildren();
        pagination.hidden = totalPages <= 1;

        for (let page = 1; page <= totalPages; page += 1) {
            const link = document.createElement("a");
            const params = new URLSearchParams();
            params.set("page", String(page));
            if (query) {
                params.set("q", query);
            }
            link.href = "list.php?" + params.toString();
            link.textContent = String(page);

            if (page === currentPage) {
                link.className = "active";
                link.setAttribute("aria-current", "page");
            }

            pagination.appendChild(link);
        }
    }

    async function searchBooks(query, page = 1) {
        const currentRequest = ++requestNumber;
        if (activeRequest) {
            activeRequest.abort();
        }

        updateSearchUrl(query, page);

        activeRequest = new AbortController();
        const params = new URLSearchParams({ q: query, page: String(page) });
        status.textContent = "Mencari buku...";

        try {
            const response = await fetch(form.dataset.searchUrl + "?" + params.toString(), {
                headers: { Accept: "application/json" },
                signal: activeRequest.signal
            });
            if (!response.ok) {
                throw new Error("Permintaan pencarian gagal (HTTP " + response.status + ").");
            }

            const result = await response.json();
            if (currentRequest !== requestNumber) {
                return;
            }

            renderBooks(result.books);
            renderPagination(result.totalPages, result.page, query);
            term.textContent = query;
            count.textContent = String(result.totalRows);
            summary.hidden = query === "";
            summary.style.display = query === "" ? "none" : "";
            status.textContent = "";
        } catch (error) {
            if (error.name === "AbortError" || currentRequest !== requestNumber) {
                return;
            }
            status.textContent = error.message || "Pencarian buku gagal. Silakan coba lagi.";
        }
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        searchBooks(input.value.trim());
    });

    input.addEventListener("input", function () {
        updateSearchUrl(input.value.trim());
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(function () {
            searchBooks(input.value.trim());
        }, 250);
    });

    pagination.addEventListener("click", function (event) {
        const link = event.target.closest("a");
        if (!link) {
            return;
        }

        event.preventDefault();
        const page = Number(new URL(link.href).searchParams.get("page")) || 1;
        searchBooks(input.value.trim(), page);
    });
}

document.addEventListener("DOMContentLoaded", initBookSearch);
