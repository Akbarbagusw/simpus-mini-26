function initMemberSearch() {
    const form = document.getElementById("member-search-form");
    const input = document.getElementById("search-input");
    const tbody = document.getElementById("member-results-body");
    const status = document.getElementById("member-search-status");
    const canDelete = form && form.dataset.canDelete === "true";

    if (!form || !input || !tbody || !status) {
        return;
    }

    let debounceTimer;
    let activeRequest;
    let requestNumber = 0;

    function updateSearchUrl(query) {
        const url = new URL(window.location.href);
        url.search = "";
        if (query) {
            url.searchParams.set("q", query);
        }
        window.history.replaceState({}, "", url);
    }

    function appendCell(row, value, useCode = false) {
        const cell = document.createElement("td");
        const text = value === null || value === undefined || value === "" ? "-" : String(value);

        if (useCode) {
            const code = document.createElement("code");
            code.textContent = text;
            cell.appendChild(code);
        } else {
            cell.textContent = text;
        }

        row.appendChild(cell);
    }

    function renderMembers(members) {
        tbody.replaceChildren();

        if (members.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = 5;
            cell.textContent = "Tidak ada anggota yang cocok dengan pencarian.";
            row.appendChild(cell);
            tbody.appendChild(row);
            return;
        }

        members.forEach(function (member) {
            const row = document.createElement("tr");
            appendCell(row, member.no_anggota, true);

            const nameCell = document.createElement("td");
            const name = document.createElement("strong");
            name.textContent = member.nama || "-";
            nameCell.appendChild(name);
            row.appendChild(nameCell);

            appendCell(row, member.no_hp);
            appendCell(row, member.alamat);

            const actionCell = document.createElement("td");
            const actions = document.createElement("div");
            actions.style.display = "flex";
            actions.style.gap = "0.5rem";
            actions.style.alignItems = "center";
            actions.style.flexWrap = "wrap";

            const edit = document.createElement("a");
            edit.href = "ubah.php?id=" + encodeURIComponent(member.id);
            edit.className = "btn btn-secondary";
            edit.style.padding = "0.45rem 0.75rem";
            edit.textContent = "Edit";
            actions.appendChild(edit);

            if (canDelete) {
                const deleteForm = document.createElement("form");
                deleteForm.className = "form-hapus";
                deleteForm.action = "hapus.php";
                deleteForm.method = "post";
                deleteForm.style.display = "inline";
                deleteForm.style.margin = "0";

                const id = document.createElement("input");
                id.type = "hidden";
                id.name = "id";
                id.value = member.id;
                deleteForm.appendChild(id);

                const deleteButton = document.createElement("button");
                deleteButton.type = "submit";
                deleteButton.className = "btn-hapus";
                deleteButton.textContent = "Hapus";
                deleteForm.appendChild(deleteButton);
                actions.appendChild(deleteForm);
            }

            actionCell.appendChild(actions);
            row.appendChild(actionCell);
            tbody.appendChild(row);
        });
    }

    async function searchMembers(query) {
        const currentRequest = ++requestNumber;
        if (activeRequest) {
            activeRequest.abort();
        }

        updateSearchUrl(query);
        activeRequest = new AbortController();
        status.textContent = "Mencari anggota...";

        try {
            const params = new URLSearchParams({ q: query });
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

            renderMembers(result.members);
            status.textContent = query
                ? "Ditemukan " + result.members.length + " anggota."
                : "";
        } catch (error) {
            if (error.name === "AbortError" || currentRequest !== requestNumber) {
                return;
            }
            status.textContent = error.message || "Pencarian anggota gagal. Silakan coba lagi.";
        }
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        window.clearTimeout(debounceTimer);
        searchMembers(input.value.trim());
    });

    input.addEventListener("input", function () {
        const query = input.value.trim();
        updateSearchUrl(query);
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(function () {
            searchMembers(query);
        }, 250);
    });

    if (input.value.trim()) {
        searchMembers(input.value.trim());
    }
}

document.addEventListener("DOMContentLoaded", initMemberSearch);
